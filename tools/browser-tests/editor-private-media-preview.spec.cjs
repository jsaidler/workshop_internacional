const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/editor-private-media-preview.html';

test('private media slot is a real editor target without becoming a public image component',async({page})=>{
  await page.setViewportSize({width:1320,height:900});
  await page.goto(url);
  const frame=page.frameLocator('#page-frame');
  const pending=frame.locator('[data-cms-editor-ui="private-media-preview"][data-private-media-slot="pendente"]');
  const bound=frame.locator('[data-cms-editor-ui="private-media-preview"][data-private-media-slot="vinculado"]');
  await expect(pending).toContainText('Infográfico pendente');
  await expect(pending).toContainText('Diagrama de exposição');
  await expect(pending).toContainText('Escolher imagem');
  await expect(bound.locator('img')).toHaveCount(1);
  await expect(bound).toContainText('Imagem privada vinculada');

  await pending.click();
  await expect(page.locator('#inspector')).toContainText('Imagem privada');
  await expect(page.locator('#inspector')).toContainText('Diagrama de exposição');
  await expect(page.locator('#inspector')).toContainText('slot: pendente');
  await expect(page.locator('#inspector button',{hasText:'Escolher imagem'})).toBeVisible();
  await expect(frame.locator('#pending')).not.toHaveAttribute('data-cms-component','image');

  await page.locator('#inspector button',{hasText:'Escolher imagem'}).click();
  const dialog=page.locator('.cms-private-media-dialog');
  await expect(dialog).toBeVisible();
  await expect(dialog).toContainText('Diagrama escolhido');
  await dialog.locator('.cms-private-media-choice',{hasText:'Diagrama escolhido'}).click();
  await expect(dialog).not.toBeVisible();
  await expect(frame.locator('[data-cms-editor-ui="private-media-preview"][data-private-media-slot="pendente"] img')).toHaveCount(1);
  await expect(page.locator('#inspector')).toContainText('Imagem vinculada a este espaço.');
  await expect(page.locator('#inspector button',{hasText:'Trocar imagem'})).toBeVisible();
  await expect(frame.locator('#pending')).not.toHaveAttribute('data-cms-component','image');

  let canonical=await frame.locator('[data-cms-page-main]').evaluate(node=>node.innerHTML);
  expect(canonical).not.toContain('Infográfico pendente');
  expect(canonical).not.toContain('Imagem privada vinculada');
  expect(canonical).not.toContain('data-cms-editor-ui');
  expect(canonical).not.toContain('data-cms-image-placeholder');
  expect(canonical).toContain('data-private-media-slot="pendente"');
  expect(canonical).toContain('data-private-media-slot="vinculado"');

  await page.locator('#inspector button',{hasText:'Remover vínculo'}).click();
  await expect(frame.locator('[data-cms-editor-ui="private-media-preview"][data-private-media-slot="pendente"]')).toContainText('Infográfico pendente');
  await expect(page.locator('#inspector')).toContainText('Nenhuma imagem vinculada a este espaço.');
  canonical=await frame.locator('[data-cms-page-main]').evaluate(node=>node.innerHTML);
  expect(canonical).not.toContain('data-cms-component="image"');
});
