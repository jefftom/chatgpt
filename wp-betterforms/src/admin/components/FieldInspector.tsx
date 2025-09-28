import { useEffect, useRef } from 'react';
import type { FormField } from '../types';

export interface FieldInspectorProps {
  field: FormField | null;
  onChange: (fieldId: string, updates: Partial<FormField>) => void;
  onDelete: (fieldId: string) => void;
}

export function FieldInspector({ field, onChange, onDelete }: FieldInspectorProps) {
  const labelRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    if (field && labelRef.current) {
      labelRef.current.focus();
    }
  }, [field]);

  if (!field) {
    return (
      <aside className="bf-inspector" aria-label="Field inspector">
        <p>Select a field to edit its settings.</p>
      </aside>
    );
  }

  return (
    <aside className="bf-inspector" aria-label="Field inspector">
      <header className="bf-panel-heading-group">
        <h2 className="bf-panel-heading">{field.label || 'Field'}</h2>
        <button type="button" className="bf-button-danger" onClick={() => onDelete(field.id)}>
          Remove field
        </button>
      </header>
      <label>
        Label
        <input
          ref={labelRef}
          type="text"
          value={field.label}
          onChange={(event) => onChange(field.id, { label: event.target.value })}
        />
      </label>
      <label>
        Placeholder
        <input
          type="text"
          value={field.placeholder ?? ''}
          onChange={(event) => onChange(field.id, { placeholder: event.target.value })}
        />
      </label>
      <label>
        Help text
        <textarea
          value={field.helpText ?? ''}
          onChange={(event) => onChange(field.id, { helpText: event.target.value })}
        />
      </label>
      <label className="bf-field-toggle">
        <input
          type="checkbox"
          checked={Boolean(field.required)}
          onChange={(event) => onChange(field.id, { required: event.target.checked })}
        />
        Required
      </label>
      {(field.type === 'checkbox' || field.type === 'select') && field.options && (
        <div className="bf-inspector__options">
          <h3>Options</h3>
          <ul>
            {field.options.map((option, index) => (
              <li key={option.id}>
                <input
                  type="text"
                  value={option.label}
                  onChange={(event) => {
                    const next = [...field.options!];
                    next[index] = { ...option, label: event.target.value };
                    onChange(field.id, { options: next });
                  }}
                />
              </li>
            ))}
          </ul>
          <button
            type="button"
            className="bf-button-secondary"
            onClick={() =>
              onChange(field.id, {
                options: [
                  ...(field.options ?? []),
                  { id: crypto.randomUUID(), label: `Option ${field.options.length + 1}` },
                ],
              })
            }
          >
            Add option
          </button>
        </div>
      )}
      {field.type === 'repeater' && <p>This repeater supports nested fields. Drag items onto it from the palette.</p>}
    </aside>
  );
}
