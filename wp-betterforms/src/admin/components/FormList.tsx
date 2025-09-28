import { useRef } from 'react';
import { useForms } from '../hooks/useForms';

export interface FormListProps {
  onCreate: () => void;
  onOpen: (formId: number) => void;
}

export function FormList({ onCreate, onOpen }: FormListProps) {
  const { forms, loading, error, refresh } = useForms();
  const liveRef = useRef<HTMLParagraphElement>(null);

  return (
    <section className="bf-admin-panel" aria-label="Forms">
      <header className="bf-admin-panel__header">
        <div>
          <h1>WP Better Forms</h1>
          <p className="bf-admin-panel__subtitle">Design dynamic, accessible forms with conditional logic and advanced styling.</p>
        </div>
        <div className="bf-admin-panel__actions">
          <button type="button" className="bf-button-secondary" onClick={() => refresh()} disabled={loading}>
            Refresh
          </button>
          <button type="button" onClick={onCreate} className="bf-button">
            New form
          </button>
        </div>
      </header>
      {loading && (
        <p role="status" ref={liveRef} aria-live="polite">
          Loading forms…
        </p>
      )}
      {error && (
        <p className="bf-error" role="alert">
          {error}
        </p>
      )}
      <ul className="bf-form-list" role="list">
        {forms.map((form) => (
          <li key={form.id}>
            <div>
              <p className="bf-form-list__title">{form.title}</p>
              <p className="bf-form-list__meta">
                <span>{form.status}</span>
                {(() => {
                  const timestamp = new Date(form.updated_at);
                  const label = Number.isNaN(timestamp.getTime())
                    ? 'Never updated'
                    : `Updated ${timestamp.toLocaleString()}`;
                  return <time dateTime={form.updated_at || ''}>{label}</time>;
                })()}
              </p>
            </div>
            <div className="bf-form-list__actions">
              <button type="button" className="bf-button-secondary" onClick={() => onOpen(form.id)}>
                Edit
              </button>
            </div>
          </li>
        ))}
      </ul>
      {forms.length === 0 && !loading && <p>No forms yet. Create one to get started.</p>}
    </section>
  );
}
