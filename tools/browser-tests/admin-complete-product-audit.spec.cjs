const {test,expect}=require('@playwright/test');
const fs=require('fs');
const path=require('path');

const base='http://127.0.0.1:8099/tools/browser-fixture/admin-complete-product-audit.php';
const views=[
  'overview','courses','course','cohorts','cohort','registrations','registration','students','student',
  'questions','question','tests','test','lessons','material','pages','site','design','forms','form',
  'responses','media','media-detail','analytics','processes','process','lab-catalogs','system','integrity',
  'activities','people','person'
];
const viewports={
  phone:{width:390,height:844},
  tablet:{width:768,height:1024},
  compact:{width:1280,height:800},
  wide:{width:1600,height:900}
};

async function expectNoDocumentOverflow(page,label){
  const d=await page.evaluate(()=>({viewport:document.documentElement.clientWidth,doc:document.documentElement.scrollWidth,body:document.body.scrollWidth}));
  expect(d.doc,label+' document overflow').toBeLessThanOrEqual(d.viewport+1);
  expect(d.body,label+' body overflow').toBeLessThanOrEqual(d.viewport+1);
}
async function expectMainInside(page,label){
  const box=await page.locator('.admin-main').boundingBox();
  expect(box,label+' main box').not.toBeNull();
  expect(box.x,label+' main x').toBeGreaterThanOrEqual(-1);
  expect(box.x+box.width,label+' main end').toBeLessThanOrEqual(page.viewportSize().width+1);
}
async function expectTouchTargets(page,label){
  const bad=await page.locator('.admin-button,.admin-workspace-nav a,.admin-content-tabs a,.admin-status-tabs a,.admin-context-back,.admin-list-return,.admin-menu-toggle,.site-detail-index a,.link-button,.admin-panel-toggle').evaluateAll(nodes=>nodes
    .filter(el=>{const s=getComputedStyle(el);const r=el.getBoundingClientRect();return s.display!=='none'&&s.visibility!=='hidden'&&r.width>0&&r.height>0})
    .map(el=>({text:(el.textContent||'').trim(),height:el.getBoundingClientRect().height}))
    .filter(item=>item.height<39.5));
  expect(bad,label+' touch targets').toEqual([]);
}

for(const [device,viewport] of Object.entries(viewports)){
  for(const view of views){
    test('admin complete audit '+device+' '+view,async({page})=>{
      await page.setViewportSize(viewport);
      await page.goto(base+'?view='+encodeURIComponent(view),{waitUntil:'networkidle'});
      await expectNoDocumentOverflow(page,device+'/'+view);
      await expectMainInside(page,device+'/'+view);
      const active=page.locator('.admin-nav-links a[aria-current="page"]');
      await expect(active,device+'/'+view+' active navigation').toHaveCount(1);

      if(viewport.width<=1000){
        await expect(page.locator('.admin-mobile-header')).toBeVisible();
        await expect(page.locator('.admin-sidebar')).not.toHaveClass(/is-open/);
        await expectTouchTargets(page,device+'/'+view);
      }else{
        await expect(page.locator('.admin-mobile-header')).toBeHidden();
        await expect(page.locator('.admin-sidebar')).toBeVisible();
      }

      const responsive=page.locator('.admin-responsive-list');
      if(viewport.width<=700 && await responsive.count()){
        const overflow=await responsive.evaluateAll(nodes=>nodes.map(el=>el.scrollWidth-el.clientWidth));
        for(const value of overflow)expect(value,device+'/'+view+' responsive list overflow').toBeLessThanOrEqual(1);
      }

      if(view==='responses'&&viewport.width<=900){
        const cols=await page.locator('.inbox-layout').evaluate(el=>getComputedStyle(el).gridTemplateColumns);
        expect(cols.trim().split(/\s+/).length,device+'/responses single column').toBe(1);
      }
      if(view==='media-detail'){
        const dialog=page.locator('.media-detail-dialog');
        await expect(dialog).toBeVisible();
        const box=await dialog.boundingBox();
        expect(box.x).toBeGreaterThanOrEqual(-1);
        expect(box.x+box.width).toBeLessThanOrEqual(viewport.width+1);
      }

      const out=path.join('test-results','admin-complete-audit',device);
      fs.mkdirSync(out,{recursive:true});
      await page.screenshot({path:path.join(out,view+'.png'),fullPage:true,animations:'disabled'});
    });
  }
}

for(const device of ['phone','tablet']){
  test('admin drawer keyboard and focus '+device,async({page})=>{
    const viewport=viewports[device];
    await page.setViewportSize(viewport);
    await page.goto(base+'?view=overview');
    const toggle=page.getByRole('button',{name:'Menu'});
    const sidebar=page.locator('#admin-navigation');
    const backdrop=page.locator('.admin-nav-backdrop');

    await toggle.click();
    await expect(sidebar).toHaveClass(/is-open/);
    await expect(backdrop).toBeVisible();
    await expect(toggle).toHaveAttribute('aria-expanded','true');
    await expect.poll(()=>page.evaluate(()=>document.querySelector('#admin-navigation').contains(document.activeElement))).toBe(true);

    await page.keyboard.press('Escape');
    await expect(sidebar).not.toHaveClass(/is-open/);
    await expect(backdrop).toBeHidden();
    await expect(toggle).toBeFocused();

    await toggle.click();
    await backdrop.click({position:{x:viewport.width-10,y:10}});
    await expect(sidebar).not.toHaveClass(/is-open/);
    await expect(toggle).toBeFocused();
  });
}
