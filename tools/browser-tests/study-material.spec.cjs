const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/study-material.html';
const px=value=>Number.parseFloat(value||'0');
const bottom=box=>box.y+box.height;
const gridColumns=async locator=>locator.evaluate(el=>getComputedStyle(el).gridTemplateColumns.trim().split(/\s+/).filter(Boolean).length);

async function boxes(locator){
  const out=[];
  for(let i=0;i<await locator.count();i++)out.push(await locator.nth(i).boundingBox());
  return out;
}

async function expectSameRow(locator){
  const rendered=await boxes(locator);
  expect(rendered.length).toBeGreaterThan(1);
  for(let i=1;i<rendered.length;i++)expect(Math.abs(rendered[0].y-rendered[i].y)).toBeLessThan(2);
  return rendered;
}

async function renderedGap(before,after){
  const a=await before.boundingBox();
  const b=await after.boundingBox();
  return b.y-bottom(a);
}

test('desktop study material reads as continuous longform instead of stacked panels',async({page})=>{
  await page.setViewportSize({width:1440,height:1000});
  await page.goto(url);

  const material=page.locator('#material');
  const unit=page.locator('#unit-reference');
  const label=page.locator('#unit-label');
  const copy=page.locator('#direct-copy');
  const note=page.locator('#technical-note');
  const heading=page.locator('#unit-heading');
  const headingCopy=page.locator('#heading-copy');
  const paragraphTwo=page.locator('#paragraph-two');
  const internalHeading=page.locator('#internal-heading');
  const afterInternal=page.locator('#after-internal-heading');
  const grid=page.locator('#reference-grid');
  const afterGrid=page.locator('#after-grid-copy');
  const process=page.locator('#reference-process-list');
  const afterProcess=page.locator('#after-process-copy');
  const figure=page.locator('#reference-figure');
  const afterFigure=page.locator('#after-figure-copy');

  const unitBox=await unit.boundingBox();
  const copyBox=await copy.boundingBox();
  const labelBox=await label.boundingBox();
  const noteBox=await note.boundingBox();
  expect(unitBox.width).toBeGreaterThan(1120);
  expect(unitBox.width).toBeLessThanOrEqual(1201);
  expect(copyBox.width).toBeGreaterThanOrEqual(835);
  expect(copyBox.width).toBeLessThanOrEqual(845);
  expect(Math.abs(labelBox.x-copyBox.x)).toBeLessThan(2);
  expect(Math.abs(noteBox.x-copyBox.x)).toBeLessThan(2);

  expect(px(await copy.evaluate(el=>getComputedStyle(el).fontSize))).toBe(18);
  const lineHeight=px(await copy.evaluate(el=>getComputedStyle(el).lineHeight));
  expect(lineHeight).toBeGreaterThanOrEqual(29);
  expect(lineHeight).toBeLessThanOrEqual(30.5);

  const unitStyle=await unit.evaluate(el=>getComputedStyle(el));
  expect(px(unitStyle.borderTopWidth)).toBe(0);
  expect(px(await label.evaluate(el=>getComputedStyle(el).borderBottomWidth))).toBe(0);
  expect(px(await label.evaluate(el=>getComputedStyle(el).marginBottom))).toBeGreaterThanOrEqual(23);
  expect(px(await label.evaluate(el=>getComputedStyle(el).marginBottom))).toBeLessThanOrEqual(25);

  expect(await renderedGap(label,heading)).toBeGreaterThanOrEqual(22);
  expect(await renderedGap(label,heading)).toBeLessThanOrEqual(26);
  expect(await renderedGap(heading,headingCopy)).toBeGreaterThanOrEqual(26);
  expect(await renderedGap(heading,headingCopy)).toBeLessThanOrEqual(30);
  expect(await renderedGap(paragraphTwo,internalHeading)).toBeGreaterThanOrEqual(44);
  expect(await renderedGap(paragraphTwo,internalHeading)).toBeLessThanOrEqual(48);
  expect(await renderedGap(internalHeading,afterInternal)).toBeGreaterThanOrEqual(15);
  expect(await renderedGap(internalHeading,afterInternal)).toBeLessThanOrEqual(19);
  expect(await renderedGap(afterInternal,note)).toBeGreaterThanOrEqual(38);
  expect(await renderedGap(afterInternal,note)).toBeLessThanOrEqual(42);
  expect(await renderedGap(note,grid)).toBeGreaterThanOrEqual(40);
  expect(await renderedGap(note,grid)).toBeLessThanOrEqual(44);
  expect(await renderedGap(grid,afterGrid)).toBeGreaterThanOrEqual(40);
  expect(await renderedGap(grid,afterGrid)).toBeLessThanOrEqual(44);
  expect(await renderedGap(process,afterProcess)).toBeGreaterThanOrEqual(40);
  expect(await renderedGap(process,afterProcess)).toBeLessThanOrEqual(44);
  expect(await renderedGap(figure,afterFigure)).toBeGreaterThanOrEqual(48);
  expect(await renderedGap(figure,afterFigure)).toBeLessThanOrEqual(52);

  const noteStyle=await note.evaluate(el=>getComputedStyle(el));
  expect(noteStyle.backgroundColor).toBe('rgba(0, 0, 0, 0)');
  expect(px(noteStyle.borderTopWidth)).toBe(0);
  expect(px(noteStyle.borderBottomWidth)).toBe(0);
  expect(px(noteStyle.borderLeftWidth)).toBeGreaterThanOrEqual(2);
  expect(noteBox.height).toBeLessThan(80);

  const dataCards=await expectSameRow(page.locator('#reference-grid > .format-card'));
  expect(dataCards).toHaveLength(3);
  for(const box of dataCards)expect(box.width).toBeGreaterThan(300);

  expect(await material.evaluate(el=>el.scrollWidth-el.clientWidth)).toBeLessThanOrEqual(1);
});

