const menuToggle = document.querySelector('.admin-menu-toggle');
const navigation = document.querySelector('#admin-navigation');
if (menuToggle && navigation) {
  menuToggle.addEventListener('click', () => {
    const open = menuToggle.getAttribute('aria-expanded') === 'true';
    menuToggle.setAttribute('aria-expanded', String(!open));
    navigation.classList.toggle('is-open', !open);
  });
}

document.querySelectorAll('[data-dialog-open]').forEach(button => button.addEventListener('click', () => {
  const dialog = document.getElementById(button.dataset.dialogOpen);
  if (dialog?.showModal) dialog.showModal();
}));
document.querySelectorAll('[data-dialog-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog')?.close()));
document.querySelectorAll('[data-confirm]').forEach(button => button.addEventListener('click', event => {
  if (!window.confirm(button.dataset.confirm || 'Confirmar esta ação?')) event.preventDefault();
}));

document.querySelectorAll('[data-response-toggle]').forEach(toggle => toggle.addEventListener('click', () => { const panel=document.getElementById(toggle.getAttribute('aria-controls')); const open=toggle.getAttribute('aria-expanded')==='true'; document.querySelectorAll('[data-response-toggle]').forEach(other=>{if(other!==toggle){other.setAttribute('aria-expanded','false');document.getElementById(other.getAttribute('aria-controls')).hidden=true;}});toggle.setAttribute('aria-expanded',String(!open));panel.hidden=open;const url=new URL(location.href);open?url.searchParams.delete('response'):url.searchParams.set('response',toggle.dataset.responseId);history.replaceState({},'',url); }));
document.querySelectorAll('[data-delete-dialog-open]').forEach(open => { const dialog=open.closest('[data-response-item]').querySelector('[data-delete-dialog]'); const close=()=>{dialog.close();open.focus();};open.addEventListener('click',()=>{dialog.showModal();dialog.querySelector('[data-delete-dialog-close]')?.focus();});dialog.querySelectorAll('[data-delete-dialog-close]').forEach(button=>button.addEventListener('click',close));dialog.addEventListener('cancel',event=>{event.preventDefault();close();});dialog.querySelector('form')?.addEventListener('submit',()=>{const button=dialog.querySelector('[data-delete-submit]');button.disabled=true;button.textContent='Excluindo…';});});

// A importação histórica de alunos é apresentada como CSV no admin.
document.querySelectorAll('a,button,h2').forEach(element => {
  if (element.textContent.trim() === 'Importar planilha') element.textContent = 'Importar CSV';
});
document.querySelectorAll('p').forEach(element => {
  if (element.textContent.includes('A importação de planilhas também fica aqui.')) {
    element.textContent = element.textContent.replace('A importação de planilhas também fica aqui.', 'A importação por CSV também fica aqui.');
  }
});

const studentCsvInput = document.querySelector('input[name="spreadsheet"]');
if (studentCsvInput) {
  studentCsvInput.setAttribute('accept', '.csv,text/csv');
  const label = studentCsvInput.closest('label');
  if (label) [...label.childNodes].forEach(node => {if (node.nodeType === Node.TEXT_NODE && node.textContent.trim() === 'Planilha') node.textContent = 'Arquivo CSV';});
  const form = studentCsvInput.closest('form');
  if (form) {const pageUrl = new URL(window.location.href);const actionUrl = new URL('/admin/student-import-csv.php', window.location.origin);const activity = pageUrl.searchParams.get('activity');if (activity) actionUrl.searchParams.set('activity', activity);form.action = actionUrl.pathname + actionUrl.search;}
  const card = form?.closest('.admin-card');const note = form?.querySelector('.muted');if (note) note.textContent = note.textContent.replace('CSV ou XLSX', 'CSV');
  const actions = card?.querySelector('header .admin-card-actions');
  if (actions && !actions.querySelector('[data-student-csv-template]')) {const templateLink = document.createElement('a');templateLink.className = 'admin-button secondary';templateLink.href = '/assets/modelo-importacao-alunos.csv';templateLink.download = 'modelo-importacao-alunos.csv';templateLink.dataset.studentCsvTemplate = '1';templateLink.textContent = 'Baixar modelo CSV';actions.prepend(templateLink);}
  if (form && !form.querySelector('[data-student-csv-example]')) {const example = document.createElement('p');example.className = 'muted admin-form-span-2';example.dataset.studentCsvExample = '1';const strong = document.createElement('strong');strong.textContent = 'Exemplo de linha: ';const code = document.createElement('code');code.textContent = 'Maria da Silva;maria@email.com;12345678900;(24) 99999-9999;@maria;Rua Exemplo, 123;Petrópolis/RJ;25600-000';example.append(strong, code);form.querySelector('.admin-form-actions')?.before(example);}
  const validateCsv = event => {const file = studentCsvInput.files?.[0];if (file && !file.name.toLowerCase().endsWith('.csv')) {event?.preventDefault();window.alert('Envie um arquivo CSV.');studentCsvInput.value = '';studentCsvInput.focus();return false;}return true;};
  studentCsvInput.addEventListener('change', event => validateCsv(event));form?.addEventListener('submit', event => validateCsv(event));
}

