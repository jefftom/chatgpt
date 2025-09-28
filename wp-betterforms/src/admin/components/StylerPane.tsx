import { useMemo, useState } from 'react';
import type { CSSProperties } from 'react';
import type { StylerPreset, StylerTokens } from '../types';

const PRESET_MAP: Record<StylerPreset, Record<string, string>> = {
  minimal: {
    '--bf-control-border': '1px solid rgba(148, 163, 184, 0.5)',
    '--bf-control-padding': '0.625rem 0.75rem',
    '--bf-control-radius': '0.75rem',
    '--bf-control-bg': 'rgba(255, 255, 255, 0.9)',
    '--bf-label-weight': '600',
  },
  outlined: {
    '--bf-control-border': '2px solid rgba(37, 99, 235, 0.4)',
    '--bf-control-padding': '0.75rem 1rem',
    '--bf-control-radius': '0.75rem',
    '--bf-control-bg': 'white',
    '--bf-label-weight': '500',
  },
  filled: {
    '--bf-control-border': '1px solid transparent',
    '--bf-control-padding': '0.75rem 1rem',
    '--bf-control-radius': '0.75rem',
    '--bf-control-bg': 'rgba(59, 130, 246, 0.12)',
    '--bf-label-weight': '600',
  },
  underline: {
    '--bf-control-border': '0 0 2px 0 rgba(148, 163, 184, 0.8)',
    '--bf-control-padding': '0.5rem 0',
    '--bf-control-radius': '0',
    '--bf-control-bg': 'transparent',
    '--bf-label-weight': '500',
  },
  compact: {
    '--bf-control-border': '1px solid rgba(148, 163, 184, 0.3)',
    '--bf-control-padding': '0.25rem 0.5rem',
    '--bf-control-radius': '0.5rem',
    '--bf-control-bg': 'white',
    '--bf-label-weight': '500',
  },
  spacious: {
    '--bf-control-border': '1px solid rgba(37, 99, 235, 0.25)',
    '--bf-control-padding': '1rem 1.25rem',
    '--bf-control-radius': '1rem',
    '--bf-control-bg': 'rgba(37, 99, 235, 0.05)',
    '--bf-label-weight': '600',
  },
  'dark-auto': {
    '--bf-control-border': '1px solid rgba(148, 163, 184, 0.4)',
    '--bf-control-padding': '0.75rem 1rem',
    '--bf-control-radius': '0.75rem',
    '--bf-control-bg': 'rgba(15, 23, 42, 0.4)',
    '--bf-label-weight': '600',
    '--bf-surface-bg': 'rgba(15, 23, 42, 0.75)',
    '--bf-surface-color': 'rgba(248, 250, 252, 1)',
  },
};

interface StylerPaneProps {
  styles: StylerTokens;
  onChange: (styles: StylerTokens) => void;
}