test('desktop reference components receive visual weight according to semantic value',async({page})=>{
  await page.setViewportSize({width:1440,height:1000});
  await page.goto(url);

  const equation=page.locator('#equation-note');
  const quote=page.locator('#quote-note');
  const compact=page.locator('#compact-values');
  const comparison=page.locator('#comparison-grid');
  const resources=page.locator('#resource-list');
  const resourceCards=page.locator('#resource-list > .format-card');

  expect(px(await equation.evaluate(el=>getComputedStyle(el).fontSize))).toBeGreaterThanOrEqual(15.5);
  expect(px(await quote.evaluate(el=>getComputedStyle(el).fontSize))).toBeGreaterThanOrEqual(17.5);

  expect(await gridColumns(compact)).toBe(4);
  expect(await gridColumns(comparison)).toBe(2);
  expect(await resources.evaluate(el=>getComputedStyle(el).display)).toBe('block');
  expect(await gridColumns(resourceCards.first())).toBe(2);

  const compactBox=await compact.boundingBox();
  const comparisonBox=await comparison.boundingBox();
  const resourcesBox=await resources.boundingBox();
  expect(compactBox.width).toBeLessThanOrEqual(845);
  expect(comparisonBox.width).toBeLessThanOrEqual(845);
  expect(resourcesBox.width).toBeLessThanOrEqual(845);

  for(let i=0;i<await resourceCards.count();i++){
    const card=resourceCards.nth(i);
    expect(await card.evaluate(el=>getComputedStyle(el).backgroundColor)).toBe('rgba(0, 0, 0, 0)');
    expect(px(await card.evaluate(el=>getComputedStyle(el).borderBottomWidth))).toBeGreaterThanOrEqual(1);
  }
});

test('unit boundaries keep one macro pause after removing decorative divider rules',async({page})=>{
  await page.setViewportSize({width:1440,height:1000});
  await page.goto(url);

  const beforeCopy=page.locator('#before-register-copy');
  const registerUnit=page.locator('#unit-register');
  const registerLabel=page.locator('#register-label');
  const beforeBox=await beforeCopy.boundingBox();
  const unitBox=await registerUnit.boundingBox();
  const labelBox=await registerLabel.boundingBox();

  const beforeBoundary=unitBox.y-bottom(beforeBox);
  const afterBoundary=labelBox.y-unitBox.y;
  const total=labelBox.y-bottom(beforeBox);
  expect(beforeBoundary).toBeGreaterThanOrEqual(50);
  expect(beforeBoundary).toBeLessThanOrEqual(54);
  expect(afterBoundary).toBeGreaterThanOrEqual(62);
  expect(afterBoundary).toBeLessThanOrEqual(65);
  expect(total).toBeGreaterThanOrEqual(112);
  expect(total).toBeLessThanOrEqual(119);
  expect(px(await registerUnit.evaluate(el=>getComputedStyle(el).borderTopWidth))).toBe(0);
});

