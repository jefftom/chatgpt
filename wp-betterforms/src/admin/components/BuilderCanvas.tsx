import { useCallback, useMemo, useRef } from 'react';
import type { FormField } from '../types';
import { FIELD_PALETTE_DATA_TYPE, PalettePayload } from './FieldPalette';
import { addFieldAt, createField, DropLocation, moveField } from '../utils/schema';

const NODE_DATA_TYPE = 'application/x-bf-field-node';

export interface BuilderCanvasProps {
  fields: FormField[];
  selectedFieldId: string | null;
  onChange: (fields: FormField[]) => void;
  onSelect: (fieldId: string | null) => void;
  onCreate: (field: FormField) => void;
}

interface DropPayload {
  mode: 'move';
  fieldId: string;
}

type DragPayload = PalettePayload | DropPayload;

function parsePayload(event: React.DragEvent<HTMLElement>): DragPayload | null {
  const paletteData = event.dataTransfer.getData(FIELD_PALETTE_DATA_TYPE);
  if (paletteData) {
    try {
      return JSON.parse(paletteData) as PalettePayload;
    } catch (error) {
      console.error('Unable to parse palette payload', error);
      return null;
    }
  }

  const nodeData = event.dataTransfer.getData(NODE_DATA_TYPE);
  if (nodeData) {
    try {
      return JSON.parse(nodeData) as DropPayload;
    } catch (error) {
      console.error('Unable to parse node payload', error);
      return null;
    }
  }

  return null;
}

export function BuilderCanvas({ fields, selectedFieldId, onChange, onSelect, onCreate }: BuilderCanvasProps) {
  const liveRegionRef = useRef<HTMLDivElement>(null);

  const announce = useCallback((message: string) => {
    const region = liveRegionRef.current;
    if (!region) {
      return;
    }
    region.textContent = '';
    window.requestAnimationFrame(() => {
      region.textContent = message;
    });
  }, []);

  const handleDrop = useCallback(
    (location: DropLocation) =>
      (event: React.DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        const payload = parsePayload(event);
        if (!payload) {
          return;
        }

        if (payload.mode === 'create') {
          const field = createField(payload.fieldType);
          onChange(addFieldAt(fields, field, location));
          onCreate(field);
          announce(`Added ${payload.fieldType} field.`);
          return;
        }

        if (payload.mode === 'move') {
          if (payload.fieldId === undefined) {
            return;
          }
          onChange(moveField(fields, payload.fieldId, location));
          announce('Field moved.');
        }
      },
    [announce, fields, onChange, onCreate],
  );

  const handleDragOver = useCallback((event: React.DragEvent<HTMLDivElement>) => {
    const payload = parsePayload(event);
    event.preventDefault();
    event.dataTransfer.dropEffect = payload?.mode === 'create' ? 'copy' : 'move';
  }, []);

  const handleDragStart = useCallback((fieldId: string) => (event: React.DragEvent<HTMLButtonElement>) => {
    const payload: DropPayload = { mode: 'move', fieldId };
    event.dataTransfer.setData(NODE_DATA_TYPE, JSON.stringify(payload));
    event.dataTransfer.effectAllowed = 'move';
  }, []);

  const fieldOptions = useMemo(() => fields.length, [fields]);

  return (
    <section className="bf-canvas" aria-label="Form builder canvas">
      <div className="bf-canvas__dropzone" onDrop={handleDrop({ parentId: null, index: 0 })} onDragOver={handleDragOver}>
        {fields.length === 0 && <p>Drag fields here or select one from the palette to get started.</p>}
      </div>
      <div className="bf-canvas__list" role="list">
        {fields.map((field, index) => (
          <FieldNode
            key={field.id}
            field={field}
            index={index}
            parentId={null}
            depth={0}
            onDrop={handleDrop}
            onDragOver={handleDragOver}
            onDragStart={handleDragStart}
            onSelect={onSelect}
            selectedFieldId={selectedFieldId}
          />
        ))}
      </div>
      <div className="bf-canvas__dropzone" onDrop={handleDrop({ parentId: null, index: fieldOptions })} onDragOver={handleDragOver}>
        <p>Add field to the end of the form.</p>
      </div>
      <div ref={liveRegionRef} className="sr-only" aria-live="polite" aria-atomic="true" />
    </section>
  );
}

interface FieldNodeProps {
  field: FormField;
  index: number;
  parentId: string | null;
  depth: number;
  selectedFieldId: string | null;
  onDrop: (location: DropLocation) => (event: React.DragEvent<HTMLDivElement>) => void;
  onDragOver: (event: React.DragEvent<HTMLDivElement>) => void;
  onDragStart: (fieldId: string) => (event: React.DragEvent<HTMLButtonElement>) => void;
  onSelect: (fieldId: string | null) => void;
}

function FieldNode({
  field,
  index,
  parentId,
  depth,
  selectedFieldId,
  onDrop,
  onDragOver,
  onDragStart,
  onSelect,
}: FieldNodeProps) {
  const isSelected = field.id === selectedFieldId;
  const dropBefore = useMemo<DropLocation>(() => ({ parentId, index }), [index, parentId]);
  const dropAfter = useMemo<DropLocation>(() => ({ parentId, index: index + 1 }), [index, parentId]);
  const childDrop = useMemo<DropLocation>(() => ({ parentId: field.id, index: field.children?.length ?? 0 }), [field.children?.length, field.id]);

  return (
    <div className={`bf-field-node ${isSelected ? 'is-selected' : ''}`} role="listitem">
      <div className="bf-drop-target" onDrop={onDrop(dropBefore)} onDragOver={onDragOver} aria-hidden="true" />
      <div className="bf-field-node__body" data-depth={depth}>
        <div className="bf-field-node__header">
          <button
            type="button"
            className="bf-field-node__drag"
            aria-label={`Drag handle for ${field.label}`}
            draggable
            onDragStart={onDragStart(field.id)}
          >
            ::
          </button>
          <button
            type="button"
            className="bf-field-node__label"
            onClick={() => onSelect(field.id)}
            aria-pressed={isSelected}
          >
            <span className="bf-field-node__type">{field.type}</span>
            <span className="bf-field-node__name">{field.label}</span>
          </button>
        </div>
        {field.children && (
          <div className="bf-field-node__children" aria-label={`Children for ${field.label}`}>
            {field.children.map((child, childIndex) => (
              <FieldNode
                key={child.id}
                field={child}
                index={childIndex}
                parentId={field.id}
                depth={depth + 1}
                selectedFieldId={selectedFieldId}
                onDrop={onDrop}
                onDragOver={onDragOver}
                onDragStart={onDragStart}
                onSelect={onSelect}
              />
            ))}
            <div className="bf-drop-target is-inner" onDrop={onDrop(childDrop)} onDragOver={onDragOver}>
              <p>Add fields inside this repeater</p>
            </div>
          </div>
        )}
      </div>
      <div className="bf-drop-target" onDrop={onDrop(dropAfter)} onDragOver={onDragOver} aria-hidden="true" />
    </div>
  );
}
