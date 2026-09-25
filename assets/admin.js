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
// O parser legado continua tolerante a formatos antigos, mas a interface canônica
// oferece um único formato previsível e um modelo pronto para preenchimento.
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
  if (label) {
    [...label.childNodes].forEach(node => {
      if (node.nodeType === Node.TEXT_NODE && node.textContent.trim() === 'Planilha') node.textContent = 'Arquivo CSV';
    });
  }

  const form = studentCsvInput.closest('form');
  if (form) {
    const pageUrl = new URL(window.location.href);
    const actionUrl = new URL('/admin/student-import-csv.php', window.location.origin);
    const activity = pageUrl.searchParams.get('activity');
    if (activity) actionUrl.searchParams.set('activity', activity);
    form.action = actionUrl.pathname + actionUrl.search;
  }

  const card = form?.closest('.admin-card');
  const note = form?.querySelector('.muted');
  if (note) note.textContent = note.textContent.replace('CSV ou XLSX', 'CSV');

  const actions = card?.querySelector('header .admin-card-actions');
  if (actions && !actions.querySelector('[data-student-csv-template]')) {
    const templateLink = document.createElement('a');
    templateLink.className = 'admin-button secondary';
    templateLink.href = '/assets/modelo-importacao-alunos.csv';
    templateLink.download = 'modelo-importacao-alunos.csv';
    templateLink.dataset.studentCsvTemplate = '1';
    templateLink.textContent = 'Baixar modelo CSV';
    actions.prepend(templateLink);
  }

  if (form && !form.querySelector('[data-student-csv-example]')) {
    const example = document.createElement('p');
    example.className = 'muted admin-form-span-2';
    example.dataset.studentCsvExample = '1';
    const strong = document.createElement('strong');
    strong.textContent = 'Exemplo de linha: ';
    const code = document.createElement('code');
    code.textContent = 'Maria da Silva;maria@email.com;12345678900;(24) 99999-9999;@maria;Rua Exemplo, 123;Petrópolis/RJ;25600-000';
    example.append(strong, code);
    const formActions = form.querySelector('.admin-form-actions');
    formActions?.before(example);
  }

  const validateCsv = event => {
    const file = studentCsvInput.files?.[0];
    if (file && !file.name.toLowerCase().endsWith('.csv')) {
      event?.preventDefault();
      window.alert('Envie um arquivo CSV.');
      studentCsvInput.value = '';
      studentCsvInput.focus();
      return false;
    }
    return true;
  };
  studentCsvInput.addEventListener('change', event => validateCsv(event));
  form?.addEventListener('submit', event => validateCsv(event));
}
