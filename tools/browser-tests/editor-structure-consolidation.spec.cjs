const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/editor-structure-consolidation.html';

async function openFixture(page,suffix=''){
  await page.goto(url+suffix);
  const frame=page.frameLocator('#page-frame');
  await expect(frame.locator('[data-cms-page-main]')).toBeVisible();
  return frame;
}

test('structure is the default navigator and section creation uses one library',async({page})=>{
  await openFixture(page);

  await expect(page.locator('[data-structure-view="tree"]')).toHaveClass(/is-active/);
  await expect(page.locator('#page-structure-tree')).toBeVisible();
  await expect(page.locator('#sections-list')).toBeHidden();
  await expect.poll(()=>page.evaluate(()=>sessionStorage.getItem('cms-structure-sidebar-view'))).toBe('tree');

  await expect(page.locator('#pro-add-component')).toBeHidden();
  await page.locator('#add-section-side').click();
  await expect(page.locator('#pro-components-dialog')).toBeVisible();
  await expect(page.locator('#pro-components-dialog h2')).toHaveText('Adicionar seção');
  await expect(page.locator('[data-pro-tab="components"]')).toHaveText('Seções prontas');
  await expect(page.locator('[data-pro-component="free2"]')).toBeHidden();
  await expect(page.locator('[data-pro-component="free3"]')).toBeHidden();
  await expect(page.locator('[data-pro-component="free4"]')).toBeHidden();
  await expect(page.locator('[data-modern-free-section]')).toBeVisible();
  await expect(page.locator('body')).toHaveAttribute('data-open-count','1');

  await expect(page.locator('#editor-more .editor-more-menu > #pro-save-block')).toHaveCount(1);
  await expect(page.locator('#pro-save-block')).toHaveText('Salvar seção como bloco');
});

test('legacy free grid is folded away and can be upgraded without losing content',async({page})=>{
  const frame=await openFixture(page);

  const details=page.locator('.cms-layout-legacy-details');
  await expect(details).toBeVisible();
  await expect(details).not.toHaveAttribute('open','');
  await expect(page.locator('[data-upgrade-free-grid]')).toBeVisible();

  await page.locator('[data-upgrade-free-grid]').click();

  const grid=frame.locator('#legacy-grid');
  await expect(grid).toHaveClass(/cms-container-columns/);
  await expect(grid).not.toHaveClass(/cms-free-grid/);
  await expect(grid).toHaveAttribute('data-cms-container','columns');
  await expect(grid).toHaveAttribute('data-cms-columns','2');
  await expect(grid).toHaveAttribute('data-cms-ratio','40-60');
  await expect(grid.locator(':scope > [data-cms-column]')).toHaveCount(2);
  await expect(frame.locator('#legacy-a')).toHaveAttribute('data-cms-mobile-order','2');
  await expect(frame.locator('#legacy-b')).toHaveAttribute('data-cms-mobile-order','1');
  await expect(grid).toContainText('Título mantido');
  await expect(grid).toContainText('Texto A');
  await expect(grid).toContainText('Texto B');
  await expect(frame.locator('section[data-cms-section="legacy"]')).not.toHaveAttribute('data-cms-columns','2');
  await expect(page.locator('body')).toHaveAttribute('data-save-count','1');
});

test('legacy custom column widths can be upgraded and preserved',async({page})=>{
  const frame=await openFixture(page,'?spans=1');

  await expect(page.locator('[data-upgrade-free-grid]')).toBeVisible();
  await expect(page.locator('.cms-layout-consolidation-card')).toContainText('larguras personalizadas');
  await page.locator('[data-upgrade-free-grid]').click();

  const grid=frame.locator('#legacy-grid');
  await expect(grid).toHaveClass(/cms-container-columns/);
  await expect(grid).toHaveAttribute('data-cms-columns','4');
  await expect(frame.locator('#legacy-a')).toHaveAttribute('data-cms-column','');
  await expect(frame.locator('#legacy-a')).toHaveAttribute('data-cms-span','1');
  await expect(frame.locator('#legacy-b')).toHaveAttribute('data-cms-span','3');
  await expect(grid).toContainText('Título mantido');
  await expect(grid).toContainText('Texto B');
  await expect(frame.locator('section[data-cms-section="legacy"]')).not.toHaveAttribute('data-cms-columns','4');
  await expect(page.locator('body')).toHaveAttribute('data-save-count','1');
});

test('new free sections start on the current structural model',async({page})=>{
  const frame=await openFixture(page);
  await page.locator('#add-section').click();
  await page.locator('[data-modern-free-section]').click();

  await expect(frame.locator('section[data-cms-section-name="Seção livre"]')).toHaveCount(1);
  const container=frame.locator('section[data-cms-section-name="Seção livre"] [data-cms-container="stack"]');
  await expect(container).toHaveAttribute('data-cms-component','container');
  await expect(container.locator('[data-cms-component="heading"]')).toHaveText('Novo título');
  await expect(container.locator('[data-cms-component="paragraph"]')).toHaveText('Novo parágrafo.');
});
