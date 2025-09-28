import { useMemo } from 'react';
import type { FieldType } from '../types';

const CATALOG: { type: FieldType; label: string; description: string }[] = [
  { type: 'text', label: 'Text', description: 'Single line text input.' },
  { type: 'textarea', label: 'Paragraph', description: 'Multi-line textarea.' },
  { type: 'email', label: 'Email', description: 'Email address field with validation.' },
  { type: 'number', label: 'Number', description: 'Numeric input supporting step configuration.' },
  { type: 'checkbox', label: 'Checkboxes', description: 'Multiple selection checkboxes.' },
  { type: 'select', label: 'Dropdown', description: 'Single selection dropdown menu.' },
  { type: 'repeater', label: 'Repeater', description: 'Nested section that can repeat fields.' },
];

export const FIELD_PALETTE_DATA_TYPE = 'application/x-bf-palette-field';

export interface FieldPaletteProps {
  onAdd: (type: FieldType) => void;
}

export function FieldPalette({ onAdd }: FieldPaletteProps) {
  const items = useMemo(() => CATALOG, []);

  return (
    <aside className="bf-palette" aria-label="Field palette">
      <h2 className="bf-panel-heading">Fields</h2>
      <ul className="bf-palette__list">
        {items.map((item) => (
          <li key={item.type}>
            <button
              type="button"
              className="bf-palette__item"
              draggable
              onDragStart={(event) => {
                event.dataTransfer.effectAllowed = 'copy';
                event.dataTransfer.setData(
                  FIELD_PALETTE_DATA_TYPE,
                  JSON.stringify({ mode: 'create', fieldType: item.type }),
                );
              }}
              onClick={() => onAdd(item.type)}
            >
              <span className="bf-palette__item-label">{item.label}</span>
              <span className="bf-palette__item-description">{item.description}</span>
            </button>
          </li>
        ))}
      </ul>
    </aside>
  );
}

export type PalettePayload = {
  mode: 'create';
  fieldType: FieldType;
};
