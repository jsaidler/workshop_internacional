(()=>{
'use strict';
const form=document.querySelector('[data-recorded-process-form]');
if(!form)return;
const stage=form.querySelector('[data-recorded-stage]');
const development=form.querySelector('[data-recorded-development]');
const custom=form.querySelector('[data-recorded-custom]');
const developer=form.querySelector('[data-recorded-developer]');
const stock=form.querySelector('[data-recorded-stock-volume]');
const water=form.querySelector('[data-recorded-water]');
const fresh=form.querySelector('[data-recorded-fresh-volume]');
const syncDeveloper=()=>{
  if(!developer)return;
  const option=developer.selectedOptions[0];const mode=option?.dataset.preparationMode||'stock';
  const isFresh=mode==='fresh';
  if(stock)stock.hidden=isFresh;
  if(water)water.hidden=isFresh;
  if(fresh)fresh.hidden=!isFresh;
};
const syncStage=()=>{
  const option=stage?.selectedOptions[0];const type=option?.dataset.stageType||'';const key=stage?.value||'';
  if(development)development.hidden=type!=='development';
  if(custom)custom.hidden=key!=='custom';
  if(type==='development')syncDeveloper();
};
stage?.addEventListener('change',syncStage);
developer?.addEventListener('change',syncDeveloper);
syncStage();
})();
