(() => {
  'use strict';
  const root = document.querySelector('#form-admin-root');
  if (!root) return;

  const id = Number(root.dataset.formId);
  let csrf = '';
  let form = null;
  let selected = null;
  let dirty = false;

  const types = [
    ['text','Texto curto'],['email','E-mail'],['tel','Telefone'],['number','Número'],['textarea','Texto longo'],
    ['select','Lista'],['radio','Escolha única'],['checkbox','Checkbox'],['checkbox-group','Múltipla escolha'],['date','Data'],['time','Hora'],['consent','Aceite / consentimento']
  ];
  const autocomplete = ['', 'name','email','tel','country-name','address-level2','organization','given-name','family-name','postal-code','off'];
  const esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
  const slug = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().replace(/[^a-z0-9_]+/g,'_').replace(/^_+|_+$/g,'') || 'field';
  const typeLabel = type => types.find(row => row[0] === type)?.[1] || type;

  async function request(url, options = {}) {
    const response = await fetch(url, {credentials:'same-origin', ...options});
    const body = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(body?.error?.message || body?.error?.code || `HTTP ${response.status}`);
    return body;
  }
  function post(url, payload) { return request(url, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({...payload, csrf})}); }
  function markDirty() { dirty = true; renderStatus(); renderPreview(); }
  function renderStatus() {
    const status = root.querySelector('#builder-status');
    if (!status || !form) return;
    const unpublished = form.publishedRevision == null || form.publishedRevision !== form.draftRevision;
    status.textContent = dirty ? 'Alterações não salvas' : unpublished ? 'Rascunho não publicado' : 'Publicado';
  }

  function optionEditor(field) {
    if (!['select','radio','checkbox-group'].includes(field.type)) return '';
    field.options = Array.isArray(field.options) ? field.options : [];
    return `<div class="form-builder-options"><strong>Opções</strong><div id="builder-options">${field.options.map((option,index)=>`<div class="form-builder-option" draggable="true" data-option-row="${index}"><span class="form-drag">⋮⋮</span><input data-ov="${index}" value="${esc(option.value)}" placeholder="valor"><input data-ol="${index}" value="${esc(option.label)}" placeholder="rótulo"><button type="button" data-or="${index}" aria-label="Remover opção">×</button></div>`).join('')}</div><button type="button" id="builder-add-option">+ opção</button></div>`;
  }

  function render() {
    const schema = form.schema;
    root.innerHTML = `<div class="form-builder form-builder-pro">
      <div class="form-builder-settings">
        <div class="form-builder-heading"><div><p class="admin-kicker">Configuração</p><h2>${esc(form.title)}</h2></div><strong id="builder-status"></strong></div>
        <label>Título interno<input id="builder-title" value="${esc(form.title)}"></label>
        <div class="form-grid two-columns"><label>Texto do botão<input id="builder-submit" value="${esc(schema.submitLabel)}"></label><label>Título após envio<input id="builder-success-title" value="${esc(schema.successTitle)}"></label></div>
        <label>Mensagem após envio<textarea id="builder-success-message" rows="3">${esc(schema.successMessage)}</textarea></label>
        <div class="form-builder-actions"><button id="builder-add" type="button">+ Campo</button><button id="builder-save" type="button" class="admin-button">Salvar rascunho</button><button id="builder-publish" type="button" class="admin-button secondary">Publicar</button></div>
      </div>
      <div class="form-builder-workspace">
        <div class="form-builder-fields"><div id="builder-list"></div><div id="builder-field"></div></div>
        <aside class="form-builder-preview"><p class="admin-kicker">Prévia</p><h2>Formulário</h2><div id="builder-preview"></div></aside>
      </div>
    </div>`;

    root.querySelector('#builder-title').oninput = event => { form.title = event.target.value; markDirty(); };
    root.querySelector('#builder-submit').oninput = event => { schema.submitLabel = event.target.value; markDirty(); };
    root.querySelector('#builder-success-title').oninput = event => { schema.successTitle = event.target.value; markDirty(); };
    root.querySelector('#builder-success-message').oninput = event => { schema.successMessage = event.target.value; markDirty(); };
    root.querySelector('#builder-add').onclick = () => addField();
    root.querySelector('#builder-save').onclick = async () => { try { await save(); flash('Rascunho salvo.'); } catch (error) { alert(error.message); } };
    root.querySelector('#builder-publish').onclick = publish;
    renderList(); renderField(); renderPreview(); renderStatus();
  }

  function uniqueId(base='field') {
    let candidate = slug(base), n = 2;
    while (form.schema.fields.some(field => field.id === candidate)) candidate = `${slug(base)}_${n++}`;
    return candidate;
  }
  function addField(after = null) {
    const field = {id:uniqueId('field'),type:'text',label:'Novo campo',required:false,width:'full'};
    const fields = form.schema.fields;
    if (after == null) { fields.push(field); selected = fields.length - 1; }
    else { fields.splice(after + 1,0,field); selected = after + 1; }
    markDirty(); renderList(); renderField();
  }
  function duplicate(index) {
    const fields = form.schema.fields, original = fields[index];
    const copy = JSON.parse(JSON.stringify(original));
    copy.id = uniqueId(original.id + '_copy'); copy.label = original.label + ' — cópia';
    fields.splice(index + 1,0,copy); selected = index + 1; markDirty(); renderList(); renderField();
  }

  function renderList() {
    const fields = form.schema.fields, list = root.querySelector('#builder-list');
    list.innerHTML = fields.map((field,index)=>`<div class="form-builder-row ${selected===index?'selected':''}" draggable="true" data-field-row="${index}"><span class="form-drag">⋮⋮</span><button type="button" data-fi="${index}"><strong>${esc(field.label)}</strong><small>${esc(typeLabel(field.type))} · ${field.required?'obrigatório':'opcional'} · ${field.width==='half'?'½ linha':'linha inteira'}</small></button><button type="button" data-copy="${index}" title="Duplicar">⧉</button><button type="button" data-fu="${index}" title="Subir">↑</button><button type="button" data-fd="${index}" title="Descer">↓</button><button type="button" data-fr="${index}" title="Remover">×</button></div>`).join('');
    list.querySelectorAll('[data-fi]').forEach(button => button.onclick = () => { selected=Number(button.dataset.fi); renderList(); renderField(); });
    list.querySelectorAll('[data-copy]').forEach(button => button.onclick = () => duplicate(Number(button.dataset.copy)));
    list.querySelectorAll('[data-fu]').forEach(button => button.onclick = () => move(Number(button.dataset.fu),-1));
    list.querySelectorAll('[data-fd]').forEach(button => button.onclick = () => move(Number(button.dataset.fd),1));
    list.querySelectorAll('[data-fr]').forEach(button => button.onclick = () => remove(Number(button.dataset.fr)));
    let drag = null;
    list.querySelectorAll('[data-field-row]').forEach(row => {
      row.ondragstart = () => { drag=Number(row.dataset.fieldRow); row.classList.add('dragging'); };
      row.ondragend = () => row.classList.remove('dragging');
      row.ondragover = event => event.preventDefault();
      row.ondrop = event => { event.preventDefault(); const to=Number(row.dataset.fieldRow); if (drag==null || drag===to) return; const moving=fields.splice(drag,1)[0]; fields.splice(to,0,moving); selected=to; drag=null; markDirty(); renderList(); renderField(); };
    });
  }
  function move(index,direction) { const fields=form.schema.fields,target=index+direction;if(target<0||target>=fields.length)return;fields.splice(target,0,fields.splice(index,1)[0]);selected=target;markDirty();renderList();renderField(); }
  function remove(index) { const field=form.schema.fields[index];if(!confirm(`Remover “${field.label}”?`))return;form.schema.fields.splice(index,1);selected=null;markDirty();renderList();renderField(); }

  function renderField() {
    const host=root.querySelector('#builder-field');
    if(selected===null||!form.schema.fields[selected]){host.innerHTML='<div class="admin-empty compact"><p>Selecione um campo para editar suas propriedades.</p></div>';return;}
    const field=form.schema.fields[selected];
    host.innerHTML=`<div class="form-builder-field"><div class="form-builder-heading"><div><p class="admin-kicker">Campo ${selected+1}</p><h3>${esc(field.label)}</h3></div><button type="button" id="bf-duplicate" class="link-button">Duplicar</button></div>
      <label>Rótulo<input id="bf-label" value="${esc(field.label)}"></label>
      <div class="form-grid two-columns"><label>Identificador<input id="bf-id" value="${esc(field.id)}"></label><label>Tipo<select id="bf-type">${types.map(([type,label])=>`<option value="${type}" ${field.type===type?'selected':''}>${esc(label)}</option>`).join('')}</select></label></div>
      <div class="form-grid two-columns"><label>Largura<select id="bf-width"><option value="full" ${field.width!=='half'?'selected':''}>Linha inteira</option><option value="half" ${field.width==='half'?'selected':''}>Meia linha</option></select></label><label>Autocomplete<select id="bf-autocomplete">${autocomplete.map(value=>`<option value="${value}" ${(field.autocomplete||'')===value?'selected':''}>${value||'Automático'}</option>`).join('')}</select></label></div>
      <label class="check"><input id="bf-required" type="checkbox" ${field.required?'checked':''}> Campo obrigatório</label>
      <label>Texto de ajuda<input id="bf-help" value="${esc(field.help||'')}"></label><label>Placeholder<input id="bf-placeholder" value="${esc(field.placeholder||'')}"></label>
      ${field.type==='textarea'?`<label>Linhas visíveis<input id="bf-rows" type="number" min="2" max="12" value="${Number(field.rows)||4}"></label>`:''}
      ${optionEditor(field)}
    </div>`;
    root.querySelector('#bf-duplicate').onclick=()=>duplicate(selected);
    const sync=()=>{field.label=host.querySelector('#bf-label').value;const wanted=slug(host.querySelector('#bf-id').value);field.id=wanted===field.id?wanted:uniqueId(wanted);host.querySelector('#bf-id').value=field.id;field.type=host.querySelector('#bf-type').value;field.width=host.querySelector('#bf-width').value;field.required=host.querySelector('#bf-required').checked;field.help=host.querySelector('#bf-help').value;field.placeholder=host.querySelector('#bf-placeholder').value;field.autocomplete=host.querySelector('#bf-autocomplete').value;if(host.querySelector('#bf-rows'))field.rows=Math.max(2,Math.min(12,Number(host.querySelector('#bf-rows').value)||4));if(['select','radio','checkbox-group'].includes(field.type)&&!Array.isArray(field.options))field.options=[];markDirty();renderList();};
    host.querySelectorAll('#bf-label,#bf-id,#bf-type,#bf-width,#bf-required,#bf-help,#bf-placeholder,#bf-autocomplete,#bf-rows').forEach(element=>element?.addEventListener('change',()=>{const oldType=field.type;sync();if(oldType!==field.type)renderField();}));
    bindOptions(field,host);
  }
  function bindOptions(field,host){if(!['select','radio','checkbox-group'].includes(field.type))return;host.querySelectorAll('[data-ov]').forEach(el=>el.oninput=()=>{field.options[Number(el.dataset.ov)].value=slug(el.value);markDirty()});host.querySelectorAll('[data-ol]').forEach(el=>el.oninput=()=>{field.options[Number(el.dataset.ol)].label=el.value;markDirty()});host.querySelectorAll('[data-or]').forEach(el=>el.onclick=()=>{field.options.splice(Number(el.dataset.or),1);markDirty();renderField()});host.querySelector('#builder-add-option').onclick=()=>{field.options.push({value:uniqueOption(field),label:'Nova opção'});markDirty();renderField()};let drag=null;host.querySelectorAll('[data-option-row]').forEach(row=>{row.ondragstart=()=>drag=Number(row.dataset.optionRow);row.ondragover=e=>e.preventDefault();row.ondrop=e=>{e.preventDefault();const to=Number(row.dataset.optionRow);if(drag==null||drag===to)return;field.options.splice(to,0,field.options.splice(drag,1)[0]);drag=null;markDirty();renderField()}})}
  function uniqueOption(field){let n=field.options.length+1,value=`option_${n}`;while(field.options.some(o=>o.value===value))value=`option_${++n}`;return value}

  function previewInput(field){const label=`<span>${esc(field.label)}${field.required?' *':''}</span>`,help=field.help?`<small>${esc(field.help)}</small>`:'';if(field.type==='textarea')return `<label class="cms-field ${field.width==='half'?'half':''}">${label}<textarea rows="${field.rows||4}" placeholder="${esc(field.placeholder||'')}"></textarea>${help}</label>`;if(field.type==='select')return `<label class="cms-field ${field.width==='half'?'half':''}">${label}<select><option></option>${(field.options||[]).map(o=>`<option>${esc(o.label)}</option>`).join('')}</select>${help}</label>`;if(['radio','checkbox-group'].includes(field.type))return `<fieldset class="cms-choice-group ${field.width==='half'?'half':''}"><legend>${label}</legend><div class="cms-choice-grid">${(field.options||[]).map(o=>`<label><input type="${field.type==='radio'?'radio':'checkbox'}"><span>${esc(o.label)}</span></label>`).join('')}</div>${help}</fieldset>`;if(['checkbox','consent'].includes(field.type))return `<label class="cms-consent ${field.width==='half'?'half':''}"><input type="checkbox"><span>${esc(field.label)}${field.required?' *':''}</span></label>`;return `<label class="cms-field ${field.width==='half'?'half':''}">${label}<input type="${['email','tel','number','date','time'].includes(field.type)?field.type:'text'}" placeholder="${esc(field.placeholder||'')}">${help}</label>`}
  function renderPreview(){const host=root.querySelector('#builder-preview');if(!host||!form)return;host.innerHTML=`<div class="cms-form preview-only"><div class="cms-form-grid">${form.schema.fields.map(previewInput).join('')}</div><button type="button" class="button">${esc(form.schema.submitLabel||'Enviar')} ↗</button></div>`}
  function flash(message){const status=root.querySelector('#builder-status');if(status){status.textContent=message;setTimeout(renderStatus,1400)}}

  async function save(){const data=await post('/admin/api/cms-form-save.php',{formId:form.id,revision:form.draftRevision,title:form.title,schema:form.schema});form=data.form;dirty=false;render();return form;}
  async function publish(){try{await save();const data=await post('/admin/api/cms-form-publish.php',{formId:form.id});form.publishedRevision=data.publishedRevision;dirty=false;alert('Formulário publicado.');render()}catch(error){alert(error.message)}}
  async function load(){try{const data=await request(`/admin/api/cms-form-load.php?form=${id}`);csrf=data.csrf;form=data.form;render()}catch(error){root.textContent=error.message}}
  window.addEventListener('beforeunload',event=>{if(!dirty)return;event.preventDefault();event.returnValue='';});
  load();
})();
