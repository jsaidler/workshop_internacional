const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/editor-inline-insert.html';

async function fixture(page){
  await page.setViewportSize({width:1280,height:900});
  await page.goto(url);
  const frame=page.frameLocator('#page-frame');
  await expect(frame.locator('[data-cms-page-main]')).toBeVisible();
  await page.locator('[data-structure-view="tree"]').click();
  await expect(page.locator('#page-structure-tree')).toBeVisible();
  await expect(page.locator('.cms-structure-search')).toBeVisible();
  return frame;
}

test('structure search filters long trees without losing the matching element context',async({page})=>{
  const frame=await fixture(page);
  const search=page.locator('.cms-structure-search input');
  await search.fill('Texto existente');

  await expect(page.locator('#page-structure-tree [data-tree-level="section"]')).toBeVisible();
  await expect(page.locator('#page-structure-tree [data-tree-level="node"] .cms-page-tree-main',{hasText:'Grupo de colunas'})).toBeVisible();
  await expect(page.locator('#page-structure-tree [data-tree-level="node"] .cms-page-tree-main',{hasText:'Coluna 1'})).toBeVisible();
  await expect(page.locator('#page-structure-tree [data-tree-level="node"] .cms-page-tree-main',{hasText:'Texto existente'})).toBeVisible();
  await expect(page.locator('#page-structure-tree [data-tree-level="node"] .cms-page-tree-main',{hasText:'Coluna 2'})).toBeHidden();
  await expect(page.locator('#page-structure-tree [data-tree-level="node"] .cms-page-tree-main',{hasText:'Divisor'})).toBeHidden();

  await search.fill('Divisor');
  await search.press('Enter');
  await expect(frame.locator('#divider')).toHaveClass(/cms-structure-selected/);

  await search.fill('não existe');
  await expect(page.locator('.cms-structure-search-empty')).toBeVisible();
  await search.press('Escape');
  await expect(search).toHaveValue('');
  await expect(page.locator('#page-structure-tree [data-tree-level="node"]')).toHaveCount(5);
});

test('inspector breadcrumb exposes structural location and navigates to parents',async({page})=>{
  const frame=await fixture(page);
  await page.locator('#page-structure-tree [data-tree-level="node"] .cms-page-tree-main',{hasText:'Coluna 1'}).click();
  await expect(frame.locator('#column-a')).toHaveClass(/cms-structure-selected/);

  const breadcrumb=page.locator('#inspector .cms-editor-breadcrumb');
  await expect(breadcrumb).toHaveCount(1);
  await expect(breadcrumb).toContainText('Fixture');
  await expect(breadcrumb).toContainText('Grupo de colunas');
  await expect(breadcrumb).toContainText('Coluna 1');
  await expect(breadcrumb.locator('[aria-current="page"]')).toHaveText('Coluna 1');

  await breadcrumb.locator('button',{hasText:'Grupo de colunas'}).click();
  await expect(frame.locator('#columns')).toHaveClass(/cms-structure-selected/);
  await expect(page.locator('#inspector .cms-editor-breadcrumb [aria-current="page"]')).toHaveText('Grupo de colunas');
});

test('structural elements can receive editor-only names used by tree search and breadcrumbs',async({page})=>{
  const frame=await fixture(page);
  await page.locator('#page-structure-tree [data-tree-level="node"] .cms-page-tree-main',{hasText:'Divisor'}).click();
  await expect(frame.locator('#divider')).toHaveClass(/cms-structure-selected/);

  const input=page.locator('#inspector [data-cms-editor-label-input]');
  await expect(input).toBeVisible();
  await expect(page.locator('#inspector')).toContainText('não aparece no site');
  await input.fill('Separador antes da inscrição');
  await input.press('Enter');

  await page.waitForTimeout(900);
  await expect(frame.locator('#divider')).toHaveAttribute('data-cms-editor-label','Separador antes da inscrição');
  await expect(frame.locator('#divider')).not.toContainText('Separador antes da inscrição');

  await page.locator('[data-structure-view="tree"]').click();
  await page.locator('[data-structure-view="tree"]').click();
  const tree=page.locator('#page-structure-tree');
  await expect(tree).toContainText('Separador antes da inscrição');

  const search=page.locator('.cms-structure-search input');
  await search.fill('separador antes');
  await expect(tree.locator('[data-tree-level="node"] .cms-page-tree-main',{hasText:'Separador antes da inscrição'})).toBeVisible();
  await search.press('Enter');
  await expect(frame.locator('#divider')).toHaveClass(/cms-structure-selected/);
  await expect(page.locator('#inspector .cms-editor-breadcrumb [aria-current="page"]')).toHaveText('Separador antes da inscrição');
});
