(() => {
  'use strict';
  const root = document.querySelector('#form-admin-root');
  if (!root) return;

  const id = Number(root.dataset.formId);
  let csrf = '';
  let form = null;
  let selected = null;

  const esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
  })[char]);

  async function request(url, options = {}) {
    const response = await fetch(url, {credentials: 'same-origin', ...options});
    const body = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(body?.error?.message || body?.error?.code || `HTTP ${response.status}`);
    return body;
  }

  function post(url, payload) {
    return request(url, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({...payload, csrf})
    });
  }

  function optionEditor(field) {
    if (!['select', 'radio', 'checkbox-group'].includes(field.type)) return '';
    field.options = field.options || [];
    return `<div class="form-builder-options">
      <strong>Opções</strong>
      <div id="builder-options">${field.options.map((option, index) => `<div class="form-builder-option">
        <input data-ov="${index}" value="${esc(option.value)}" placeholder="valor">
        <input data-ol="${index}" value="${esc(option.label)}" placeholder="rótulo">
        <button type="button" data-or="${index}">×</button>
      </div>`).join('')}</div>
      <button type="button" id="builder-add-option">+ opção</button>
    </div>`;
  }

  function render() {
    const schema = form.schema;
    root.innerHTML = `<div class="form-builder">
      <div class="form-builder-settings">
        <label>Título interno<input id="builder-title" value="${esc(form.title)}"></label>
        <label>Texto do botão<input id="builder-submit" value="${esc(schema.submitLabel)}"></label>
        <label>Título após envio<input id="builder-success-title" value="${esc(schema.successTitle)}"></label>
        <label>Mensagem após envio<textarea id="builder-success-message">${esc(schema.successMessage)}</textarea></label>
        <div class="form-builder-actions">
          <button id="builder-add" type="button">+ Campo</button>
          <button id="builder-save" type="button" class="admin-button">Salvar rascunho</button>
          <button id="builder-publish" type="button" class="admin-button secondary">Publicar</button>
        </div>
      </div>
      <div class="form-builder-fields"><div id="builder-list"></div><div id="builder-field"></div></div>
    </div>`;

    root.querySelector('#builder-title').oninput = event => { form.title = event.target.value; };
    root.querySelector('#builder-submit').oninput = event => { schema.submitLabel = event.target.value; };
    root.querySelector('#builder-success-title').oninput = event => { schema.successTitle = event.target.value; };
    root.querySelector('#builder-success-message').oninput = event => { schema.successMessage = event.target.value; };
    root.querySelector('#builder-add').onclick = () => {
      let number = schema.fields.length + 1;
      let fieldId = `field_${number}`;
      while (schema.fields.some(field => field.id === fieldId)) fieldId = `field_${++number}`;
      schema.fields.push({id: fieldId, type: 'text', label: 'Novo campo', required: false, width: 'full'});
      selected = schema.fields.length - 1;
      renderList();
      renderField();
    };
    root.querySelector('#builder-save').onclick = async () => {
      try { await save(); alert('Rascunho salvo.'); }
      catch (error) { alert(error.message); }
    };
    root.querySelector('#builder-publish').onclick = publish;
    renderList();
    renderField();
  }

  function renderList() {
    const fields = form.schema.fields;
    const list = root.querySelector('#builder-list');
    list.innerHTML = fields.map((field, index) => `<div class="form-builder-row ${selected === index ? 'selected' : ''}">
      <button type="button" data-fi="${index}"><strong>${esc(field.label)}</strong><small>${esc(field.type)} · ${field.required ? 'obrigatório' : 'opcional'}</small></button>
      <button type="button" data-fu="${index}">↑</button>
      <button type="button" data-fd="${index}">↓</button>
      <button type="button" data-fr="${index}">×</button>
    </div>`).join('');

    list.querySelectorAll('[data-fi]').forEach(button => {
      button.onclick = () => { selected = Number(button.dataset.fi); renderList(); renderField(); };
    });
    list.querySelectorAll('[data-fu]').forEach(button => { button.onclick = () => move(Number(button.dataset.fu), -1); });
    list.querySelectorAll('[data-fd]').forEach(button => { button.onclick = () => move(Number(button.dataset.fd), 1); });
    list.querySelectorAll('[data-fr]').forEach(button => { button.onclick = () => remove(Number(button.dataset.fr)); });
  }

  function move(index, direction) {
    const fields = form.schema.fields;
    const target = index + direction;
    if (target < 0 || target >= fields.length) return;
    fields.splice(target, 0, fields.splice(index, 1)[0]);
    selected = target;
    renderList();
    renderField();
  }

  function remove(index) {
    const field = form.schema.fields[index];
    if (!confirm(`Remover “${field.label}”?`)) return;
    form.schema.fields.splice(index, 1);
    selected = null;
    renderList();
    renderField();
  }

  function renderField() {
    const host = root.querySelector('#builder-field');
    if (selected === null || !form.schema.fields[selected]) {
      host.innerHTML = '<p class="admin-muted">Selecione um campo para editar.</p>';
      return;
    }
    const field = form.schema.fields[selected];
    host.innerHTML = `<div class="form-builder-field">
      <h3>Campo selecionado</h3>
      <label>Rótulo<input id="bf-label" value="${esc(field.label)}"></label>
      <label>Identificador<input id="bf-id" value="${esc(field.id)}"></label>
      <label>Tipo<select id="bf-type">${['text','email','tel','number','textarea','select','radio','checkbox','checkbox-group','date','time','consent'].map(type => `<option value="${type}" ${field.type === type ? 'selected' : ''}>${type}</option>`).join('')}</select></label>
      <label>Largura<select id="bf-width"><option value="full" ${field.width !== 'half' ? 'selected' : ''}>Linha inteira</option><option value="half" ${field.width === 'half' ? 'selected' : ''}>Meia linha</option></select></label>
      <label class="check"><input id="bf-required" type="checkbox" ${field.required ? 'checked' : ''}> Obrigatório</label>
      <label>Ajuda<input id="bf-help" value="${esc(field.help || '')}"></label>
      <label>Placeholder<input id="bf-placeholder" value="${esc(field.placeholder || '')}"></label>
      ${optionEditor(field)}
    </div>`;

    const sync = () => {
      field.label = host.querySelector('#bf-label').value;
      field.id = host.querySelector('#bf-id').value;
      field.type = host.querySelector('#bf-type').value;
      field.width = host.querySelector('#bf-width').value;
      field.required = host.querySelector('#bf-required').checked;
      field.help = host.querySelector('#bf-help').value;
      field.placeholder = host.querySelector('#bf-placeholder').value;
      if (['select','radio','checkbox-group'].includes(field.type) && !Array.isArray(field.options)) field.options = [];
      renderList();
    };
    host.querySelectorAll('#bf-label,#bf-id,#bf-type,#bf-width,#bf-required,#bf-help,#bf-placeholder').forEach(element => element.addEventListener('change', sync));

    if (['select','radio','checkbox-group'].includes(field.type)) {
      host.querySelectorAll('[data-ov]').forEach(element => { element.oninput = () => { field.options[Number(element.dataset.ov)].value = element.value; }; });
      host.querySelectorAll('[data-ol]').forEach(element => { element.oninput = () => { field.options[Number(element.dataset.ol)].label = element.value; }; });
      host.querySelectorAll('[data-or]').forEach(element => { element.onclick = () => { field.options.splice(Number(element.dataset.or), 1); renderField(); }; });
      host.querySelector('#builder-add-option').onclick = () => { field.options.push({value: `option_${field.options.length + 1}`, label: 'Nova opção'}); renderField(); };
    }
  }

  async function save() {
    const data = await post('/admin/api/cms-form-save.php', {formId: form.id, revision: form.draftRevision, title: form.title, schema: form.schema});
    form = data.form;
    render();
    return form;
  }

  async function publish() {
    try {
      await save();
      const data = await post('/admin/api/cms-form-publish.php', {formId: form.id});
      form.publishedRevision = data.publishedRevision;
      alert('Formulário publicado.');
      render();
    } catch (error) { alert(error.message); }
  }

  async function load() {
    try {
      const data = await request(`/admin/api/cms-form-load.php?form=${id}`);
      csrf = data.csrf;
      form = data.form;
      render();
    } catch (error) { root.textContent = error.message; }
  }

  load();
})();
