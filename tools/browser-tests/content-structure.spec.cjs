const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/content-structure.html';
const columnCount=async(locator)=>locator.evaluate(el=>getComputedStyle(el).gridTemplateColumns.trim().split(/\s+/).filter(Boolean).length);
const widths=async(locator)=>{const items=locator.locator(':scope > .cms-column');const a=await items.nth(0).boundingBox();const b=await items.nth(1).boundingBox();return[a.width,b.width]};

test('container desktop choices do not leak into automatic tablet or phone layout',async({page})=>{
  await page.setViewportSize({width:1440,height:900});
  await page.goto(url);
  const automatic=page.locator('#automatic');
  const stackAuto=page.locator('#stack-auto');
  expect(await columnCount(automatic)).toBe(2);
  const [deskA,deskB]=await widths(automatic);
  expect(deskB/deskA).toBeGreaterThan(2.1);
  expect(await automatic.evaluate(el=>getComputedStyle(el).gap)).toBe('48px');
  expect(await stackAuto.evaluate(el=>getComputedStyle(el).gap)).toBe('48px');

  // 950px belongs to the canonical tablet range. This catches the old
  // transitional 900px breakpoint used by the first-class container CSS.
  await page.setViewportSize({width:950,height:900});
  expect(await columnCount(automatic)).toBe(1);
  expect(await automatic.evaluate(el=>getComputedStyle(el).gap)).toBe('24px');
  expect(await stackAuto.evaluate(el=>getComputedStyle(el).gap)).toBe('24px');

  await page.setViewportSize({width:800,height:900});
  expect(await columnCount(automatic)).toBe(1);
  expect(await automatic.evaluate(el=>getComputedStyle(el).gap)).toBe('24px');
  expect(await stackAuto.evaluate(el=>getComputedStyle(el).gap)).toBe('24px');

  await page.setViewportSize({width:390,height:844});
  expect(await columnCount(automatic)).toBe(1);
  expect(await automatic.evaluate(el=>getComputedStyle(el).gap)).toBe('24px');
  expect(await stackAuto.evaluate(el=>getComputedStyle(el).gap)).toBe('24px');
});

test('tablet and mobile container overrides are independent',async({page})=>{
  await page.setViewportSize({width:800,height:900});
  await page.goto(url);
  const explicit=page.locator('#explicit');
  expect(await columnCount(explicit)).toBe(2);
  const [tabletA,tabletB]=await widths(explicit);
  expect(tabletB/tabletA).toBeGreaterThan(1.35);
  expect(tabletB/tabletA).toBeLessThan(1.7);
  expect(await page.locator('#stack').evaluate(el=>getComputedStyle(el).gap)).toBe('12px');

  await page.setViewportSize({width:390,height:844});
  expect(await columnCount(explicit)).toBe(2);
  const [mobileA,mobileB]=await widths(explicit);
  expect(mobileB/mobileA).toBeGreaterThan(2.1);
  expect(await page.locator('#stack').evaluate(el=>getComputedStyle(el).gap)).toBe('24px');
});

test('individual column width is independent on desktop tablet and phone',async({page})=>{
  await page.setViewportSize({width:1440,height:900});
  await page.goto(url);
  const spans=page.locator('#spans');
  const a=page.locator('#span-a');
  const b=page.locator('#span-b');
  expect(await columnCount(spans)).toBe(4);
  let boxA=await a.boundingBox(),boxB=await b.boundingBox();
  expect(boxA.width/boxB.width).toBeGreaterThan(2.7);
  expect(boxA.width/boxB.width).toBeLessThan(3.3);

  const clamped=page.locator('#clamped');
  const clampedA=page.locator('#clamped-a');
  expect(await columnCount(clamped)).toBe(2);
  const clampGrid=await clamped.boundingBox(),clampA=await clampedA.boundingBox();
  expect(Math.abs(clampGrid.width-clampA.width)).toBeLessThan(2);

  await page.setViewportSize({width:800,height:900});
  expect(await columnCount(spans)).toBe(3);
  boxA=await a.boundingBox();boxB=await b.boundingBox();
  expect(boxA.width/boxB.width).toBeGreaterThan(1.7);
  expect(boxA.width/boxB.width).toBeLessThan(2.3);

  await page.setViewportSize({width:390,height:844});
  expect(await columnCount(spans)).toBe(2);
  boxA=await a.boundingBox();boxB=await b.boundingBox();
  expect(boxA.width/boxB.width).toBeGreaterThan(.9);
  expect(boxA.width/boxB.width).toBeLessThan(1.1);
});

test('container appearance and column behavior have independent viewport controls',async({page})=>{
  await page.setViewportSize({width:1440,height:900});
  await page.goto(url);
  const box=page.locator('#properties');
  const a=page.locator('#property-a');
  const b=page.locator('#property-b');
  const desktopBox=await box.boundingBox();
  expect(desktopBox.width).toBeLessThanOrEqual(761);
  expect(await box.evaluate(el=>getComputedStyle(el).paddingTop)).toBe('88px');
  expect(await box.evaluate(el=>getComputedStyle(el).alignItems)).toBe('end');
  expect(await a.evaluate(el=>getComputedStyle(el).order)).toBe('2');
  expect(await b.evaluate(el=>getComputedStyle(el).order)).toBe('1');
  expect(await a.evaluate(el=>getComputedStyle(el).textAlign)).toBe('right');
  expect(await b.evaluate(el=>getComputedStyle(el).display)).not.toBe('none');

  await page.setViewportSize({width:800,height:900});
  expect(await box.evaluate(el=>getComputedStyle(el).paddingTop)).toBe('24px');
  expect(await box.evaluate(el=>getComputedStyle(el).alignItems)).toBe('center');
  expect(await a.evaluate(el=>getComputedStyle(el).order)).toBe('1');
  expect(await b.evaluate(el=>getComputedStyle(el).order)).toBe('2');
  expect(await a.evaluate(el=>getComputedStyle(el).textAlign)).toBe('center');
  expect(await b.evaluate(el=>getComputedStyle(el).display)).not.toBe('none');

  await page.setViewportSize({width:390,height:844});
  expect(await box.evaluate(el=>getComputedStyle(el).paddingTop)).toBe('10px');
  expect(await box.evaluate(el=>getComputedStyle(el).alignItems)).toBe('stretch');
  expect(await a.evaluate(el=>getComputedStyle(el).order)).toBe('2');
  expect(await a.evaluate(el=>getComputedStyle(el).textAlign)).toBe('left');
  expect(await b.evaluate(el=>getComputedStyle(el).display)).toBe('none');
});
