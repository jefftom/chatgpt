import { useMemo } from 'react';
import type { FormField, FormLogic, LogicRule } from '../types';
import { flattenFields } from '../utils/schema';

export interface LogicEditorProps {
  logic: FormLogic;
  fields: FormField[];
  onChange: (logic: FormLogic) => void;
}

const OPERATORS: { value: LogicRule['operator']; label: string }[] = [
  { value: 'equals', label: 'equals' },
  { value: 'not_equals', label: 'does not equal' },
  { value: 'contains', label: 'contains' },
  { value: 'greater_than', label: 'is greater than' },
  { value: 'less_than', label: 'is less than' },
];

const ACTIONS: { value: LogicRule['action']; label: string }[] = [
  { value: 'show', label: 'show field' },
  { value: 'hide', label: 'hide field' },
  { value: 'enable', label: 'enable field' },
  { value: 'disable', label: 'disable field' },
];

export function LogicEditor({ logic, fields, onChange }: LogicEditorProps) {
  const flatFields = useMemo(
    () =>
      flattenFields(fields).map((field) => ({
        id: field.id,
        label: field.label,
      })),
    [fields],
  );

  const updateRule = (rule: LogicRule, index: number) => {
    const nextRules = [...logic.rules];
    nextRules[index] = rule;
    onChange({ rules: nextRules });
  };

  const removeRule = (index: number) => {
    const nextRules = [...logic.rules];
    nextRules.splice(index, 1);
    onChange({ rules: nextRules });
  };

  const addRule = () => {
    const firstField = flatFields[0];
    const id = crypto.randomUUID();
    const rule: LogicRule = {
      id,
      fieldId: firstField?.id ?? '',
      operator: 'equals',
      value: '',
      action: 'show',
      targetId: firstField?.id ?? '',
    };
    onChange({ rules: [...logic.rules, rule] });
  };

  return (
    <section className="bf-logic" aria-label="Conditional logic">
      <div className="bf-panel-heading-group">
        <h2 className="bf-panel-heading">Conditional logic</h2>
        <button type="button" className="bf-button-secondary" onClick={addRule}>
          Add rule
        </button>
      </div>
      {logic.rules.length === 0 && <p>No logic yet. Add a rule to start orchestrating conditional behaviors.</p>}
      <ol className="bf-logic__list">
        {logic.rules.map((rule, index) => (
          <li key={rule.id} className="bf-logic__rule">
            <div className="bf-logic__row">
              <label>
                When
                <select
                  value={rule.fieldId}
                  onChange={(event) => updateRule({ ...rule, fieldId: event.target.value }, index)}
                >
                  {flatFields.map((field) => (
                    <option key={field.id} value={field.id}>
                      {field.label || field.id}
                    </option>
                  ))}
                </select>
              </label>
              <label>
                Operator
                <select
                  value={rule.operator}
                  onChange={(event) => updateRule({ ...rule, operator: event.target.value as LogicRule['operator'] }, index)}
                >
                  {OPERATORS.map((operator) => (
                    <option key={operator.value} value={operator.value}>
                      {operator.label}
                    </option>
                  ))}
                </select>
              </label>
              <label>
                Value
                <input value={rule.value} onChange={(event) => updateRule({ ...rule, value: event.target.value }, index)} />
              </label>
            </div>
            <div className="bf-logic__row">
              <label>
                Then
                <select
                  value={rule.action}
                  onChange={(event) => updateRule({ ...rule, action: event.target.value as LogicRule['action'] }, index)}
                >
                  {ACTIONS.map((action) => (
                    <option key={action.value} value={action.value}>
                      {action.label}
                    </option>
                  ))}
                </select>
              </label>
              <label>
                Target field
                <select
                  value={rule.targetId}
                  onChange={(event) => updateRule({ ...rule, targetId: event.target.value }, index)}
                >
                  {flatFields.map((field) => (
                    <option key={field.id} value={field.id}>
                      {field.label || field.id}
                    </option>
                  ))}
                </select>
              </label>
              <button type="button" className="bf-button-danger" onClick={() => removeRule(index)}>
                Remove
              </button>
            </div>
          </li>
        ))}
      </ol>
    </section>
  );
}
