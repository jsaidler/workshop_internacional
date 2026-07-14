const STORAGE_KEY = 'controlled-visual-editor-content-v1';
const frame = document.querySelector('#preview-frame');
const clean = value => value === 'auto' ? null : value;
function apply(doc, content) {
  if (!content) return;
  doc.documentElement.toggleAttribute('data-theme', false);
  const theme = clean(content.theme || 'auto'); if (theme) doc.documentElement.setAttribute('data-theme', theme);
  doc.querySelectorAll('[data-theme-value]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.themeValue === (content.theme || 'auto'))));
  Object.entries(content.texts || {}).forEach(([key, value]) => { const el = doc.querySelector(`[data-editable-text="${key}"]`); if (el) el.innerHTML = value.html; });
  Object.entries(content.images || {}).forEach(([key, value]) => { const el = doc.querySelector(`[data-editable-image="${key}"]`); if (el) { el.src = value.src; el.alt = value.alt || ''; el.style.objectFit = value.objectFit || ''; el.style.objectPosition = value.objectPosition || ''; } });
  Object.entries(content.videos || {}).forEach(([key, value]) => { const el = doc.querySelector(`[data-editable-video="${key}"]`); if (!el) return; if (el.tagName === 'VIDEO') { el.querySelector('source')?.setAttribute('src', value.src); el.poster = value.poster || ''; el.controls = !!value.controls; el.autoplay = !!value.autoplay; el.muted = !!value.muted; el.loop = !!value.loop; el.load(); } else if (value.src) el.innerHTML = `<video ${value.controls ? 'controls' : ''} ${value.muted ? 'muted' : ''} ${value.loop ? 'loop' : ''} ${value.autoplay ? 'autoplay' : ''} playsinline poster="${value.poster || ''}"><source src="${value.src}" type="video/mp4"></video>`; });
}
frame.addEventListener('load', () => { try { apply(frame.contentDocument, JSON.parse(localStorage.getItem(STORAGE_KEY))); } catch {} });
