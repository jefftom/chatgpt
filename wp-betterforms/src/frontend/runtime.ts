import './styles.css';

declare global {
interface Window {
wpBetterFormsRuntime: {
root: string;
nonce: string;
};
}
}

function enhanceForm(form: HTMLFormElement) {
if (form.dataset.enhanced) {
return;
}

form.dataset.enhanced = 'true';
const messages = form.querySelector<HTMLElement>('.bf-messages');

form.addEventListener('submit', async (event) => {
event.preventDefault();
const honeypot = form.querySelector<HTMLInputElement>('input[name="bf_hp"]');
if (honeypot?.value) {
return;
}

const submitButton = form.querySelector<HTMLButtonElement>('button[type="submit"]');
submitButton?.setAttribute('disabled', 'true');

const formId = form.dataset.formId;
const nonce = form.dataset.nonce ?? window.wpBetterFormsRuntime?.nonce ?? '';
const data = Object.fromEntries(new FormData(form).entries());
delete (data as Record<string, unknown>).bf_hp;

try {
const response = await fetch(`${window.wpBetterFormsRuntime.root}/${formId}`, {
method: 'POST',
headers: {
'Content-Type': 'application/json',
'X-WP-Nonce': nonce,
},
body: JSON.stringify(data),
});

const body = await response.json();

if (!response.ok) {
throw body;
}

messages && (messages.textContent = body.message ?? 'Success');
messages?.classList.remove('is-error');
form.reset();
} catch (error: any) {
const fieldErrors = error?.data?.errors ?? {};
messages && (messages.textContent = error?.message ?? 'Submission failed');
messages?.classList.add('is-error');
for (const field of Array.from(form.querySelectorAll<HTMLElement>('.bf-field'))) {
const input = field.querySelector<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>('input,select,textarea');
const key = input?.name;
field.querySelector('.bf-field-error')?.remove();
if (key && fieldErrors[key]) {
const errorEl = document.createElement('div');
errorEl.className = 'bf-field-error';
errorEl.textContent = fieldErrors[key];
field.appendChild(errorEl);
}
}
} finally {
submitButton?.removeAttribute('disabled');
}
});
}

document.addEventListener('DOMContentLoaded', () => {
document.querySelectorAll<HTMLFormElement>('.bf-form').forEach(enhanceForm);
});
