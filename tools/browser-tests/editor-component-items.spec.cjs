const {test,expect}=require('@playwright/test');
const fixtureUrl='http://127.0.0.1:8099/tools/browser-fixture/editor-inline-insert.html';

async function setup(page){
  page.on('dialog',dialog=>dialog.accept());
  await page.goto(fixtureUrl);
  const frame=page.frameLocator('#page-frame');
  await expect(frame.locator('section[data-cms-section]').first()).toBeVisible();
  await expect(frame.locator('#cms-inline-insert-style')).toHaveCount(1);
  return frame;
}

test('removes selected middle list item without damaging siblings',async({page})=>{
  const frame=await setup(page);
  await frame.locator('section[data-cms-section]').first().evaluate(el=>el.insertAdjacentHTML('beforeend',
    '<div id="audit-list" data-cms-component="list"><ul><li data-cms-editable>Primeiro</li><li data-cms-editable>Segundo</li><li data-cms-editable>Terceiro</li></ul></div>'));
  await frame.locator('#audit-list').evaluate(el=>el.dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true})));
  await expect(page.locator('#inspector [data-rich-list-item]')).toBeVisible();
  await page.locator('#inspector [data-rich-list-item]').selectOption('1');
  await page.locator('#inspector [data-rich-list-remove]').click();
  await expect(frame.locator('#audit-list li')).toHaveCount(2);
  await expect(frame.locator('#audit-list')).toContainText('Primeiro');
  await expect(frame.locator('#audit-list')).toContainText('Terceiro');
  await expect(frame.locator('#audit-list')).not.toContainText('Segundo');
});

test('removes selected middle gallery item without removing last item',async({page})=>{
  const frame=await setup(page);
  await frame.locator('section[data-cms-section]').first().evaluate(el=>el.insertAdjacentHTML('beforeend',
    '<div id="audit-gallery" data-cms-component="gallery"><div class="cms-gallery-item" id="first"><span>Uma</span></div><div class="cms-gallery-item" id="second"><span>Duas</span></div><div class="cms-gallery-item" id="third"><span>Três</span></div></div>'));
  await frame.locator('#audit-gallery').evaluate(el=>el.dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true})));
  await expect(page.locator('#inspector [data-rich-gallery-item]')).toBeVisible();
  await page.locator('#inspector [data-rich-gallery-item]').selectOption('1');
  await page.locator('#inspector [data-rich-gallery-remove]').click();
  await expect(frame.locator('#audit-gallery .cms-gallery-item')).toHaveCount(2);
  await expect(frame.locator('#audit-gallery #first')).toHaveCount(1);
  await expect(frame.locator('#audit-gallery #second')).toHaveCount(0);
  await expect(frame.locator('#audit-gallery #third')).toHaveCount(1);
});
