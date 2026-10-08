const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/editor-inspector-coherence.html?page=1';

test('section inspector is grouped by editorial responsibility',async({page})=>{
  await page.goto(url);
  const inspector=page.locator('#inspector');
  await expect(inspector.locator('[data-inspector-group="identity"]')).toContainText('Identidade');
  await expect(inspector.locator('[data-inspector-group="layout"]')).toContainText('Layout');
  await expect(inspector.locator('[data-inspector-group="audience"]')).toContainText('Audiência');
  await expect(inspector.locator('[data-inspector-group="availability"]')).toContainText('Disponibilidade');
  await expect(inspector.locator('[data-inspector-group="identity"] #s-name')).toHaveCount(1);
  await expect(inspector.locator('[data-inspector-group="layout"] #s-width')).toHaveCount(1);
  await expect(inspector.locator('[data-inspector-group="audience"] #cms-access-audience')).toHaveCount(1);
  await expect(inspector.locator('[data-inspector-group="availability"] #cms-access-availability')).toHaveCount(1);
  await expect(inspector.locator('[data-cms-access-controls]')).toHaveClass(/is-consumed/);
});

test('moving controls into coherent groups preserves their existing listeners',async({page})=>{
  await page.goto(url);
  const name=page.locator('#inspector [data-inspector-group="identity"] #s-name');
  await name.fill('Aula 1 revisada');
  await name.dispatchEvent('change');
  await expect.poll(()=>page.evaluate(()=>window.fixtureChanges)).toBe(1);
});


test('page inspector keeps one access control under concurrent mutations and puts global actions last',async({page})=>{
  await page.goto(url+'&mode=page');
  const inspector=page.locator('#inspector');
  await expect(inspector.locator('[data-inspector-group="page-identity"]')).toContainText('Identidade');
  await expect(inspector.locator('[data-inspector-group="page-navigation"]')).toContainText('Navegação e aparência');
  await expect(inspector.locator('[data-inspector-group="page-seo"]')).toContainText('SEO e compartilhamento');
  await expect(inspector.locator('[data-inspector-group="page-audience"]')).toContainText('Audiência');
  await expect(inspector.locator('[data-inspector-group="page-global"]')).toContainText('Configurações globais');
  await expect.poll(()=>inspector.locator('[data-cms-page-access]').count()).toBe(1);
  await expect(inspector.locator('#cms-page-access')).toHaveCount(1);
  await expect(inspector.locator('[data-inspector-group="page-audience"] #cms-page-access')).toHaveCount(1);
  await expect(inspector.locator('[data-inspector-group="page-global"] .cms-inspector-global-links')).toContainText('Design global');
  await expect(inspector.locator('[data-inspector-group="page-global"] .cms-inspector-global-links')).toContainText('Header e footer');
  const order=await inspector.locator('.cms-inspector-group').evaluateAll(nodes=>nodes.map(node=>node.dataset.inspectorGroup));
  expect(order).toEqual(['page-identity','page-navigation','page-seo','page-audience','page-global']);
});
