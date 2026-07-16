(() => {
  const grid = document.querySelector('#library-grid');
  const status = document.querySelector('#library-status');
  const upload = document.querySelector('#library-upload');
  if (!grid || !status || !upload || !window.MediaLibrary) return;
  const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character]));
  const size = (value) => value < 1048576 ? `${Math.ceil(value / 1024)} KB` : `${(value / 1048576).toFixed(1)} MB`;
  const render = () => {
    const items = MediaLibrary.items;
    status.textContent = items.length ? `${items.length} arquivo${items.length === 1 ? '' : 's'} na biblioteca. Limite efetivo para envio: ${MediaLibrary.uploadLimitLabel()}.` : 'A biblioteca ainda não possui arquivos.';
    grid.innerHTML = items.map((item) => {
      const title = escapeHtml(item.title);
      const source = escapeHtml(item.derivatives.filter((derivative) => derivative.format === 'jpeg' || derivative.format === 'png').at(-1)?.src || item.url);
      const preview = item.kind === 'image' ? `<img src="${source}" alt="">` : item.poster?.src ? `<img src="${escapeHtml(item.poster.src)}" alt="Poster de ${title}">` : '<span>Vídeo</span>';
      return `<article class="library-card"><div class="library-preview">${preview}</div><div><p class="admin-kicker">${item.kind === 'image' ? 'Imagem' : 'Vídeo'} · ${item.processing_status === 'ready' ? 'Pronto' : escapeHtml(item.processing_status)}</p><h2>${title}</h2><p>${item.width && item.height ? `${item.width} × ${item.height} · ` : ''}${size(item.byte_size)}</p></div></article>`;
    }).join('') || '<div class="admin-empty compact"><h2>Nenhum arquivo na biblioteca</h2><p>Envie uma imagem ou um vídeo compatível quando precisar usá-lo na página.</p></div>';
  };
  const load = async () => {
    try { await MediaLibrary.load(); render(); }
    catch { status.textContent = 'Não foi possível carregar a biblioteca de mídia. Atualize a página ou tente novamente.'; }
  };
  upload.addEventListener('change', async () => {
    const file = upload.files?.[0];
    if (!file) return;
    status.textContent = 'Enviando arquivo…';
    try { await MediaLibrary.upload(file); render(); }
    catch (error) { status.textContent = `Não foi possível enviar o arquivo: ${error.message}`; }
    finally { upload.value = ''; }
  });
  load();
})();
