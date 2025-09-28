export type FieldType =
  | 'text'
  | 'textarea'
  | 'email'
  | 'number'
  | 'checkbox'
  | 'select'
  | 'repeater';

export type FieldOption = {
  id: string;
  label: string;
};

export interface FormField {
  id: string;
  type: FieldType;
  label: string;
  required?: boolean;
  placeholder?: string;
  helpText?: string;
  options?: FieldOption[];
  children?: FormField[];
}

export interface IntegrationSettings {
  emailNotifications: {
    enabled: boolean;
    recipients: string;
  };
  webhook: {
    enabled: boolean;
    url: string;
  };
}

export type LogicOperator = 'equals' | 'not_equals' | 'contains' | 'greater_than' | 'less_than';

export type LogicActionType = 'show' | 'hide' | 'enable' | 'disable';

export interface LogicRule {
  id: string;
  fieldId: string;
  operator: LogicOperator;
  value: string;
  action: LogicActionType;
  targetId: string;
}

export interface FormLogic {
  rules: LogicRule[];
}

export interface StylerTokens {
  preset: StylerPreset;
  global: Record<string, string>;
  form: Record<string, string>;
}

export type StylerPreset =
  | 'minimal'
  | 'outlined'
  | 'filled'
  | 'underline'
  | 'compact'
  | 'spacious'
  | 'dark-auto';

export interface FormSchema {
  fields: FormField[];
  logic: FormLogic;
  integrations: IntegrationSettings;
}

export interface BuilderForm {
  id?: number;
  title: string;
  slug: string;
  status: string;
  schema: FormSchema;
  styles: StylerTokens;
}

export interface FormSummary {
  id: number;
  title: string;
  slug: string;
  status: string;
  updated_at: string;
}

export interface FormResponse {
  forms: FormSummary[];
}

export interface SingleFormResponse {
  form: BuilderForm;
}
