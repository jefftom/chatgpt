import './styles.css';
import { createRoot } from 'react-dom/client';
import { StrictMode, useEffect, useState } from 'react';

type FormSummary = {
id: number;
title: string;
slug: string;
status: string;
updated_at: string;
};

type FormResponse = {
forms: FormSummary[];
};

type BuilderForm = {
id?: number;
title: string;
slug: string;
schema: Record<string, unknown>;
styles: Record<string, unknown>;
status: string;
};

const apiBase = (window as any).wpBetterFormsConfig?.restBase ?? '';
const nonce = (window as any).wpBetterFormsConfig?.nonce ?? '';

function useForms() {
const [forms, setForms] = useState<FormSummary[]>([]);
const [loading, setLoading] = useState(false);
const [error, setError] = useState<string | null>(null);

const fetchForms = async () => {
setLoading(true);
try {
const res = await fetch(apiBase, {
headers: {
'X-WP-Nonce': nonce,
},
});
if (!res.ok) {
throw new Error('Failed to fetch forms');
}
const data = (await res.json()) as FormResponse;
setForms(data.forms);
} catch (err) {
setError((err as Error).message);
} finally {
setLoading(false);
}
};

useEffect(() => {
fetchForms();
}, []);

return { forms, loading, error, refresh: fetchForms };
}

function FormList({ onCreate }: { onCreate: () => void }) {
const { forms, loading, error, refresh } = useForms();

return (
<section className="bf-admin-panel">
<header className="bf-admin-panel__header">
<h1>WP Better Forms</h1>
<button onClick={onCreate} className="bf-button">New form</button>
</header>
{loading && <p>Loading…</p>}
{error && <p className="bf-error">{error}</p>}
<ul className="bf-form-list">
{forms.map((form) => (
<li key={form.id}>
<strong>{form.title}</strong>
<span>{form.status}</span>
<time dateTime={form.updated_at}>{form.updated_at}</time>
</li>
))}
</ul>
</section>
);
}

function Builder({ form, onSave }: { form: BuilderForm; onSave: (form: BuilderForm) => void }) {
const [draft, setDraft] = useState<BuilderForm>(form);
const [saving, setSaving] = useState(false);
const [message, setMessage] = useState<string | null>(null);

const handleChange = (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
setDraft({ ...draft, [event.target.name]: event.target.value });
};

const save = async () => {
setSaving(true);
setMessage(null);
try {
const res = await fetch(apiBase, {
method: 'POST',
headers: {
'Content-Type': 'application/json',
'X-WP-Nonce': nonce,
},
body: JSON.stringify(draft),
});
if (!res.ok) {
throw new Error('Unable to save form');
}
const data = await res.json();
onSave(data.form);
setMessage('Form saved successfully.');
} catch (err) {
setMessage((err as Error).message);
} finally {
setSaving(false);
}
};

return (
<div className="bf-builder">
<header>
<input
name="title"
value={draft.title}
onChange={handleChange}
placeholder="Form title"
aria-label="Form title"
/>
<textarea
name="slug"
value={draft.slug}
onChange={handleChange}
placeholder="Slug"
aria-label="Form slug"
/>
</header>
<div className="bf-builder__actions">
<button className="bf-button" disabled={saving} onClick={save}>
{saving ? 'Saving…' : 'Save form'}
</button>
</div>
{message && <p role="status">{message}</p>}
<section className="bf-builder__placeholder" aria-live="polite">
<p>Drag and drop fields, configure conditional logic, and design styles here. This lightweight shell ensures the PHP integration works end-to-end.</p>
</section>
</div>
);
}

function App() {
const [currentForm, setCurrentForm] = useState<BuilderForm | null>(null);

return (
<main className="bf-admin">
{currentForm ? (
<Builder form={currentForm} onSave={(form) => setCurrentForm(form)} />
) : (
<FormList onCreate={() => setCurrentForm({ title: 'Untitled form', slug: 'untitled-form', schema: { fields: [] }, styles: { tokens: {} }, status: 'draft' })} />
)}
</main>
);
}

const rootElement = document.getElementById('wp-betterforms-admin');
if (rootElement) {
createRoot(rootElement).render(
<StrictMode>
<App />
</StrictMode>
);
}
