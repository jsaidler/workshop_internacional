const STORAGE_KEY = 'controlled-visual-editor-content-v1';
const frame = document.querySelector('#page-frame');
const properties = document.querySelector('#properties');
const modal = document.querySelector('#media-modal');
const mediaGrid = document.querySelector('#media-grid');
const toast = document.querySelector('#toast');
let content, selected = null, history = [], historyIndex = -1, editing = null;

const clone = value => JSON.parse(JSON.stringify(value));
const keyFor = (type, element) => element.dataset[`editable${type}`];
const selectorFor = (type, key) => `[data-editable-${type.toLowerCase()}="${CSS.escape(key)}"]`;

function announce(message) { toast.textContent = message; toast.classList.add('show'); clearTimeout(announce.timer); announce.timer = setTimeout(() => toast.classList.remove('show'), 1800); }
function saveLocal(message = 'Conteúdo salvo no navegador.') { localStorage.setItem(STORAGE_KEY, JSON.stringify(content)); announce(message); }
function pushHistory() { history = history.slice(0, historyIndex + 1); history.push(clone(content)); historyIndex = history.length - 1; updateHistoryControls(); }
function updateHistoryControls() { document.querySelector('#undo').disabled = historyIndex < 1; document.querySelector('#redo').disabled = historyIndex >= history.length - 1; }

