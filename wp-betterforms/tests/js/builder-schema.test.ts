import { describe, expect, it } from 'vitest';
import { addFieldAt, createField, findField, moveField, removeField } from '../../src/admin/utils/schema';

describe('schema utilities', () => {
  it('creates default repeater with nested children array', () => {
    const repeater = createField('repeater');
    expect(repeater.children).toEqual([]);
  });

  it('adds fields at root and nested locations', () => {
    const repeater = createField('repeater');
    let fields = addFieldAt([], repeater, { parentId: null, index: 0 });
    const child = createField('text');
    fields = addFieldAt(fields, child, { parentId: repeater.id, index: 0 });

    expect(fields).toHaveLength(1);
    expect(fields[0].children).toHaveLength(1);
    expect(fields[0].children?.[0].id).toBe(child.id);
  });

  it('moves a field between parents', () => {
    const repeater = createField('repeater');
    const text = createField('text');
    const another = createField('email');

    let fields = [repeater, text];
    fields = addFieldAt(fields, another, { parentId: repeater.id, index: 0 });

    fields = moveField(fields, text.id, { parentId: repeater.id, index: 1 });

    const parent = findField(fields, repeater.id);
    expect(parent?.children?.map((child) => child.id)).toEqual([another.id, text.id]);
  });

  it('removes fields recursively', () => {
    const repeater = createField('repeater');
    const child = createField('text');
    let fields = addFieldAt([], repeater, { parentId: null, index: 0 });
    fields = addFieldAt(fields, child, { parentId: repeater.id, index: 0 });

    fields = removeField(fields, child.id);
    expect(findField(fields, child.id)).toBeNull();
  });
});
