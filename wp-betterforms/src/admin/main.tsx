import './styles.css';
import { createRoot } from 'react-dom/client';
import { StrictMode, useCallback, useMemo, useState } from 'react';
import type { ChangeEvent } from 'react';
import { FormList } from './components/FormList';
import type { BuilderForm, FormField, FormSchema, SingleFormResponse } from './types';
import { BuilderCanvas } from './components/BuilderCanvas';
import { FieldPalette } from './components/FieldPalette';
import { FieldInspector } from './components/FieldInspector';
import { LogicEditor } from './components/LogicEditor';
import { StylerPane } from './components/StylerPane';
import { IntegrationsPanel } from './components/IntegrationsPanel';
import { getApiBase, getNonce } from './hooks/useForms';
import { createField, findField, removeField, updateField } from './utils/schema';

const rootEl = document.getElementById('wp-betterforms-admin');

const SCHEMA_TEMPLATE: FormSchema = {
  fields: [],
  logic: { rules: [] },
  integrations: {
    emailNotifications: {
      enabled: true,
      recipients: '',
    },
    webhook: {
      enabled: false,
      url: '',
    },
  },
};

function createDefaultSchema(): FormSchema {
  return {
    fields: [],
    logic: { rules: [] },
    integrations: {
      emailNotifications: { ...SCHEMA_TEMPLATE.integrations.emailNotifications },
      webhook: { ...SCHEMA_TEMPLATE.integrations.webhook },
    },
  };
}

function createDefaultStyles() {
  return {
    preset: 'minimal' as const,
    global: {},
    form: {},
  };
}

function normalizeForm(form: BuilderForm): BuilderForm {
  const defaults = createDefaultSchema();
  const defaultStyles = createDefaultStyles();
  const schema = form.schema ?? defaults;
  const styles = form.styles ?? defaultStyles;

  return {
    ...form,
    schema: {
      ...defaults,
      ...schema,
      fields: Array.isArray(schema.fields) ? schema.fields : [],
      logic: schema.logic ?? defaults.logic,
      integrations: {
        emailNotifications: {
          ...defaults.integrations.emailNotifications,
          ...(schema.integrations?.emailNotifications ?? {}),
        },
        webhook: {
          ...defaults.integrations.webhook,
          ...(schema.integrations?.webhook ?? {}),
        },
      },
    },
    styles: {
      ...defaultStyles,
      ...styles,
      global: styles.global ?? {},
      form: styles.form ?? {},
    },
  };
}

function createNewForm(): BuilderForm {
  const slug = `form-${Date.now()}`;
  return normalizeForm({
    title: 'Untitled form',
    slug,
    status: 'draft',
    schema: createDefaultSchema(),
    styles: createDefaultStyles(),
  });
}

