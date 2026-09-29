const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/admin-mobile-overflow.html';

async function expectInsideViewport(page,selector){
  const box=await page.locator(selector).boundingBox();
  expect(box).not.toBeNull();
  expect(box.x).toBeGreaterThanOrEqual(-0.5);
  expect(box.x+box.width).toBeLessThanOrEqual((await page.viewportSize()).width+0.5);
}

for(const viewport of [{width:390,height:844},{width:768,height:900}]){
  test(`admin cards stay inside ${viewport.width}px viewport and wide tables scroll locally`,async({page})=>{
    await page.setViewportSize(viewport);
    await page.goto(url);

    const dimensions=await page.evaluate(()=>({
      viewport:document.documentElement.clientWidth,
      document:document.documentElement.scrollWidth,
      body:document.body.scrollWidth,
    }));
    expect(dimensions.document).toBeLessThanOrEqual(dimensions.viewport);
    expect(dimensions.body).toBeLessThanOrEqual(dimensions.viewport);

    await expectInsideViewport(page,'.admin-content');
    await expectInsideViewport(page,'#update-card');
    await expectInsideViewport(page,'#diagnostic-card');
    await expectInsideViewport(page,'#install-update');
    await expect(page.locator('#install-update')).toBeVisible();

    const scroller=page.locator('#diagnostic-scroll');
    await expectInsideViewport(page,'#diagnostic-scroll');
    const scrollState=await scroller.evaluate(el=>({clientWidth:el.clientWidth,scrollWidth:el.scrollWidth,overflowX:getComputedStyle(el).overflowX}));
    expect(scrollState.scrollWidth).toBeGreaterThan(scrollState.clientWidth);
    expect(['auto','scroll']).toContain(scrollState.overflowX);
  });
}