// Páginas protegidas não têm biblioteca própria. Os slots apontam para assets
// privados da Biblioteca de mídia canônica.
const privateMediaHeading=[...document.querySelectorAll('.admin-card h2')].find(el=>el.textContent.trim()==='Imagens privadas');
if(privateMediaHeading){
  const card=privateMediaHeading.closest('.admin-card');
  const legacyForm=card?.querySelector('form');
  const pageId=Number(legacyForm?.querySelector('input[name="page_id"]')?.value||new URL(location.href).searchParams.get('page')||0);
  const activity=new URL(location.href).searchParams.get('activity')||'';
  const esc=value=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
  async function loadProtectedMediaSlots(){
    if(!card||!pageId)return;
    card.innerHTML='<header><div><h2>Mídia protegida</h2><p>Os espaços desta página usam arquivos privados da Biblioteca de mídia.</p></div></header><p class="muted">Carregando…</p>';
    try{
      const url=`/admin/api/protected-media-slots.php?activity=${encodeURIComponent(activity)}&page=${pageId}`;const response=await fetch(url,{credentials:'same-origin'});const data=await response.json();if(!response.ok)throw new Error(data.error||'Falha ao carregar');
      const assetOptions=data.assets.map(asset=>`<option value="${asset.id}">${esc(asset.title)} · ${esc(asset.original_name)}</option>`).join('');
      const rows=data.slots.map(slot=>`<tr><td><strong>${esc(slot.label)}</strong><div class="muted">${esc(slot.key)}</div></td><td>${slot.binding?esc(slot.binding.title):'<span class="muted">Nenhuma mídia vinculada</span>'}</td><td><form class="admin-inline-actions" data-protected-slot="${esc(slot.key)}"><select name="assetId"><option value="">Selecionar mídia privada</option>${assetOptions}</select><button class="admin-button secondary" type="submit">Vincular</button>${slot.binding?'<button class="admin-button secondary" type="button" data-unbind>Remover</button>':''}</form></td></tr>`).join('');
      card.innerHTML=`<header><div><h2>Mídia protegida</h2><p>Vincule cada espaço a uma imagem marcada como privada na Biblioteca. O envio e a privacidade são administrados em um único lugar.</p></div><div class="admin-card-actions"><a class="admin-button secondary" href="${esc(data.mediaUrl)}">Abrir Biblioteca</a></div></header>${!data.slots.length?'<div class="admin-empty">Esta página não possui espaços de mídia protegida.</div>':`<div class="admin-table-scroll"><table class="admin-data-table"><thead><tr><th>Espaço</th><th>Mídia atual</th><th>Ação</th></tr></thead><tbody>${rows}</tbody></table></div>`}${data.slots.length&&!data.assets.length?'<p class="muted">Não há imagens privadas na Biblioteca. Abra a Biblioteca, envie ou abra uma imagem e altere Acesso para Privada.</p>':''}`;
      card.querySelectorAll('[data-protected-slot]').forEach(form=>{
        form.addEventListener('submit',async event=>{event.preventDefault();const assetId=Number(new FormData(form).get('assetId')||0);if(!assetId)return;await saveSlot(form.dataset.protectedSlot,'bind',assetId,data.csrf);});
        form.querySelector('[data-unbind]')?.addEventListener('click',()=>saveSlot(form.dataset.protectedSlot,'unbind',0,data.csrf));
      });
    }catch(error){card.innerHTML=`<header><div><h2>Mídia protegida</h2></div></header><div class="admin-empty">${esc(error.message)}</div>`;}
  }
  async function saveSlot(slot,action,assetId,csrf){const response=await fetch(`/admin/api/protected-media-slots.php?activity=${encodeURIComponent(activity)}&page=${pageId}`,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({page:pageId,slot,action,assetId,csrf})});const data=await response.json();if(!response.ok){window.alert(data.error||'Falha ao salvar vínculo');return;}loadProtectedMediaSlots();}
  loadProtectedMediaSlots();
}
