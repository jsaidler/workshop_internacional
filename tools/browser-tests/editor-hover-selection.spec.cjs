const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/editor-hover-selection.html';

test('hover preview matches the element the editor would select',async({page})=>{
  await page.goto(url);
  const frame=page.frameLocator('#page-frame');
  const label=frame.locator('.cms-editor-hover-label');

  await frame.locator('#editable').hover();
  await expect(label).toHaveText('Texto · Parágrafo editável');

  await frame.locator('#divider').hover();
  await expect(label).toHaveText('Divisor');

  await frame.locator('#blank').hover();
  await expect(label).toHaveText('Seção · Aula 1 · Ponto de partida');

  const canonical=await frame.locator('[data-cms-page-main]').evaluate(node=>node.innerHTML);
  expect(canonical).not.toContain('cms-editor-hover');
  expect(canonical).not.toContain('data-cms-editor-ui');
});
