const menuToggle = document.querySelector('.admin-menu-toggle');
const navigation = document.querySelector('#admin-navigation');
const navigationBackdrop = document.querySelector('.admin-nav-backdrop');
if (menuToggle && navigation) {
  const mobileNav = window.matchMedia('(max-width: 1000px)');
  const setAdminMenu = (open, {returnFocus = false} = {}) => {
    const next = Boolean(open) && mobileNav.matches;
    menuToggle.setAttribute('aria-expanded', String(next));
    navigation.classList.toggle('is-open', next);
    document.body.classList.toggle('admin-nav-open', next);
    navigationBackdrop?.setAttribute('aria-hidden', String(!next));
    if (next) {
      requestAnimationFrame(() => (navigation.querySelector('a[aria-current="page"]') || navigation.querySelector('a,button'))?.focus());
    } else if (returnFocus || navigation.contains(document.activeElement)) {
      menuToggle.focus();
    }
  };
  menuToggle.addEventListener('click', () => setAdminMenu(menuToggle.getAttribute('aria-expanded') !== 'true', {returnFocus:true}));
  navigationBackdrop?.addEventListener('click', () => setAdminMenu(false, {returnFocus:true}));
  navigation.addEventListener('click', event => {
    if (mobileNav.matches && event.target.closest('a')) setAdminMenu(false);
  });
  document.addEventListener('keydown', event => {
    if (menuToggle.getAttribute('aria-expanded') !== 'true') return;
    if (event.key === 'Escape') {
      event.preventDefault();
      setAdminMenu(false, {returnFocus:true});
      return;
    }
    if (event.key === 'Tab') {
      const focusables=[...navigation.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])')]
        .filter(el=>el.getClientRects().length>0);
      if (!focusables.length) return;
      const first=focusables[0],last=focusables[focusables.length-1];
      if (event.shiftKey && document.activeElement===first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement===last) {
        event.preventDefault();
        first.focus();
      }
    }
  });
  mobileNav.addEventListener?.('change', event => { if (!event.matches) setAdminMenu(false); });
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

async function initStudentTestBleach() {
  if (!adminUrl.pathname.endsWith('/admin/student-area.php') || adminUrl.searchParams.get('view') !== 'tests') return;
  const testId = adminUrl.searchParams.get('test'), activity = adminUrl.searchParams.get('activity');if (!testId || !activity) return;
  const response = await fetch(`/admin/api/student-test-detail.php?activity=${encodeURIComponent(activity)}&test=${encodeURIComponent(testId)}`, {credentials:'same-origin'});if (!response.ok) return;
  const data = await response.json();
  const table = document.querySelector('.admin-section-stack .admin-card .admin-data-table tbody');if (!table || table.querySelector('[data-test-bleach]')) return;
  const row = document.createElement('tr');row.dataset.testBleach='1';row.innerHTML = `<th>Branqueador</th><td colspan="3"></td>`;row.querySelector('td').textContent = data.bleach || '—';table.append(row);
}
initStudentTestBleach().catch(console.error);
