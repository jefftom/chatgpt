import type { IntegrationSettings } from '../types';

export interface IntegrationsPanelProps {
  integrations: IntegrationSettings;
  onChange: (value: IntegrationSettings) => void;
}

export function IntegrationsPanel({ integrations, onChange }: IntegrationsPanelProps) {
  const update = <K extends keyof IntegrationSettings>(key: K, value: IntegrationSettings[K]) => {
    onChange({ ...integrations, [key]: value });
  };

  return (
    <section className="bf-integrations" aria-label="Integrations">
      <h2 className="bf-panel-heading">Integrations</h2>
      <fieldset>
        <legend>Email notifications</legend>
        <label>
          <input
            type="checkbox"
            checked={integrations.emailNotifications.enabled}
            onChange={(event) =>
              update('emailNotifications', {
                ...integrations.emailNotifications,
                enabled: event.target.checked,
              })
            }
          />
          Enabled
        </label>
        <label>
          Recipients
          <input
            type="text"
            value={integrations.emailNotifications.recipients}
            onChange={(event) =>
              update('emailNotifications', {
                ...integrations.emailNotifications,
                recipients: event.target.value,
              })
            }
            placeholder="editor@example.com"
          />
        </label>
      </fieldset>
      <fieldset>
        <legend>Webhook</legend>
        <label>
          <input
            type="checkbox"
            checked={integrations.webhook.enabled}
            onChange={(event) =>
              update('webhook', {
                ...integrations.webhook,
                enabled: event.target.checked,
              })
            }
          />
          Enabled
        </label>
        <label>
          URL
          <input
            type="url"
            value={integrations.webhook.url}
            onChange={(event) =>
              update('webhook', {
                ...integrations.webhook,
                url: event.target.value,
              })
            }
            placeholder="https://example.com/webhook"
          />
        </label>
      </fieldset>
    </section>
  );
}
