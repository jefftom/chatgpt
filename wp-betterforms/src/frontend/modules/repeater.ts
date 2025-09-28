export type SerializedForm = Record<string, FormDataEntryValue | FormDataEntryValue[]>;

const ATTRIBUTE_CONFIG = [
  { attr: 'id', datasetKey: 'bfTemplateId' as const },
  { attr: 'for', datasetKey: 'bfTemplateFor' as const },
  { attr: 'name', datasetKey: 'bfTemplateName' as const },
];

const INDEX_PATTERNS = [
  /\{\{\s*index\s*\}\}/gi,
  /__INDEX__/gi,
  /%index%/gi,
];

const BRACKETED_INDEX = /\[(\d+)\]/g;

function applyIndex(template: string, index: number): string {
  let candidate = template;

  for (const pattern of INDEX_PATTERNS) {
    const replaced = candidate.replace(pattern, String(index));
    if (replaced !== candidate) {
      candidate = replaced;
    }
  }

  if (candidate !== template) {
    return candidate;
  }

  let replaced = false;
  return template.replace(BRACKETED_INDEX, (match) => {
    if (replaced) {
      return match;
    }

    replaced = true;
    return `[${index}]`;
  });
}

function getTemplate(
  element: HTMLElement,
  attr: string,
  datasetKey: (typeof ATTRIBUTE_CONFIG)[number]['datasetKey'],
): string | null {
  const datasetValue = element.dataset[datasetKey];
  if (datasetValue !== undefined) {
    return datasetValue;
  }

  const inlineTemplate = element.dataset.bfTemplate;
  if (inlineTemplate) {
    return inlineTemplate;
  }

  const current = element.getAttribute(attr);
  if (!current) {
    return null;
  }

  if (current.includes('[') || /\d/.test(current)) {
    element.dataset[datasetKey] = current;
  }

  return current;
}

export function replacePlaceholders(root: HTMLElement, index: number): void {
  const elements = [root, ...Array.from(root.querySelectorAll<HTMLElement>('*'))];

  for (const element of elements) {
    for (const { attr, datasetKey } of ATTRIBUTE_CONFIG) {
      const template = getTemplate(element, attr, datasetKey);
      if (!template) {
        continue;
      }

      const value = applyIndex(template, index);
      if (value === element.getAttribute(attr)) {
        continue;
      }

      if (attr === 'for') {
        if (value) {
          (element as HTMLLabelElement).htmlFor = value;
        } else {
          (element as HTMLLabelElement).removeAttribute('for');
        }
      } else if (value) {
        element.setAttribute(attr, value);
      } else {
        element.removeAttribute(attr);
      }
    }
  }
}

export function serializeForm(form: HTMLFormElement): SerializedForm {
  const data = new FormData(form);
  const result: SerializedForm = {};

  for (const [name, value] of data.entries()) {
    if (name in result) {
      const existing = result[name];
      if (Array.isArray(existing)) {
        existing.push(value);
      } else {
        result[name] = [existing, value];
      }
    } else {
      result[name] = value;
    }
  }

  return result;
}