export function StylerPane({ styles, onChange }: StylerPaneProps) {
  const [isImporting, setIsImporting] = useState(false);
  const [importText, setImportText] = useState('');

  const presetOptions = useMemo(() => Object.keys(PRESET_MAP) as StylerPreset[], []);
  const presetTokens = useMemo(() => PRESET_MAP[styles.preset], [styles.preset]);
  const mergedTokens = useMemo(() => ({ ...presetTokens, ...styles.global, ...styles.form }), [presetTokens, styles.form, styles.global]);

  const updatePreset = (preset: StylerPreset) => {
    onChange({ ...styles, preset });
  };

  const updateToken = (scope: 'global' | 'form', key: string, value: string) => {
    onChange({ ...styles, [scope]: { ...styles[scope], [key]: value } });
  };

  const removeToken = (scope: 'global' | 'form', key: string) => {
    const next = { ...styles[scope] };
    delete next[key];
    onChange({ ...styles, [scope]: next });
  };

  const exportJson = () => {
    const payload = JSON.stringify(styles, null, 2);
    navigator.clipboard?.writeText(payload).catch(() => {
      setIsImporting(true);
      setImportText(payload);
    });
  };

  const applyImport = () => {
    try {
      const parsed = JSON.parse(importText) as StylerTokens;
      if (!parsed || typeof parsed !== 'object') {
        return;
      }
      onChange(parsed);
      setIsImporting(false);
      setImportText('');
    } catch (error) {
      console.error('Invalid style JSON', error);
    }
  };

  const previewStyle = useMemo(() => {
    const cssVars = Object.entries(mergedTokens).reduce<Record<string, string>>((acc, [key, value]) => {
      if (!key.startsWith('--')) {
        acc[`--${key}`] = value;
        return acc;
      }
      acc[key] = value;
      return acc;
    }, {});

    return cssVars;
  }, [mergedTokens]);

  return (
    <section className="bf-styler" aria-label="Visual styler">
      <div className="bf-panel-heading-group">
        <h2 className="bf-panel-heading">Visual styler</h2>
        <div className="bf-styler__actions">
          <button type="button" className="bf-button-secondary" onClick={exportJson}>
            Export JSON
          </button>
          <button type="button" className="bf-button-secondary" onClick={() => setIsImporting((value) => !value)}>
            {isImporting ? 'Cancel' : 'Import JSON'}
          </button>
        </div>
      </div>
      <label className="bf-styler__preset">
        Preset
        <select value={styles.preset} onChange={(event) => updatePreset(event.target.value as StylerPreset)}>
          {presetOptions.map((preset) => (
            <option key={preset} value={preset}>
              {preset.replace('-', ' ')}
            </option>
          ))}
        </select>
      </label>
      {isImporting && (
        <div className="bf-styler__import">
          <textarea
            value={importText}
            onChange={(event) => setImportText(event.target.value)}
            placeholder="Paste style JSON"
          />
          <button type="button" className="bf-button" onClick={applyImport}>
            Apply styles
          </button>
        </div>
      )}
      <div className="bf-styler__tokens">
        <TokenTable title="Global tokens" tokens={styles.global} preset={presetTokens} onChange={(key, value) => updateToken('global', key, value)} onRemove={(key) => removeToken('global', key)} />
        <TokenTable title="Form overrides" tokens={styles.form} preset={presetTokens} onChange={(key, value) => updateToken('form', key, value)} onRemove={(key) => removeToken('form', key)} />
      </div>
      <div className="bf-styler__preview" style={previewStyle as CSSProperties}>
        <div className="bf-styler__preview-surface">
          <h3>Preview</h3>
          <label>
            <span>Name</span>
            <input type="text" placeholder="Ada Lovelace" style={{ padding: 'var(--bf-control-padding)', borderRadius: 'var(--bf-control-radius)', border: 'var(--bf-control-border)', background: 'var(--bf-control-bg)', fontWeight: 'var(--bf-label-weight)' }} />
          </label>
          <label>
            <span>Email</span>
            <input type="email" placeholder="ada@example.com" style={{ padding: 'var(--bf-control-padding)', borderRadius: 'var(--bf-control-radius)', border: 'var(--bf-control-border)', background: 'var(--bf-control-bg)', fontWeight: 'var(--bf-label-weight)' }} />
          </label>
          <label>
            <span>Message</span>
            <textarea placeholder="Share a note" style={{ padding: 'var(--bf-control-padding)', borderRadius: 'var(--bf-control-radius)', border: 'var(--bf-control-border)', background: 'var(--bf-control-bg)', fontWeight: 'var(--bf-label-weight)' }} />
          </label>
          <button type="button" className="bf-button">Submit</button>
        </div>
      </div>
    </section>
  );
}

interface TokenTableProps {
  title: string;
  tokens: Record<string, string>;
  preset: Record<string, string>;
  onChange: (key: string, value: string) => void;
  onRemove: (key: string) => void;
}

function TokenTable({ title, tokens, preset, onChange, onRemove }: TokenTableProps) {
  const tokenEntries = useMemo(() => Object.entries({ ...preset, ...tokens }), [preset, tokens]);

  return (
    <div className="bf-token-table">
      <h3>{title}</h3>
      <table>
        <thead>
          <tr>
            <th>Token</th>
            <th>Value</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {tokenEntries.map(([key, value]) => (
            <tr key={key}>
              <td>{key}</td>
              <td>
                <input value={tokens[key] ?? value} onChange={(event) => onChange(key, event.target.value)} />
              </td>
              <td>
                {tokens[key] && (
                  <button type="button" className="bf-button-danger" onClick={() => onRemove(key)}>
                    Reset
                  </button>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
