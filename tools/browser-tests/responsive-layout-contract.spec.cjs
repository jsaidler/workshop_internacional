const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/responsive-layout-contract.html';
const columns=async(locator)=>locator.evaluate(el=>getComputedStyle(el).gridTemplateColumns.trim().split(/\s+/).filter(Boolean).length);

async function childWidths(locator){
  const children=locator.locator(':scope > div');
  const first=await children.nth(0).boundingBox();
  const second=await children.nth(1).boundingBox();
  return [first.width,second.width];
}

test('desktop choices remain desktop choices while smaller screens adapt automatically',async({page})=>{
  await page.setViewportSize({width:1440,height:900});
  await page.goto(url);
  const legacy=page.locator('#legacy-ratio .cms-proof-grid');
  const builder=page.locator('#builder-columns .cms-grid');
  expect(await columns(legacy)).toBe(2);
  const [desktopFirst,desktopSecond]=await childWidths(legacy);
  expect(desktopSecond).toBeGreaterThan(desktopFirst*1.8);
  expect(await columns(builder)).toBe(4);

  await page.setViewportSize({width:900,height:900});
  expect(await columns(legacy)).toBe(1);
  expect(await columns(builder)).toBe(1);

  await page.setViewportSize({width:390,height:844});
  expect(await columns(legacy)).toBe(1);
  expect(await columns(builder)).toBe(1);
});

test('tablet and phone overrides are explicit and independent from desktop ratio',async({page})=>{
  await page.setViewportSize({width:900,height:900});
  await page.goto(url);
  const tablet=page.locator('#tablet-override .cms-proof-grid');
  expect(await columns(tablet)).toBe(2);
  const [tabletFirst,tabletSecond]=await childWidths(tablet);
  expect(tabletSecond/tabletFirst).toBeGreaterThan(1.35);
  expect(tabletSecond/tabletFirst).toBeLessThan(1.7);

  await page.setViewportSize({width:390,height:844});
  const mobile=page.locator('#mobile-override .cms-proof-grid');
  expect(await columns(mobile)).toBe(2);
  const [mobileFirst,mobileSecond]=await childWidths(mobile);
  expect(mobileSecond).toBeGreaterThan(mobileFirst*1.8);
});
