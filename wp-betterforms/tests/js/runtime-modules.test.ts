import { describe, expect, it } from 'vitest';
import { replacePlaceholders, serializeForm } from '../../src/frontend/modules/repeater';

describe('repeater module', () => {
  it('reindexes rows sequentially after removal and keeps serialized values', () => {
    document.body.innerHTML = `
      <form id="bf-form">
        <div class="bf-repeater__rows">
          <div class="bf-repeater__row" data-bf-index="0" data-bf-template-index="{{index}}">
            <div class="bf-field bf-field--text">
              <label for="contacts-0-first" data-bf-template-for="contacts-{{index}}-first">First name</label>
              <input
                type="text"
                id="contacts-0-first"
                name="contacts[0][first]"
                value="Alice"
                data-bf-template-id="contacts-{{index}}-first"
                data-bf-template-name="contacts[{{index}}][first]"
              />
            </div>
            <div class="bf-field bf-field--email">
              <label for="contacts-0-email" data-bf-template-for="contacts-{{index}}-email">Email</label>
              <input
                type="email"
                id="contacts-0-email"
                name="contacts[0][email]"
                value="alice@example.com"
                data-bf-template-id="contacts-{{index}}-email"
                data-bf-template-name="contacts[{{index}}][email]"
              />
            </div>
            <input type="hidden" name="contacts[0][id]" value="1" />
          </div>
          <div class="bf-repeater__row" data-bf-index="1" data-bf-template-index="{{index}}">
            <div class="bf-field bf-field--text">
              <label for="contacts-1-first" data-bf-template-for="contacts-{{index}}-first">First name</label>
              <input
                type="text"
                id="contacts-1-first"
                name="contacts[1][first]"
                value="Bob"
                data-bf-template-id="contacts-{{index}}-first"
                data-bf-template-name="contacts[{{index}}][first]"
              />
            </div>
            <div class="bf-field bf-field--email">
              <label for="contacts-1-email" data-bf-template-for="contacts-{{index}}-email">Email</label>
              <input
                type="email"
                id="contacts-1-email"
                name="contacts[1][email]"
                value="bob@example.com"
                data-bf-template-id="contacts-{{index}}-email"
                data-bf-template-name="contacts[{{index}}][email]"
              />
            </div>
            <input type="hidden" name="contacts[1][id]" value="2" />
          </div>
          <div class="bf-repeater__row" data-bf-index="2" data-bf-template-index="{{index}}">
            <div class="bf-field bf-field--text">
              <label for="contacts-2-first" data-bf-template-for="contacts-{{index}}-first">First name</label>
              <input
                type="text"
                id="contacts-2-first"
                name="contacts[2][first]"
                value="Cara"
                data-bf-template-id="contacts-{{index}}-first"
                data-bf-template-name="contacts[{{index}}][first]"
              />
            </div>
            <div class="bf-field bf-field--email">
              <label for="contacts-2-email" data-bf-template-for="contacts-{{index}}-email">Email</label>
              <input
                type="email"
                id="contacts-2-email"
                name="contacts[2][email]"
                value="cara@example.com"
                data-bf-template-id="contacts-{{index}}-email"
                data-bf-template-name="contacts[{{index}}][email]"
              />
            </div>
            <input type="hidden" name="contacts[2][id]" value="3" />
          </div>
        </div>
      </form>
    `;

    const repeater = document.querySelector('.bf-repeater__rows') as HTMLElement;
    const rows = repeater.querySelectorAll<HTMLElement>('.bf-repeater__row');
    rows[1]?.remove();

    const remainingRows = repeater.querySelectorAll<HTMLElement>('.bf-repeater__row');
    remainingRows.forEach((row, index) => {
      row.dataset.bfIndex = String(index);
      replacePlaceholders(row, index);
    });

    const names = Array.from(repeater.querySelectorAll<HTMLInputElement>('input[name^="contacts"]')).map((input) => input.name);
    expect(names).toEqual([
      'contacts[0][first]',
      'contacts[0][email]',
      'contacts[0][id]',
      'contacts[1][first]',
      'contacts[1][email]',
      'contacts[1][id]',
    ]);

    const ids = Array.from(repeater.querySelectorAll<HTMLInputElement>('input[id^="contacts"]')).map((input) => input.id);
    expect(ids).toEqual([
      'contacts-0-first',
      'contacts-0-email',
      'contacts-1-first',
      'contacts-1-email',
    ]);

    const fors = Array.from(repeater.querySelectorAll<HTMLLabelElement>('label[data-bf-template-for]')).map((label) => label.htmlFor);
    expect(fors).toEqual([
      'contacts-0-first',
      'contacts-0-email',
      'contacts-1-first',
      'contacts-1-email',
    ]);

    const form = document.querySelector('form') as HTMLFormElement;
    const data = serializeForm(form);

    expect(data['contacts[0][first]']).toBe('Alice');
    expect(data['contacts[0][email]']).toBe('alice@example.com');
    expect(data['contacts[0][id]']).toBe('1');
    expect(data['contacts[1][first]']).toBe('Cara');
    expect(data['contacts[1][email]']).toBe('cara@example.com');
    expect(data['contacts[1][id]']).toBe('3');

    const contactKeys = Object.keys(data).filter((key) => key.startsWith('contacts['));
    expect(contactKeys).toHaveLength(6);
  });
});
