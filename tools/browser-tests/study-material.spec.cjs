const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/study-material.html';
const px=value=>Number.parseFloat(value||'0');
const gridColumns=async locator=>locator.evaluate(el=>getComputedStyle(el).gridTemplateColumns.trim().split(/\s+/).filter(Boolean).length);

async function boxes(locator){
  const out=[];
  for(let i=0;i<await locator.count();i++)out.push(await locator.nth(i).boundingBox());
  return out;
}

test('study material uses the desktop canvas without sacrificing reading measure',async({page})=>{
  await page.setViewportSize({width:1440,height:1000});
  await page.goto(url);

  const material=page.locator('#material');
  const coverTitle=page.locator('.study-cover h1');
  const unit=page.locator('#unit-reference');
  const label=page.locator('#unit-label');
  const directCopy=page.locator('#direct-copy');
  const note=page.locator('#technical-note');
  const unitGrid=page.locator('#unit-heading-grid');
  const indexGrid=page.locator('#study-index-grid');

  const unitBox=await unit.boundingBox();
  const labelBox=await label.boundingBox();
  const copyBox=await directCopy.boundingBox();
  expect(unitBox.width).toBeGreaterThan(1120);
  expect(unitBox.width).toBeLessThanOrEqual(1201);
  expect(copyBox.width).toBeGreaterThan(640);
  expect(copyBox.width).toBeLessThanOrEqual(765);
  expect(labelBox.x).toBeLessThan(copyBox.x-70);

  expect(await gridColumns(unitGrid)).toBe(2);
  expect(await gridColumns(indexGrid)).toBe(3);
  expect(px(await directCopy.evaluate(el=>getComputedStyle(el).fontSize))).toBeGreaterThanOrEqual(17.5);
  expect(px(await directCopy.evaluate(el=>getComputedStyle(el).lineHeight))).toBeGreaterThan(28);
  expect(px(await note.evaluate(el=>getComputedStyle(el).fontSize))).toBeGreaterThanOrEqual(12);
  expect(px(await coverTitle.evaluate(el=>getComputedStyle(el).fontSize))).toBeGreaterThan(70);

  const overflow=await material.evaluate(el=>el.scrollWidth-el.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);
});

test('study material keeps reference components visually distinct and keyboard navigable',async({page})=>{
  await page.setViewportSize({width:1440,height:1000});
  await page.goto(url);

  const copy=page.locator('#direct-copy');
  const note=page.locator('#technical-note');
  const cards=page.locator('#reference-grid > .format-card');
  const firstLink=page.locator('#study-index-grid a').first();

  const copyBg=await copy.evaluate(el=>getComputedStyle(el).backgroundColor);
  const noteBg=await note.evaluate(el=>getComputedStyle(el).backgroundColor);
  expect(noteBg).not.toBe(copyBg);

  const cardBoxes=await boxes(cards);
  expect(cardBoxes).toHaveLength(3);
  expect(Math.abs(cardBoxes[0].y-cardBoxes[1].y)).toBeLessThan(2);
  expect(Math.abs(cardBoxes[0].y-cardBoxes[2].y)).toBeLessThan(2);
  for(const box of cardBoxes)expect(box.width).toBeGreaterThan(190);

  await firstLink.focus();
  expect(await firstLink.evaluate(el=>getComputedStyle(el).outlineStyle)).not.toBe('none');
});

test('study material collapses to a single reading flow on small screens',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url);

  const material=page.locator('#material');
  const unit=page.locator('#unit-reference');
  const unitGrid=page.locator('#unit-heading-grid');
  const indexGrid=page.locator('#study-index-grid');
  const reference=page.locator('#reference-grid');
  const copy=page.locator('#direct-copy');
  const label=page.locator('#unit-label');

  expect(await unit.evaluate(el=>getComputedStyle(el).display)).toBe('block');
  expect(await unitGrid.evaluate(el=>getComputedStyle(el).display)).toBe('block');
  expect(await gridColumns(indexGrid)).toBe(1);
  expect(await gridColumns(reference)).toBe(1);
  expect(px(await copy.evaluate(el=>getComputedStyle(el).fontSize))).toBeGreaterThanOrEqual(17);

  const copyBox=await copy.boundingBox();
  const labelBox=await label.boundingBox();
  expect(Math.abs(copyBox.x-labelBox.x)).toBeLessThan(2);
  expect(copyBox.width).toBeGreaterThan(340);
  expect(await material.evaluate(el=>el.scrollWidth-el.clientWidth)).toBeLessThanOrEqual(1);
  expect(await page.evaluate(()=>document.documentElement.scrollWidth-window.innerWidth)).toBeLessThanOrEqual(1);
});