async function loadDefault() { const response = await fetch('../data/default-content.json'); return response.json(); }
function setTheme(theme) {
  content.theme = theme;
  const doc = frame.contentDocument;
  doc.documentElement.removeAttribute('data-theme');
  if (theme !== 'auto') doc.documentElement.setAttribute('data-theme', theme);
  doc.querySelectorAll('[data-theme-value]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.themeValue === theme)));
  document.querySelectorAll('[data-editor-theme]').forEach(button => button.classList.toggle('active', button.dataset.editorTheme === theme));
}
function applyContent() {
  const doc = frame.contentDocument; if (!doc || !content) return;
  setTheme(content.theme || 'auto');
  Object.entries(content.texts || {}).forEach(([key, value]) => { const element = doc.querySelector(selectorFor('Text', key)); if (element && element !== editing) element.innerHTML = value.html; });
  Object.entries(content.images || {}).forEach(([key, value]) => applyImage(doc.querySelector(selectorFor('Image', key)), value));
  Object.entries(content.videos || {}).forEach(([key, value]) => applyVideo(doc.querySelector(selectorFor('Video', key)), value));
  window.EditorStage2?.apply(doc, content);
}
function applyImage(element, value) { if (!element) return; element.src = value.src; element.alt = value.alt || ''; element.style.objectFit = value.objectFit || ''; element.style.objectPosition = value.objectPosition || ''; }
function applyVideo(element, value) {
  if (!element) return;
  if (element.tagName === 'VIDEO') {
    const source = element.querySelector('source'); if (source) source.src = value.src || '';
    element.poster = value.poster || ''; element.controls = !!value.controls; element.autoplay = false; element.muted = !!value.muted; element.loop = !!value.loop; element.pause(); element.load();
  } else if (value.src) {
    element.innerHTML = `<video ${value.controls ? 'controls' : ''} ${value.muted ? 'muted' : ''} ${value.loop ? 'loop' : ''} playsinline poster="${escapeAttribute(value.poster || '')}"><source src="${escapeAttribute(value.src)}" type="video/mp4"></video>`;
  }
}
function escapeAttribute(value) { return String(value).replaceAll('&', '&amp;').replaceAll('"', '&quot;').replaceAll('<', '&lt;'); }
function sanitize(html) {
  const allowed = new Set(['BR', 'STRONG', 'B', 'EM', 'I', 'A', 'SPAN']);
  const safe = new DOMParser().parseFromString(`<div>${html}</div>`, 'text/html').body.firstElementChild;
  function walk(node) {
    [...node.childNodes].forEach(child => {
      if (child.nodeType === Node.TEXT_NODE) return;
      if (child.nodeType !== Node.ELEMENT_NODE) { child.remove(); return; }
      if (!allowed.has(child.tagName)) { child.replaceWith(...child.childNodes); return; }
      [...child.attributes].forEach(attribute => {
        const keepHref = child.tagName === 'A' && attribute.name === 'href' && /^(https?:|mailto:|#|\/)/i.test(attribute.value);
        if (!keepHref) child.removeAttribute(attribute.name);
      });
      if (child.tagName === 'A') { child.setAttribute('rel', 'noopener noreferrer'); child.setAttribute('target', '_blank'); }
      walk(child);
    });
  }
  walk(safe); return safe.innerHTML;
}
function select(type, element) { finishEditing(); selected = { type, key: keyFor(type, element), element }; renderProperties(); }
function finishEditing() {
  if (!editing) return;
  const element = editing; editing = null;
  const key = element.dataset.editableText; const next = sanitize(element.innerHTML);
  element.innerHTML = next; element.contentEditable = 'false'; element.style.outline = ''; element.style.outlineOffset = '';
  if (content.texts[key].html !== next) { content.texts[key].html = next; pushHistory(); announce('Texto atualizado.'); }
}
function enableTextEditing(element) {
  if (editing && editing !== element) finishEditing(); editing = element; element.contentEditable = 'true'; element.style.outline = '2px solid #186f4d'; element.style.outlineOffset = '4px'; element.focus();
  const range = element.ownerDocument.createRange(); range.selectNodeContents(element); range.collapse(false); const selection = element.ownerDocument.getSelection(); selection.removeAllRanges(); selection.addRange(range);
}
function hookFrame() {
  const doc = frame.contentDocument;
  doc.addEventListener('click', event => {
    const element = event.target.closest('[data-editable-image], [data-editable-video], [data-editable-text]'); if (!element) { finishEditing(); selected = null; renderProperties(); return; }
    const type = element.hasAttribute('data-editable-image') ? 'Image' : element.hasAttribute('data-editable-video') ? 'Video' : 'Text';
    if (type !== 'Text') event.preventDefault(); select(type, element);
  });
  doc.addEventListener('dblclick', event => { const element = event.target.closest('[data-editable-text]'); if (!element) return; event.preventDefault(); select('Text', element); enableTextEditing(element); });
  doc.addEventListener('focusout', event => { if (event.target === editing) finishEditing(); });
  doc.addEventListener('keydown', event => { if (event.ctrlKey && event.key === 'Enter' && editing) { event.preventDefault(); finishEditing(); } });
  doc.querySelectorAll('a,button,[data-editable-video]').forEach(element => element.addEventListener('click', event => { if (new URLSearchParams(location.search).has('editor')) event.preventDefault(); }));
}
function renderProperties() {
  if (!selected) { properties.innerHTML = '<div class="empty-state">Selecione um texto, uma imagem ou um vídeo na página.</div>'; return; }
  const label = selected.key.replaceAll('-', ' ');
  if (selected.type === 'Text') properties.innerHTML = `<p class="eyebrow">Texto editável</p><h2>${label}</h2><div class="property-block"><p>Edite diretamente na página com dois cliques.</p><p class="eyebrow">Ctrl + Enter salva a edição</p></div>`;
  if (selected.type === 'Image') renderImageProperties(label);
  if (selected.type === 'Video') renderVideoProperties(label);
}
function renderImageProperties(label) {
  const value = content.images[selected.key];
  properties.innerHTML = `<p class="eyebrow">Imagem editável</p><h2>${label}</h2><div class="property-block"><button id="choose-image">Escolher imagem</button></div><div class="property-block"><label>Texto alternativo<input id="image-alt" value="${escapeAttribute(value.alt || '')}"></label><label>Object fit<select id="image-fit">${['cover','contain','fill','none','scale-down'].map(item => `<option ${item === value.objectFit ? 'selected' : ''}>${item}</option>`).join('')}</select></label><label>Posição horizontal<input id="image-x" type="range" min="0" max="100" value="${positionPart(value.objectPosition, 0)}"></label><label>Posição vertical<input id="image-y" type="range" min="0" max="100" value="${positionPart(value.objectPosition, 1)}"></label><button class="danger" id="remove-image">Remover imagem</button></div>`;
  properties.querySelector('#choose-image').onclick = () => openMedia('image');
  properties.querySelector('#remove-image').onclick = () => { value.src = ''; value.alt = ''; applyImage(selected.element, value); pushHistory(); renderProperties(); };
  ['#image-alt','#image-fit','#image-x','#image-y'].forEach(selector => properties.querySelector(selector).addEventListener('change', () => { value.alt = properties.querySelector('#image-alt').value; value.objectFit = properties.querySelector('#image-fit').value; value.objectPosition = `${properties.querySelector('#image-x').value}% ${properties.querySelector('#image-y').value}%`; applyImage(selected.element, value); pushHistory(); }));
}
function positionPart(position, index) { return Number.parseInt((position || '50% 50%').split(' ')[index], 10) || 50; }
function renderVideoProperties(label) {
  const value = content.videos[selected.key];
  properties.innerHTML = `<p class="eyebrow">Vídeo editável</p><h2>${label}</h2><div class="property-block"><button id="choose-video">Escolher vídeo</button><button id="choose-poster" style="margin-top:8px">Escolher poster</button></div><div class="property-block">${['controls','autoplay','muted','loop'].map(key => `<label class="check"><input data-video-option="${key}" type="checkbox" ${value[key] ? 'checked' : ''}>${key}</label>`).join('')}</div>`;
  properties.querySelector('#choose-video').onclick = () => openMedia('video'); properties.querySelector('#choose-poster').onclick = () => openMedia('poster');
  properties.querySelectorAll('[data-video-option]').forEach(input => input.onchange = () => { value[input.dataset.videoOption] = input.checked; applyVideo(selected.element, value); pushHistory(); });
}
function openMedia(kind) {
  const items = kind === 'video' ? MediaLibrary.videos : MediaLibrary.images;
  document.querySelector('#media-title').textContent = kind === 'video' ? 'Escolher vídeo' : kind === 'poster' ? 'Escolher poster' : 'Escolher imagem';
  mediaGrid.innerHTML = items.map((item, index) => `<button class="media-item" data-index="${index}">${kind === 'video' ? `<video muted preload="metadata" src="${item.src}"></video>` : `<img src="${item.src}" alt="">`}<span>${item.label}</span></button>`).join('');
  mediaGrid.querySelectorAll('.media-item').forEach(button => button.onclick = () => { const item = items[Number(button.dataset.index)]; if (kind === 'image') { content.images[selected.key].src = item.src; applyImage(selected.element, content.images[selected.key]); } else if (kind === 'poster') { content.videos[selected.key].poster = item.src; applyVideo(selected.element, content.videos[selected.key]); } else { Object.assign(content.videos[selected.key], { src: item.src, poster: item.poster || content.videos[selected.key].poster }); applyVideo(selected.element, content.videos[selected.key]); } pushHistory(); closeMedia(); renderProperties(); });
  modal.hidden = false;
}
function closeMedia() { modal.hidden = true; }
function restoreSaved() { const saved = localStorage.getItem(STORAGE_KEY); if (!saved) { announce('Não há conteúdo salvo ainda.'); return; } try { content = JSON.parse(saved); applyContent(); pushHistory(); renderProperties(); announce('Conteúdo restaurado.'); } catch { announce('O conteúdo salvo é inválido.'); } }

document.querySelectorAll('[data-viewport]').forEach(button => button.onclick = () => { document.querySelectorAll('[data-viewport]').forEach(item => item.classList.toggle('active', item === button)); document.querySelector('.frame-shell').className = `frame-shell ${button.dataset.viewport === 'desktop' ? '' : button.dataset.viewport}`; });
document.querySelectorAll('[data-editor-theme]').forEach(button => button.onclick = () => { setTheme(button.dataset.editorTheme); pushHistory(); });
document.querySelector('#save').onclick = () => saveLocal(); document.querySelector('#restore').onclick = restoreSaved;
document.querySelector('#preview').onclick = () => { saveLocal('Abrindo prévia limpa.'); window.open('../preview/', '_blank', 'noopener'); };
document.querySelector('#undo').onclick = () => { if (historyIndex > 0) { content = clone(history[--historyIndex]); applyContent(); renderProperties(); updateHistoryControls(); } };
document.querySelector('#redo').onclick = () => { if (historyIndex < history.length - 1) { content = clone(history[++historyIndex]); applyContent(); renderProperties(); updateHistoryControls(); } };
document.querySelector('#close-media').onclick = closeMedia; modal.addEventListener('click', event => { if (event.target === modal) closeMedia(); });
window.EditorCore = {
  get content() { return content; },
  apply: applyContent,
  commit(message) { pushHistory(); if (message) announce(message); },
  replace(next, message) { content = next; applyContent(); pushHistory(); renderProperties(); if (message) announce(message); },
  renderProperties, announce, frame, select
};
frame.addEventListener('load', async () => { if (!content) { content = await loadDefault(); const saved = localStorage.getItem(STORAGE_KEY); if (saved && confirm('Há conteúdo salvo neste navegador. Restaurar agora?')) { try { content = JSON.parse(saved); } catch {} } pushHistory(); } applyContent(); hookFrame(); });
