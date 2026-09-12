const menuToggle = document.querySelector('.admin-menu-toggle');
const navigation = document.querySelector('#admin-navigation');
if (menuToggle && navigation) {
  menuToggle.addEventListener('click', () => {
    const open = menuToggle.getAttribute('aria-expanded') === 'true';
    menuToggle.setAttribute('aria-expanded', String(!open));
    navigation.classList.toggle('is-open', !open);
  });
}

document.querySelectorAll('[data-response-toggle]').forEach(toggle => toggle.addEventListener('click', () => { const panel=document.getElementById(toggle.getAttribute('aria-controls')); const open=toggle.getAttribute('aria-expanded')==='true'; document.querySelectorAll('[data-response-toggle]').forEach(other=>{if(other!==toggle){other.setAttribute('aria-expanded','false');document.getElementById(other.getAttribute('aria-controls')).hidden=true;}});toggle.setAttribute('aria-expanded',String(!open));panel.hidden=open;const url=new URL(location.href);open?url.searchParams.delete('response'):url.searchParams.set('response',toggle.dataset.responseId);history.replaceState({},'',url); }));
document.querySelectorAll('[data-delete-dialog-open]').forEach(open => { const dialog=open.closest('[data-response-item]').querySelector('[data-delete-dialog]'); const close=()=>{dialog.close();open.focus();};open.addEventListener('click',()=>{dialog.showModal();dialog.querySelector('[data-delete-dialog-close]')?.focus();});dialog.querySelectorAll('[data-delete-dialog-close]').forEach(button=>button.addEventListener('click',close));dialog.addEventListener('cancel',event=>{event.preventDefault();close();});dialog.querySelector('form')?.addEventListener('submit',()=>{const button=dialog.querySelector('[data-delete-submit]');button.disabled=true;button.textContent='Excluindo…';});});