test('tablet keeps editorial measure and semantic reference hierarchy',async({page})=>{
  await page.setViewportSize({width:900,height:1000});
  await page.goto(url);

  const material=page.locator('#material');
  const copy=page.locator('#direct-copy');
  const label=page.locator('#unit-label');
  const compact=page.locator('#compact-values');
  const comparison=page.locator('#comparison-grid');
  const resources=page.locator('#resource-list');
  const beforeCopy=page.locator('#before-register-copy');
  const registerLabel=page.locator('#register-label');

  const copyBox=await copy.boundingBox();
  const labelBox=await label.boundingBox();
  expect(copyBox.width).toBeGreaterThanOrEqual(755);
  expect(copyBox.width).toBeLessThanOrEqual(765);
  expect(Math.abs(copyBox.x-labelBox.x)).toBeLessThan(2);
  expect(await gridColumns(compact)).toBe(4);
  expect(await gridColumns(comparison)).toBe(2);
  expect(await resources.evaluate(el=>getComputedStyle(el).display)).toBe('block');

  const total=await renderedGap(beforeCopy,registerLabel);
  expect(total).toBeGreaterThanOrEqual(92);
  expect(total).toBeLessThanOrEqual(96);
  expect(await material.evaluate(el=>el.scrollWidth-el.clientWidth)).toBeLessThanOrEqual(1);
  expect(await page.evaluate(()=>document.documentElement.scrollWidth-window.innerWidth)).toBeLessThanOrEqual(1);
});

test('small screens collapse references without restoring card walls or horizontal overflow',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url);

  const material=page.locator('#material');
  const copy=page.locator('#direct-copy');
  const data=page.locator('#reference-grid');
  const compact=page.locator('#compact-values');
  const comparison=page.locator('#comparison-grid');
  const resources=page.locator('#resource-list');
  const resourceCard=page.locator('#resource-list > .format-card').first();
  const note=page.locator('#technical-note');
  const beforeCopy=page.locator('#before-register-copy');
  const registerLabel=page.locator('#register-label');

  expect(px(await copy.evaluate(el=>getComputedStyle(el).fontSize))).toBeGreaterThanOrEqual(17.5);
  expect(px(await copy.evaluate(el=>getComputedStyle(el).lineHeight))).toBeGreaterThanOrEqual(28.5);
  expect(await gridColumns(data)).toBe(1);
  expect(await gridColumns(compact)).toBe(2);
  expect(await gridColumns(comparison)).toBe(1);
  expect(await resources.evaluate(el=>getComputedStyle(el).display)).toBe('block');
  expect(await gridColumns(resourceCard)).toBe(1);
  expect(await note.evaluate(el=>getComputedStyle(el).backgroundColor)).toBe('rgba(0, 0, 0, 0)');

  const total=await renderedGap(beforeCopy,registerLabel);
  expect(total).toBeGreaterThanOrEqual(78);
  expect(total).toBeLessThanOrEqual(82);

  expect(await material.evaluate(el=>el.scrollWidth-el.clientWidth)).toBeLessThanOrEqual(1);
  expect(await page.evaluate(()=>document.documentElement.scrollWidth-window.innerWidth)).toBeLessThanOrEqual(1);
});

test('study index remains keyboard navigable after longform simplification',async({page})=>{
  await page.setViewportSize({width:1440,height:1000});
  await page.goto(url);
  const firstLink=page.locator('#study-index-grid a').first();
  await firstLink.focus();
  expect(await firstLink.evaluate(el=>getComputedStyle(el).outlineStyle)).not.toBe('none');
});
