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

// Estrutura, acesso e mídia de páginas pertencem ao CMS/editor. A Área do aluno
// administra somente turmas, usuários, testes e aulas.
const adminUrl = new URL(window.location.href);
if (adminUrl.pathname.endsWith('/admin/student-area.php')) {
  document.querySelectorAll('.admin-subtabs a').forEach(link => { if (link.textContent.trim() === 'Páginas protegidas') link.remove(); });
  document.querySelectorAll('.overview-card').forEach(card => { if (card.querySelector('h2')?.textContent.trim() === 'Páginas protegidas') card.remove(); });
  if (adminUrl.searchParams.get('view') === 'pages') {
    const activity = adminUrl.searchParams.get('activity');
    window.location.replace('/admin/pages.php' + (activity ? '?activity=' + encodeURIComponent(activity) : ''));
  }
}

function lessonStateLabel(state, localValue) {
  if (state === 'released') return localValue ? `Liberada · ${localValue.replace('T',' ')}` : 'Liberada';
  if (state === 'scheduled') return `Agendada · ${localValue.replace('T',' ')}`;
  return 'Bloqueada';
}
async function initLessonScheduling() {
  if (!adminUrl.pathname.endsWith('/admin/student-area.php') || adminUrl.searchParams.get('view') !== 'lessons') return;
  const activity = adminUrl.searchParams.get('activity') || '';
  const releaseForms = [...document.querySelectorAll('form input[name="action"][value="set_release"]')].map(input => input.form).filter(Boolean);
  if (!releaseForms.length) return;
  const response = await fetch('/admin/api/course-lesson-release.php?activity=' + encodeURIComponent(activity), {credentials:'same-origin'});
  if (!response.ok) return;
  const payload = await response.json();
  const map = new Map((payload.items || []).map(item => [`${item.cohortId}:${item.lessonId}`, item]));
  for (const form of releaseForms) {
    const cohortId = form.querySelector('[name="cohort_id"]')?.value;
    const lessonId = form.querySelector('[name="lesson_id"]')?.value;
    const csrf = form.querySelector('[name="_csrf"]')?.value || '';
    if (!cohortId || !lessonId) continue;
    const item = map.get(`${cohortId}:${lessonId}`) || {state:'blocked',localValue:''};
    const row = form.closest('tr'), stateCell = row?.children?.[2], actionCell = form.closest('td');
    if (!actionCell) continue;
    form.hidden = true;
    if (stateCell) stateCell.textContent = lessonStateLabel(item.state, item.localValue || '');
    const controls = document.createElement('div');
    controls.className = 'admin-inline-actions';
    controls.innerHTML = `<input type="datetime-local" aria-label="Data e hora da liberação" value="${item.localValue || ''}"><button class="admin-button secondary" type="button" data-mode="schedule">Agendar</button><button class="admin-button secondary" type="button" data-mode="release">Liberar agora</button><button class="admin-button secondary" type="button" data-mode="block">Bloquear</button>`;
    actionCell.append(controls);
    const input = controls.querySelector('input');
    async function save(mode) {
      const data = new FormData();data.append('_csrf', csrf);data.append('activity', activity);data.append('cohort_id', cohortId);data.append('lesson_id', lessonId);data.append('mode', mode);data.append('scheduled_at', input.value);
      controls.querySelectorAll('button').forEach(b => b.disabled = true);
      try {
        const r = await fetch('/admin/api/course-lesson-release.php', {method:'POST',credentials:'same-origin',body:data});
        const body = await r.json();if (!r.ok) throw new Error(body?.error?.message || 'Não foi possível salvar a liberação.');
        input.value = body.localValue || '';
        if (stateCell) stateCell.textContent = lessonStateLabel(body.state, body.localValue || '');
      } catch (error) { window.alert(error.message); }
      finally { controls.querySelectorAll('button').forEach(b => b.disabled = false); }
    }
    controls.querySelectorAll('button[data-mode]').forEach(button => button.addEventListener('click', () => save(button.dataset.mode)));
  }
}
initLessonScheduling().catch(console.error);
