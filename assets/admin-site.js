(()=>{
  'use strict';

  const escapeHtml=value=>String(value??'').replace(/[&<>"']/g,char=>({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
  }[char]));

  function bindNavigationBuilder(){
    const host=document.querySelector('[data-site-nav-builder]');
    const form=document.querySelector('[data-site-settings-form]');
    if(!host||!form)return;

    let pages=[];
    try{pages=JSON.parse(host.dataset.pages||'[]');}catch(_error){pages=[];}
    const pageOptions=()=>pages.map(page=>`<option value="${Number(page.id)}">${escapeHtml(page.title)}</option>`).join('');
    let dragged=null;

    const bind=row=>{
      row.querySelector('[data-remove-nav]')?.addEventListener('click',()=>row.remove());
      row.addEventListener('dragstart',()=>{dragged=row;row.classList.add('is-dragging');});
      row.addEventListener('dragend',()=>{row.classList.remove('is-dragging');dragged=null;});
      row.addEventListener('dragover',event=>{
        event.preventDefault();
        if(!dragged||dragged===row)return;
        const box=row.getBoundingClientRect();
        if(event.clientY>box.top+(box.height/2))row.after(dragged);else row.before(dragged);
      });
    };

    const createRow=type=>{
      const row=document.createElement('div');
      row.className='site-nav-row';
      row.draggable=true;
      row.dataset.navRow='';
      row.innerHTML=`<span class="site-nav-handle" aria-hidden="true">⋮⋮</span><input type="hidden" data-field="type" value="${type}">${type==='page'?`<label>Página<select data-field="pageId">${pageOptions()}</select></label><label>Rótulo<input data-field="label" placeholder="Usar o título da página"></label>`:`<label>Rótulo<input data-field="label"></label><label>URL<input data-field="url" placeholder="https://…"></label>`}<label class="check-row compact"><input type="checkbox" data-field="newTab" value="1"> Nova aba</label><button type="button" class="link-button danger" data-remove-nav>Remover</button>`;
      bind(row);
      return row;
    };

    host.querySelectorAll('[data-nav-row]').forEach(bind);
    document.querySelector('[data-add-nav-page]')?.addEventListener('click',()=>host.append(createRow('page')));
    document.querySelector('[data-add-nav-custom]')?.addEventListener('click',()=>host.append(createRow('custom')));

    form.addEventListener('submit',()=>{
      form.querySelectorAll('[data-generated-nav]').forEach(node=>node.remove());
      [...host.querySelectorAll('[data-nav-row]')].forEach((row,index)=>{
        row.querySelectorAll('[data-field]').forEach(field=>{
          const input=document.createElement('input');
          input.type='hidden';
          input.dataset.generatedNav='1';
          input.name=`navigation_items[${index}][${field.dataset.field}]`;
          input.value=field.type==='checkbox'?(field.checked?'1':''):field.value;
          form.append(input);
        });
      });
    });
  }

  function bindFooterLinks(){
    const host=document.querySelector('[data-footer-links]');
    const add=document.querySelector('[data-add-footer-link]');
    if(!host||!add)return;
    add.addEventListener('click',()=>{
      const index=host.querySelectorAll('[data-footer-link]').length;
      const row=document.createElement('div');
      row.className='site-footer-link';
      row.dataset.footerLink='';
      row.innerHTML=`<label>Rótulo<input name="footer_links[${index}][label]"></label><label>URL<input name="footer_links[${index}][url]"></label><button type="button" class="link-button danger" data-remove-footer>Remover</button>`;
      row.querySelector('[data-remove-footer]')?.addEventListener('click',()=>row.remove());
      host.append(row);
    });
    host.querySelectorAll('[data-remove-footer]').forEach(button=>button.addEventListener('click',()=>button.closest('[data-footer-link]')?.remove()));
  }

  function bindSeoPreview(){
    const picker=document.querySelector('[data-seo-social-picker]');
    const image=document.querySelector('[data-seo-social-image]');
    const title=document.querySelector('[data-seo-title]');
    const description=document.querySelector('[data-seo-description]');
    const previewTitle=document.querySelector('[data-seo-preview-title]');
    const previewDescription=document.querySelector('[data-seo-preview-description]');
    if(picker&&image)picker.addEventListener('change',()=>{image.value=picker.value;});
    if(title&&previewTitle){
      const fallback=previewTitle.dataset.fallback||'';
      title.addEventListener('input',()=>{previewTitle.textContent=title.value||fallback;});
    }
    if(description&&previewDescription)description.addEventListener('input',()=>{previewDescription.textContent=description.value;});
  }

  bindNavigationBuilder();
  bindFooterLinks();
  bindSeoPreview();
})();
