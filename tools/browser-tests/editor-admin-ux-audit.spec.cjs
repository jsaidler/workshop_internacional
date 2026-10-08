const {test,expect}=require('@playwright/test');
const fs=require('fs');
const path=require('path');
const url='http://127.0.0.1:8099/tools/browser-fixture/editor-admin-ux-audit.html?page=1';
const viewports={phone:{width:390,height:844},tablet:{width:768,height:1024},compact:{width:1280,height:800},wide:{width:1600,height:900}};
async function noOverflow(page,label){
  const d=await page.evaluate(()=>({client:document.documentElement.clientWidth,doc:document.documentElement.scrollWidth,body:document.body.scrollWidth}));
  expect(d.doc,label+': document overflow').toBeLessThanOrEqual(d.client+1);
  expect(d.body,label+': body overflow').toBeLessThanOrEqual(d.client+1);
}
const inspectorStates=['empty','page','section'];
for(const [name,viewport] of Object.entries(viewports)){
  for(const state of inspectorStates){
    test('editor complete layout audit '+name+' '+state,async({page})=>{
      await page.setViewportSize(viewport);
      await page.goto(url+'&state='+state);
      await noOverflow(page,name+'/'+state);
      const canvas=page.locator('.editor-canvas');
      const canvasBox=await canvas.boundingBox();
      expect(canvasBox).not.toBeNull();
      if(viewport.width<=1024){
        await expect(page.locator('#editor-structure-mobile')).toBeVisible();
        expect(canvasBox.width).toBeGreaterThan(viewport.width*.92);
        const panelBox=await page.locator('#editor-structure-panel').boundingBox();
        expect(panelBox.x).toBeLessThan(0);
        if(state==='empty'){
          await expect(page.locator('#inspector')).toBeHidden();
        }else{
          const inspectorStyle=await page.locator('#inspector').evaluate(el=>({position:getComputedStyle(el).position,overflowY:getComputedStyle(el).overflowY}));
          expect(inspectorStyle.position).toBe('fixed');
          expect(['auto','scroll']).toContain(inspectorStyle.overflowY);
          await expect(page.locator('#inspector')).toBeVisible();
        }
      }else{
        await expect(page.locator('#editor-structure-mobile')).toBeHidden();
        expect(canvasBox.width).toBeGreaterThan(viewport.width*.45);
        await expect(page.locator('#editor-structure-panel')).toBeVisible();
        await expect(page.locator('#inspector')).toBeVisible();
      }
      const out=path.join('test-results','admin-complete-audit','editor');
      fs.mkdirSync(out,{recursive:true});
      await page.screenshot({path:path.join(out,name+'-'+state+'.png'),fullPage:true,animations:'disabled'});
    });
  }
}
for(const name of ['phone','tablet']){
  test('editor structure drawer visual and keyboard audit '+name,async({page})=>{
    const viewport=viewports[name];
    await page.setViewportSize(viewport);
    await page.goto(url+'&state=empty');
    await page.locator('#editor-structure-mobile').click();
    await expect(page.locator('body')).toHaveClass(/structure-mobile-open/);
    await expect(page.locator('#editor-structure-backdrop')).toBeVisible();
    await expect(page.locator('#editor-structure-mobile-close')).toBeFocused();
    const out=path.join('test-results','admin-complete-audit','editor');
    fs.mkdirSync(out,{recursive:true});
    await page.screenshot({path:path.join(out,name+'-structure-open.png'),fullPage:true,animations:'disabled'});
    await page.keyboard.press('Escape');
    await expect(page.locator('body')).not.toHaveClass(/structure-mobile-open/);
    await expect(page.locator('#editor-structure-mobile')).toBeFocused();
  });
}
test('editor canonical CSS declarations are consumed by the browser',async({page})=>{
  await page.setViewportSize({width:1280,height:800});
  await page.goto(url+'&state=page');
  const styles=await page.evaluate(()=>({
    barPosition:getComputedStyle(document.querySelector('.cms-editor-bar')).position,
    wordmarkDecoration:getComputedStyle(document.querySelector('.editor-wordmark')).textDecorationLine,
    pageMetaOpacity:getComputedStyle(document.querySelector('.editor-page-title span')).opacity,
    actionsJustify:getComputedStyle(document.querySelector('.editor-actions')).justifyContent,
    canvasOverflow:getComputedStyle(document.querySelector('.editor-canvas')).overflowX,
    frameTransition:getComputedStyle(document.querySelector('.device-frame')).transitionProperty,
    textareaResize:getComputedStyle(document.querySelector('.inspector textarea')).resize,
    floatingPosition:getComputedStyle(document.querySelector('.floating-format')).position,
    dialogJustify:getComputedStyle(document.querySelector('.editor-dialog header')).justifyContent,
    templateAlign:getComputedStyle(document.querySelector('.section-template')).textAlign,
    tabCursor:getComputedStyle(document.querySelector('.pro-tabs button')).cursor,
    mediaMaxHeight:getComputedStyle(document.querySelector('.media-grid')).maxHeight,
    mediaAlign:getComputedStyle(document.querySelector('.media-item')).textAlign,
    toastPosition:getComputedStyle(document.querySelector('.editor-toast')).position,
    toastOpacity:getComputedStyle(document.querySelector('.editor-toast')).opacity
  }));
  expect(styles.barPosition).toBe('relative');
  expect(styles.wordmarkDecoration).toBe('none');
  expect(Number(styles.pageMetaOpacity)).toBeLessThan(1);
  expect(styles.actionsJustify).toBe('flex-end');
  expect(['auto','scroll']).toContain(styles.canvasOverflow);
  expect(styles.frameTransition).toContain('width');
  expect(styles.textareaResize).toBe('vertical');
  expect(styles.floatingPosition).toBe('fixed');
  expect(styles.dialogJustify).toBe('space-between');
  expect(styles.templateAlign).toBe('left');
  expect(styles.tabCursor).toBe('pointer');
  expect(styles.mediaMaxHeight).not.toBe('none');
  expect(styles.mediaAlign).toBe('left');
  expect(styles.toastPosition).toBe('fixed');
  expect(Number(styles.toastOpacity)).toBe(0);
});
