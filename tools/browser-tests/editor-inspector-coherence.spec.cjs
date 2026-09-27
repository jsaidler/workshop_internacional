const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/editor-inspector-coherence.html';

async function openFixture(page){
  await page.goto(url,{waitUntil:'domcontentloaded'});
  await expect(page.locator('#inspector')).toBeVisible();
}

test('section inspector is grouped by editorial responsibility',async({page})=>{
  await openFixture(page);
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
  await openFixture(page);
  const name=page.locator('#inspector [data-inspector-group="identity"] #s-name');
  await name.fill('Aula 1 revisada');
  await name.dispatchEvent('change');
  await expect.poll(()=>page.evaluate(()=>window.fixtureChanges)).toBe(1);
});
