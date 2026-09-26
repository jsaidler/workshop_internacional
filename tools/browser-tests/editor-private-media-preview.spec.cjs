const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/editor-private-media-preview.html';

test('editor shows pending and bound private media without changing canonical page markup',async({page})=>{
  await page.goto(url);
  const frame=page.frameLocator('#page-frame');
  const pending=frame.locator('[data-cms-editor-ui="private-media-preview"][data-private-media-slot="pendente"]');
  const bound=frame.locator('[data-cms-editor-ui="private-media-preview"][data-private-media-slot="vinculado"]');
  await expect(pending).toContainText('Infográfico pendente');
  await expect(pending).toContainText('Diagrama de exposição');
  await expect(pending).toContainText('slot: pendente');
  await expect(bound.locator('img')).toHaveCount(1);
  await expect(bound).toContainText('Mídia privada vinculada');

  const canonical=await frame.locator('[data-cms-page-main]').evaluate(node=>node.innerHTML);
  expect(canonical).not.toContain('Infográfico pendente');
  expect(canonical).not.toContain('Mídia privada vinculada');
  expect(canonical).not.toContain('data-cms-editor-ui');
  expect(canonical).toContain('data-private-media-slot="pendente"');
  expect(canonical).toContain('data-private-media-slot="vinculado"');
});
