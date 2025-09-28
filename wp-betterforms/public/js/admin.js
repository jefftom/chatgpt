(function () {
const { createElement: h, useEffect, useState, StrictMode, createRoot } = window.wp.element;

const apiBase = window.wpBetterFormsConfig?.restBase ?? '';
const nonce = window.wpBetterFormsConfig?.nonce ?? '';

function useForms() {
const [forms, setForms] = useState([]);
const [loading, setLoading] = useState(false);
const [error, setError] = useState(null);

const fetchForms = async () => {
setLoading(true);
setError(null);
try {
const response = await fetch(apiBase, {
headers: { 'X-WP-Nonce': nonce },
});
if (!response.ok) {
throw new Error('Unable to load forms');
}
const payload = await response.json();
setForms(payload.forms ?? []);
} catch (err) {
setError(err.message);
} finally {
setLoading(false);
}
};

useEffect(() => {
fetchForms();
}, []);

return { forms, loading, error, refresh: fetchForms };
}

function FormList({ onCreate }) {
const { forms, loading, error } = useForms();

return h(
'section',
{ className: 'bf-admin-panel' },
h(
'header',
{ className: 'bf-admin-panel__header' },
h('h1', null, 'WP Better Forms'),
h(
'button',
{ className: 'bf-button', onClick: onCreate },
'New form'
)
),
loading && h('p', null, 'Loading…'),
error && h('p', { className: 'bf-error', role: 'alert' }, error),
h(
'ul',
{ className: 'bf-form-list' },
forms.map(function (form) {
return h(
'li',
{ key: form.id },
h('strong', null, form.title),
h('span', null, form.status),
h('time', { dateTime: form.updated_at }, form.updated_at)
);
})
)
);
}

function Builder({ form, onSave }) {
const [draft, setDraft] = useState(form);
const [saving, setSaving] = useState(false);
const [message, setMessage] = useState(null);

const onChange = (event) => {
setDraft({ ...draft, [event.target.name]: event.target.value });
};

const save = async () => {
setSaving(true);
setMessage(null);
try {
const response = await fetch(apiBase, {
method: 'POST',
headers: {
'Content-Type': 'application/json',
'X-WP-Nonce': nonce,
},
body: JSON.stringify(draft),
});
if (!response.ok) {
throw new Error('Unable to save form');
}
const payload = await response.json();
onSave(payload.form);
setMessage('Form saved successfully.');
} catch (err) {
setMessage(err.message);
} finally {
setSaving(false);
}
};

return h(
'div',
{ className: 'bf-builder' },
h(
'header',
null,
h('input', {
type: 'text',
name: 'title',
value: draft.title,
onChange,
placeholder: 'Form title',
'aria-label': 'Form title',
}),
h('textarea', {
name: 'slug',
value: draft.slug,
onChange,
placeholder: 'Slug',
'aria-label': 'Form slug',
})
),
h(
'div',
{ className: 'bf-builder__actions' },
h(
'button',
{ className: 'bf-button', disabled: saving, onClick: save },
saving ? 'Saving…' : 'Save form'
)
),
message && h('p', { role: 'status' }, message),
h(
'section',
{ className: 'bf-builder__placeholder', 'aria-live': 'polite' },
h(
'p',
null,
'Drag and drop fields, configure conditional logic, and design styles here. This lightweight shell ensures the PHP integration works end-to-end.'
)
)
);
}

function App() {
const [currentForm, setCurrentForm] = useState(null);

return h(
'main',
{ className: 'bf-admin' },
currentForm
? h(Builder, {
form: currentForm,
onSave: (form) => setCurrentForm(form),
})
: h(FormList, {
onCreate: () =>
setCurrentForm({
title: 'Untitled form',
slug: 'untitled-form',
schema: { fields: [] },
styles: { tokens: {} },
status: 'draft',
}),
})
);
}

const mountNode = document.getElementById('wp-betterforms-admin');
if (mountNode && typeof createRoot === 'function') {
createRoot(mountNode).render(h(StrictMode, null, h(App)));
}
})();