function App() {
  const [currentForm, setCurrentForm] = useState<BuilderForm | null>(null);
  const [selectedFieldId, setSelectedFieldId] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [statusMessage, setStatusMessage] = useState<string | null>(null);
  const [loadingForm, setLoadingForm] = useState(false);

  const nonce = getNonce();
  const apiBase = getApiBase();

  const selectedField = useMemo(() => {
    if (!currentForm || !selectedFieldId) {
      return null;
    }
    return findField(currentForm.schema.fields, selectedFieldId);
  }, [currentForm, selectedFieldId]);

  const updateForm = useCallback(
    (updater: (form: BuilderForm) => BuilderForm) => {
      setCurrentForm((form) => (form ? updater(form) : form));
    },
    [],
  );

  const startNewForm = () => {
    setSelectedFieldId(null);
    setStatusMessage(null);
    setCurrentForm(createNewForm());
  };

  const loadForm = async (formId: number) => {
    setLoadingForm(true);
    setStatusMessage(null);
    try {
      const response = await fetch(`${apiBase}/${formId}`, {
        headers: {
          'X-WP-Nonce': nonce,
        },
      });
      if (!response.ok) {
        throw new Error('Unable to load form');
      }
      const data = (await response.json()) as SingleFormResponse;
      setCurrentForm(normalizeForm(data.form));
      setSelectedFieldId(null);
    } catch (error) {
      console.error('Failed to load form', error);
      setStatusMessage('Unable to load the requested form.');
    } finally {
      setLoadingForm(false);
    }
  };

  const handleFieldCreate = (field: FormField) => {
    setSelectedFieldId(field.id);
  };

  const handleFieldChange = (fields: FormField[]) => {
    updateForm((form) => ({
      ...form,
      schema: {
        ...form.schema,
        fields,
      },
    }));
  };

  const handleInspectorChange = (fieldId: string, updates: Partial<FormField>) => {
    updateForm((form) => ({
      ...form,
      schema: {
        ...form.schema,
        fields: updateField(form.schema.fields, fieldId, (field) => ({
          ...field,
          ...updates,
        })),
      },
    }));
  };

  const handleFieldDelete = (fieldId: string) => {
    updateForm((form) => ({
      ...form,
      schema: {
        ...form.schema,
        fields: removeField(form.schema.fields, fieldId),
      },
    }));
    setSelectedFieldId(null);
  };

  const handleLogicChange = (logic: BuilderForm['schema']['logic']) => {
    updateForm((form) => ({
      ...form,
      schema: {
        ...form.schema,
        logic,
      },
    }));
  };

  const handleIntegrationsChange = (integrations: BuilderForm['schema']['integrations']) => {
    updateForm((form) => ({
      ...form,
      schema: {
        ...form.schema,
        integrations,
      },
    }));
  };

  const handleStyleChange = (styles: BuilderForm['styles']) => {
    updateForm((form) => ({
      ...form,
      styles,
    }));
  };

  const handleMetaChange = (event: ChangeEvent<HTMLInputElement>) => {
    const { name, value } = event.target;
    updateForm((form) => ({
      ...form,
      [name]: value,
    }));
  };

  const saveForm = async () => {
    if (!currentForm) {
      return;
    }
    setSaving(true);
    setStatusMessage(null);
    try {
      const response = await fetch(apiBase, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': nonce,
        },
        body: JSON.stringify(currentForm),
      });
      if (!response.ok) {
        throw new Error('Unable to save form');
      }
      const data = (await response.json()) as SingleFormResponse;
      setCurrentForm(normalizeForm(data.form));
      setStatusMessage('Form saved successfully.');
    } catch (error) {
      console.error('Failed to save form', error);
      setStatusMessage('Unable to save form.');
    } finally {
      setSaving(false);
    }
  };

  if (!rootEl) {
    return null;
  }

  return (
    <main className="bf-admin" aria-busy={saving}>
      {!currentForm ? (
        <FormList onCreate={startNewForm} onOpen={loadForm} />
      ) : (
        <div className="bf-builder" role="region" aria-live="polite">
          <header className="bf-builder__header">
            <div className="bf-builder__meta">
              <label>
                <span className="bf-label">Form title</span>
                <input name="title" value={currentForm.title} onChange={handleMetaChange} />
              </label>
              <label>
                <span className="bf-label">Slug</span>
                <input name="slug" value={currentForm.slug} onChange={handleMetaChange} />
              </label>
            </div>
            <div className="bf-builder__actions">
              <button type="button" className="bf-button-secondary" onClick={() => setCurrentForm(null)}>
                Back to forms
              </button>
              <button type="button" className="bf-button" onClick={saveForm} disabled={saving}>
                {saving ? 'Saving…' : 'Save form'}
              </button>
            </div>
          </header>
          {statusMessage && (
            <p className="bf-status" role="status">
              {statusMessage}
            </p>
          )}
          {loadingForm ? (
            <p>Loading form…</p>
          ) : (
            <div className="bf-builder__layout">
              <FieldPalette
                onAdd={(type) => {
                  const field = createField(type);
                  handleFieldChange([...currentForm.schema.fields, field]);
                  handleFieldCreate(field);
                }}
              />
              <BuilderCanvas
                fields={currentForm.schema.fields}
                selectedFieldId={selectedFieldId}
                onChange={handleFieldChange}
                onSelect={setSelectedFieldId}
                onCreate={handleFieldCreate}
              />
              <FieldInspector field={selectedField} onChange={handleInspectorChange} onDelete={handleFieldDelete} />
            </div>
          )}
          <div className="bf-builder__secondary">
            <LogicEditor logic={currentForm.schema.logic} fields={currentForm.schema.fields} onChange={handleLogicChange} />
            <IntegrationsPanel integrations={currentForm.schema.integrations} onChange={handleIntegrationsChange} />
            <StylerPane styles={currentForm.styles} onChange={handleStyleChange} />
          </div>
        </div>
      )}
      <div className="sr-only" aria-live="polite">
        {statusMessage}
      </div>
    </main>
  );
}

if (rootEl) {
  const root = createRoot(rootEl);
  root.render(
    <StrictMode>
      <App />
    </StrictMode>,
  );
}
