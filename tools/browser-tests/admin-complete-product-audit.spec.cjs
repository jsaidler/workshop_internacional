const {test,expect}=require('@playwright/test');
const fs=require('fs');
const path=require('path');

const base='http://127.0.0.1:8099/tools/browser-fixture/admin-complete-product-audit.php';
const views=[
  'overview','courses','course','cohorts','cohort','registrations','registration','students','course-students','student',
  'questions','question','tests','test','lessons','material','pages','blocks','site','design','seo','forms','form',
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
  const bad=await page.locator('.admin-button,.admin-workspace-nav a,.admin-content-tabs a,.admin-status-tabs a,.admin-context-back,.admin-list-return,.admin-menu-toggle,.admin-nav-links a,.admin-pagination a,.admin-filter-chip,.site-detail-index a,.site-language-switch a,.analytics-period a,.link-button,.admin-panel-toggle,.media-detail-header button,.dialog-close').evaluateAll(nodes=>nodes
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
        await expect(page.locator('.admin-nav-backdrop')).toBeHidden();
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
      if(view==='overview'&&viewport.width<=800){
        const queue=page.locator('.admin-work-queue');
        await expect(queue).toBeVisible();
        const items=queue.locator('.admin-work-item');
        await expect(items).toHaveCount(2);
        const itemLayouts=await items.evaluateAll(nodes=>nodes.map(el=>getComputedStyle(el).display));
        expect(itemLayouts.every(display=>display==='grid'),device+'/overview work queue').toBe(true);
      }
      if(view==='site'&&viewport.width<=650){
        const nav=page.locator('.site-section-nav');
        const navBox=await nav.evaluate(el=>({client:el.clientWidth,scroll:el.scrollWidth}));
        expect(navBox.scroll,device+'/site tabs overflow').toBeLessThanOrEqual(navBox.client+1);
        const savebar=page.locator('.site-savebar');
        await expect(savebar).toHaveCSS('position','static');
        const lastRow=page.locator('.site-nav-row').last();
        const lastBox=await lastRow.boundingBox();
        const saveBox=await savebar.boundingBox();
        expect(saveBox.y,device+'/site savebar overlaps menu row').toBeGreaterThanOrEqual(lastBox.y+lastBox.height);
      }
      if(view==='media-detail'){
        const dialog=page.locator('.media-detail-dialog');
        await expect(dialog).toBeVisible();
        await expect(dialog.locator('.media-detail-header')).toBeVisible();
        await expect(dialog.getByRole('button',{name:'Fechar'})).toBeVisible();
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

test('responses and analytics expose mobile/touch equivalents',async({page})=>{
  await page.setViewportSize(viewports.phone);
  await page.goto(base+'?view=responses&submission=1');
  const detail=page.locator('#selected-response');
  await expect(detail).toBeVisible();
  await expect(detail).toBeFocused();

  await page.goto(base+'?view=analytics');
  const bar=page.locator('.analytics-bar').first();
  await expect(bar).toHaveAttribute('tabindex','0');
  await bar.focus();
  await expect(bar).toBeFocused();
  const opacity=await bar.locator('i').evaluate(el=>getComputedStyle(el).opacity);
  expect(Number(opacity)).toBe(1);
});

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
    const shortNavTargets=await sidebar.locator('.admin-nav-links a').evaluateAll(nodes=>nodes.map(el=>({text:(el.textContent||'').trim(),height:el.getBoundingClientRect().height})).filter(item=>item.height<39.5));
    expect(shortNavTargets,device+'/drawer navigation touch targets').toEqual([]);
    const focusables=sidebar.locator('a[href],button:not([disabled])');
    const focusableCount=await focusables.count();
    await focusables.nth(focusableCount-1).focus();
    await page.keyboard.press('Tab');
    await expect(focusables.first()).toBeFocused();
    await page.keyboard.press('Shift+Tab');
    await expect(focusables.nth(focusableCount-1)).toBeFocused();
    const out=path.join('test-results','admin-complete-audit',device);
    fs.mkdirSync(out,{recursive:true});
    await page.screenshot({path:path.join(out,'drawer-open.png'),fullPage:true,animations:'disabled'});

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
