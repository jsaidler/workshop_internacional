const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/editor-section-coherence.html';

test('sections panel lists CMS sections nested inside layout wrappers',async({page})=>{
  await page.goto(url);
  const rows=page.locator('#sections-list [data-cms-coherent-section]');
  await expect(rows).toHaveCount(3);
  await expect(rows.nth(0)).toContainText('Alpha');
  await expect(rows.nth(1)).toContainText('Beta');
  await expect(rows.nth(2)).toContainText('Gamma');

  await rows.nth(1).locator('[data-select-section]').click();
  await expect(page.frameLocator('#page-frame').locator('[data-cms-section="beta"]')).toHaveClass(/cms-section-selected/);
});

test('section reordering is limited to sections with the same DOM parent',async({page})=>{
  await page.goto(url);
  const rows=page.locator('#sections-list [data-cms-coherent-section]');
  await expect(rows).toHaveCount(3);

  await rows.nth(1).dragTo(rows.nth(0));
  const frame=page.frameLocator('#page-frame');
  await expect.poll(()=>frame.locator('#lesson-group > [data-cms-section]').evaluateAll(nodes=>nodes.map(node=>node.dataset.cmsSection).join(','))).toBe('beta,alpha');

  const refreshed=page.locator('#sections-list [data-cms-coherent-section]');
  await refreshed.filter({hasText:'Gamma'}).dragTo(refreshed.filter({hasText:'Alpha'}));
  await expect.poll(()=>frame.locator('#other-group > [data-cms-section]').evaluateAll(nodes=>nodes.map(node=>node.dataset.cmsSection).join(','))).toBe('gamma');
  await expect.poll(()=>frame.locator('#lesson-group > [data-cms-section]').evaluateAll(nodes=>nodes.map(node=>node.dataset.cmsSection).join(','))).toBe('beta,alpha');
});
