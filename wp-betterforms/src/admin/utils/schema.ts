import { FieldType, FormField } from '../types';

export function createField(type: FieldType): FormField {
  const base: FormField = {
    id: crypto.randomUUID(),
    type,
    label: defaultLabel(type),
    required: false,
    placeholder: '',
    helpText: '',
  };

  if (type === 'select' || type === 'checkbox') {
    base.options = [
      { id: crypto.randomUUID(), label: 'Option 1' },
      { id: crypto.randomUUID(), label: 'Option 2' },
    ];
  }

  if (type === 'repeater') {
    base.children = [];
  }

  return base;
}

function defaultLabel(type: FieldType): string {
  switch (type) {
    case 'text':
      return 'Text field';
    case 'textarea':
      return 'Paragraph';
    case 'email':
      return 'Email';
    case 'number':
      return 'Number';
    case 'checkbox':
      return 'Checkboxes';
    case 'select':
      return 'Dropdown';
    case 'repeater':
      return 'Repeater';
    default:
      return 'Field';
  }
}

export function findField(fields: FormField[], fieldId: string): FormField | null {
  for (const field of fields) {
    if (field.id === fieldId) {
      return field;
    }

    if (field.children) {
      const nested = findField(field.children, fieldId);
      if (nested) {
        return nested;
      }
    }
  }

  return null;
}

export function updateField(
  fields: FormField[],
  fieldId: string,
  updater: (field: FormField) => FormField,
): FormField[] {
  return fields.map((field) => {
    if (field.id === fieldId) {
      return updater(field);
    }

    if (field.children) {
      return {
        ...field,
        children: updateField(field.children, fieldId, updater),
      };
    }

    return field;
  });
}

export function removeField(fields: FormField[], fieldId: string): FormField[] {
  return fields
    .filter((field) => field.id !== fieldId)
    .map((field) =>
      field.children
        ? {
            ...field,
            children: removeField(field.children, fieldId),
          }
        : field,
    );
}

function insertField(
  fields: FormField[],
  field: FormField,
  parentId: string | null,
  index: number,
): FormField[] {
  if (!parentId) {
    const clone = [...fields];
    clone.splice(index, 0, field);
    return clone;
  }

  return fields.map((existing) => {
    if (existing.id === parentId) {
      const children = existing.children ? [...existing.children] : [];
      children.splice(index, 0, field);
      return {
        ...existing,
        children,
      };
    }

    if (existing.children) {
      return {
        ...existing,
        children: insertField(existing.children, field, parentId, index),
      };
    }

    return existing;
  });
}

export type DropLocation = {
  parentId: string | null;
  index: number;
};

export function addFieldAt(
  fields: FormField[],
  field: FormField,
  location: DropLocation,
): FormField[] {
  return insertField(fields, field, location.parentId, location.index);
}

export function moveField(
  fields: FormField[],
  fieldId: string,
  location: DropLocation,
): FormField[] {
  const field = findField(fields, fieldId);
  if (!field) {
    return fields;
  }

  const without = removeField(fields, fieldId);
  return insertField(without, field, location.parentId, location.index);
}

export function flattenFields(fields: FormField[]): FormField[] {
  return fields.flatMap((field) =>
    field.children && field.children.length
      ? [field, ...flattenFields(field.children)]
      : [field],
  );
}
