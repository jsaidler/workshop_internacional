const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/content-structure.html';
const columnCount=async(locator)=>locator.evaluate(el=>getComputedStyle(el).gridTemplateColumns.trim().split(/\s+/).filter(Boolean).length);
const widths=async(locator)=>{const items=locator.locator(':scope > .cms-column');const a=await items.nth(0).boundingBox();const b=await items.nth(1).boundingBox();return[a.width,b.width]};

test('container desktop choices do not leak into automatic tablet or phone layout',async({page})=>{
  await page.setViewportSize({width:1440,height:900});
  await page.goto(url);
  const automatic=page.locator('#automatic');
  expect(await columnCount(automatic)).toBe(2);
  const [deskA,deskB]=await widths(automatic);
  expect(deskB/deskA).toBeGreaterThan(2.1);
  expect(await automatic.evaluate(el=>getComputedStyle(el).gap)).toBe('48px');

  await page.setViewportSize({width:800,height:900});
  expect(await columnCount(automatic)).toBe(1);
  expect(await automatic.evaluate(el=>getComputedStyle(el).gap)).toBe('24px');

  await page.setViewportSize({width:390,height:844});
  expect(await columnCount(automatic)).toBe(1);
  expect(await automatic.evaluate(el=>getComputedStyle(el).gap)).toBe('24px');
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
  expect(await page.locator('#stack').evaluate(el=>getComputedStyle(el).gap)).toBe('18px');
});
