const {test,expect}=require('@playwright/test');
const fs=require('fs');
const path=require('path');

const base='http://127.0.0.1:8099/tools/browser-fixture/admin-teaching-product-audit.php';
const views=['overview','course-list','course','cohort-list','cohort','registrations','students','questions','tests','site','laboratory','system'];
const viewports={desktop:{width:1440,height:1100},phone:{width:390,height:844}};

function rgbTuple(value){
  const m=String(value).match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
  return m?m.slice(1,4).map(Number):null;
}
function colorDistance(a,b){
  const aa=rgbTuple(a),bb=rgbTuple(b);
  if(!aa||!bb)return 999;
  return Math.sqrt(aa.reduce((sum,v,i)=>sum+(v-bb[i])**2,0));
}
async function expectNoDocumentOverflow(page,label){
  const d=await page.evaluate(()=>({doc:document.documentElement.scrollWidth,client:document.documentElement.clientWidth,body:document.body.scrollWidth}));
  expect(d.doc,`${label}: document overflow`).toBeLessThanOrEqual(d.client+1);
  expect(d.body,`${label}: body overflow`).toBeLessThanOrEqual(d.client+1);
}
async function expectActiveLinkLegible(locator,label){
  await expect(locator,`${label}: active destination missing`).toHaveCount(1);
  await expect(locator).toBeVisible();
  const state=await locator.evaluate(el=>({text:(el.textContent||'').trim(),fg:getComputedStyle(el).color,bg:getComputedStyle(el).backgroundColor}));
  expect(state.text,`${label}: active destination has no label`).not.toBe('');
  expect(state.bg,`${label}: active destination must not be white`).not.toBe('rgb(255, 255, 255)');
  expect(colorDistance(state.fg,state.bg),`${label}: active destination foreground/background are indistinguishable`).toBeGreaterThan(120);
}
async function expectBoxInsideViewport(page,locator,label){
  const box=await locator.boundingBox();
  expect(box,`${label}: no bounding box`).not.toBeNull();
  const viewport=page.viewportSize();
  expect(box.x,`${label}: starts outside viewport`).toBeGreaterThanOrEqual(-1);
  expect(box.x+box.width,`${label}: ends outside viewport`).toBeLessThanOrEqual(viewport.width+1);
}

for(const [device,viewport] of Object.entries(viewports)){
  for(const view of views){
    test(`admin product visual audit ${device} ${view}`,async({page})=>{
      await page.setViewportSize(viewport);
      await page.goto(`${base}?view=${view}`,{waitUntil:'networkidle'});
      await expectNoDocumentOverflow(page,`${device}/${view}`);

      const navLinks=page.locator('.admin-nav-links a');
      const labels=await navLinks.allTextContents();
      for(const label of labels)expect(label.trim(),`${device}/${view}: blank sidebar item`).not.toBe('');

      if(device==='phone'){
        const toggle=page.getByRole('button',{name:'Menu'});
        await toggle.click();
        await expect(page.locator('.admin-sidebar')).toHaveClass(/is-open/);
        await expectActiveLinkLegible(page.locator('.admin-nav-links a[aria-current="page"]'),`${device}/${view}`);
        await toggle.click();
        await expect(page.locator('.admin-sidebar')).not.toHaveClass(/is-open/);
      }else{
        await expectActiveLinkLegible(page.locator('.admin-nav-links a[aria-current="page"]'),`${device}/${view}`);
      }

      const contextCurrent=page.locator('.admin-workspace-nav a[aria-current="page"]');
      if(await contextCurrent.count()){
        await expectActiveLinkLegible(contextCurrent,`${device}/${view} workspace`);
      }

      if(view==='course'){
        await expect(page.getByRole('link',{name:'← Todos os cursos'})).toBeVisible();
        await expect(page.getByRole('navigation',{name:'Localização'})).toContainText('Cursos');
        await expect(page.getByRole('navigation',{name:'Localização'})).toContainText('Positivo Direto em Filme de Raio-X');
      }
      if(view==='cohort'||['students','questions','tests'].includes(view)){
        const back=page.getByRole('link',{name:'← Voltar às turmas'});
        await expect(back).toBeVisible();
        await expectBoxInsideViewport(page,back,`${device}/${view} cohort back`);
        const breadcrumb=page.getByRole('navigation',{name:'Localização'});
        await expect(breadcrumb).toContainText('Cursos');
        await expect(breadcrumb).toContainText('Turmas');
        await expect(breadcrumb).toContainText('Outubro 2026');
      }

      if(device==='phone'){
        const responsive=page.locator('.admin-responsive-list');
        if(await responsive.count()){
          const scrollers=page.locator('.admin-table-scroll:has(.admin-responsive-list)');
          const overflows=await scrollers.evaluateAll(nodes=>nodes.map(el=>el.scrollWidth-el.clientWidth));
          for(const overflow of overflows)expect(overflow,`${view}: operational collection still requires horizontal scrolling`).toBeLessThanOrEqual(1);
          const actions=responsive.locator('td[data-actions="true"] .admin-button');
          for(let i=0;i<await actions.count();i++)await expectBoxInsideViewport(page,actions.nth(i),`${view} action ${i+1}`);
        }
      }

      const outDir=path.join('test-results','admin-visual-audit',device);
      fs.mkdirSync(outDir,{recursive:true});
      await page.screenshot({path:path.join(outDir,`${view}.png`),fullPage:true,animations:'disabled'});
    });
  }
}
