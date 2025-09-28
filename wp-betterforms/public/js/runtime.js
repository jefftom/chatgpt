(function(){
const forms = new Map();

function initForm(form){
if(forms.has(form)){
return;
}

forms.set(form,true);
const messageEl=form.querySelector('.bf-messages');
form.addEventListener('submit',async(event)=>{
event.preventDefault();
const honeypot=form.querySelector('input[name="bf_hp"]');
if(honeypot && honeypot.value){
return;
}

const submitButton=form.querySelector('button[type="submit"]');
if(submitButton){
submitButton.disabled=true;
}

const formId=form.getAttribute('data-form-id');
const nonce=form.getAttribute('data-nonce');
const data=Object.fromEntries(new FormData(form).entries());
delete data.bf_hp;

try{
const response=await fetch(`${window.wpBetterFormsRuntime.root}/${formId}`,{
method:'POST',
headers:{
'Content-Type':'application/json',
'X-WP-Nonce':nonce||''
},
body:JSON.stringify(data)
});
const result=await response.json();

if(!response.ok){
throw result;
}

messageEl.textContent=result.message||'Success';
messageEl.classList.remove('is-error');
form.reset();
}catch(error){
const errors=error?.data?.errors||{};
messageEl.textContent=error?.message||'Submission failed';
messageEl.classList.add('is-error');
for(const field of form.querySelectorAll('.bf-field')){
const input=field.querySelector('input,select,textarea');
const key=input?.name;
let errorEl=field.querySelector('.bf-field-error');
if(errorEl){
errorEl.remove();
}
if(key && errors[key]){
errorEl=document.createElement('div');
errorEl.className='bf-field-error';
errorEl.textContent=errors[key];
field.appendChild(errorEl);
}
}
}finally{
if(submitButton){
submitButton.disabled=false;
}
}
});
}

document.addEventListener('DOMContentLoaded',()=>{
document.querySelectorAll('.bf-form').forEach(initForm);
});

})();
