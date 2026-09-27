const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/editor-structure-selection-authority.html';

test('canonical structure selection boundary dispatches structural and section selection',async({page})=>{
  await page.goto(url);
  await expect.poll(()=>page.evaluate(()=>document.querySelector('#page-frame').contentDocument?.readyState)).toBe('complete');

  const result=await page.evaluate(()=>{
    const doc=document.querySelector('#page-frame').contentDocument;
    const paragraph=doc.querySelector('[data-cms-node-id="paragraph-1"]');
    const section=doc.querySelector('[data-cms-section="aula-1"]');
    let structuralClicks=0,sectionClicks=0;
    paragraph.addEventListener('click',event=>{structuralClicks++;event.stopPropagation()});
    section.addEventListener('click',()=>sectionClicks++);
    return {
      structuralSelected:window.CmsEditorStructure.select(paragraph),
      sectionSelected:window.CmsEditorStructure.selectSection(section),
      structuralClicks,
      sectionClicks
    };
  });

  expect(result.structuralSelected).toBe(true);
  expect(result.sectionSelected).toBe(true);
  expect(result.structuralClicks).toBe(1);
  expect(result.sectionClicks).toBe(1);
});

test('selection boundary rejects nodes outside CMS structure',async({page})=>{
  await page.goto(url);
  await expect.poll(()=>page.evaluate(()=>document.querySelector('#page-frame').contentDocument?.readyState)).toBe('complete');
  const rejected=await page.evaluate(()=>window.CmsEditorStructure.select(document.body));
  expect(rejected).toBe(false);
});
