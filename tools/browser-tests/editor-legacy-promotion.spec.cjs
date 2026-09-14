const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/editor-legacy-promotion.html';

async function fixture(page){
  await page.setViewportSize({width:1280,height:900});
  await page.goto(url);
  const frame=page.frameLocator('#page-frame');
  await expect(frame.locator('#legacy-paragraph')).toBeVisible();
  return frame;
}

async function openTree(page){
  await page.locator('[data-structure-view="tree"]').click();
  await expect(page.locator('#page-structure-tree')).toBeVisible();
}

test('legacy content enters the structure only when touched',async({page})=>{
  const frame=await fixture(page);
  expect(await frame.locator('#legacy-paragraph').getAttribute('data-cms-component')).toBeNull();
  expect(await frame.locator('#legacy-heading').getAttribute('data-cms-component')).toBeNull();
  await openTree(page);
  await expect(page.locator('#page-structure-tree')).toContainText('Já estrutural.');
  await expect(page.locator('#page-structure-tree')).not.toContainText('Parágrafo antigo ainda editável.');

  await frame.locator('#legacy-paragraph').dispatchEvent('pointerdown');
  await expect(frame.locator('#legacy-paragraph')).toHaveAttribute('data-cms-component','paragraph');
  await expect(frame.locator('#legacy-paragraph')).toHaveAttribute('data-cms-node-id',/legacy-paragraph-/);
  await expect(page.locator('#page-structure-tree')).toContainText('Parágrafo antigo ainda editável.');
  await expect(frame.locator('#legacy-heading')).not.toHaveAttribute('data-cms-component','heading');
});

test('promotion supports semantic legacy nodes and excludes form content',async({page})=>{
  const frame=await fixture(page);
  for(const [selector,type] of [['#legacy-heading','heading'],['#legacy-quote','quote'],['#legacy-list','list'],['#legacy-image','image']]){
    await frame.locator(selector).dispatchEvent('pointerdown');
    await expect(frame.locator(selector)).toHaveAttribute('data-cms-component',type);
  }
  await frame.locator('#form-copy').dispatchEvent('pointerdown');
  expect(await frame.locator('#form-copy').getAttribute('data-cms-component')).toBeNull();
  await openTree(page);
  await expect(page.locator('#page-structure-tree')).toContainText('Título antigo');
  await expect(page.locator('#page-structure-tree')).toContainText('Citação');
  await expect(page.locator('#page-structure-tree')).toContainText('Lista');
  await expect(page.locator('#page-structure-tree')).toContainText('Imagem');
});

test('promoted attributes persist through save and existing nodes are untouched',async({page})=>{
  const frame=await fixture(page);
  await frame.locator('#legacy-paragraph').dispatchEvent('pointerdown');
  const nodeId=await frame.locator('#legacy-paragraph').getAttribute('data-cms-node-id');
  expect(nodeId).toMatch(/^legacy-paragraph-/);
  await page.locator('#save-page').click();
  await expect(frame.locator('#legacy-paragraph')).toHaveAttribute('data-cms-component','paragraph');
  await expect(frame.locator('#legacy-paragraph')).toHaveAttribute('data-cms-node-id',nodeId);
  await expect(frame.locator('#first-class')).toHaveAttribute('data-cms-node-id','existing-node');
  await frame.locator('#first-class').dispatchEvent('pointerdown');
  await expect(frame.locator('#first-class')).toHaveAttribute('data-cms-node-id','existing-node');
});
