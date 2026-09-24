const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/study-material.html';
const px=value=>Number.parseFloat(value||'0');
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

const bottom=box=>box.y+box.height;
const renderedGap=async(before,after)=>{
  const beforeBox=await before.boundingBox();
  const afterBox=await after.boundingBox();
  return afterBox.y-bottom(beforeBox);
};

test('study material uses one continuous desktop reading axis with hierarchical breathing room',async({page})=>{
  await page.setViewportSize({width:1440,height:1000});
  await page.goto(url);

  const material=page.locator('#material');
  const coverTitle=page.locator('.study-cover h1');
  const unit=page.locator('#unit-reference');
  const label=page.locator('#unit-label');
  const directCopy=page.locator('#direct-copy');
  const paragraph=page.locator('#paragraph-one');
  const note=page.locator('#technical-note');
  const unitGrid=page.locator('#unit-heading-grid');
  const heading=page.locator('#unit-heading');
  const referenceGrid=page.locator('#reference-grid');
  const processList=page.locator('#reference-process-list');
  const figure=page.locator('#reference-figure');
  const indexCards=page.locator('#study-index-grid > .format-card');
  const beforeRegisterCopy=page.locator('#before-register-copy');
  const registerUnit=page.locator('#unit-register');
  const registerLabel=page.locator('#register-label');

  const unitBox=await unit.boundingBox();
  const labelBox=await label.boundingBox();
  const copyBox=await directCopy.boundingBox();
  const noteBox=await note.boundingBox();
  expect(unitBox.width).toBeGreaterThan(1120);
  expect(unitBox.width).toBeLessThanOrEqual(1201);
  expect(copyBox.width).toBeGreaterThanOrEqual(840);
  expect(copyBox.width).toBeLessThanOrEqual(885);
  expect(Math.abs(labelBox.x-copyBox.x)).toBeLessThan(2);
  expect(Math.abs(noteBox.x-copyBox.x)).toBeLessThan(2);

  expect(await unit.evaluate(el=>getComputedStyle(el).display)).toBe('block');
  expect(await unitGrid.evaluate(el=>getComputedStyle(el).display)).toBe('block');
  expect(await label.evaluate(el=>getComputedStyle(el).position)).toBe('static');

  const indexBoxes=await expectSameRow(indexCards);
  expect(indexBoxes).toHaveLength(3);
  for(const box of indexBoxes)expect(box.width).toBeGreaterThan(250);

  expect(px(await directCopy.evaluate(el=>getComputedStyle(el).fontSize))).toBeGreaterThanOrEqual(18);
  const lineHeight=px(await directCopy.evaluate(el=>getComputedStyle(el).lineHeight));
  expect(lineHeight).toBeGreaterThanOrEqual(27);
  expect(lineHeight).toBeLessThanOrEqual(29.5);

  const paragraphGap=px(await paragraph.evaluate(el=>getComputedStyle(el).marginBottom));
  expect(paragraphGap).toBeGreaterThanOrEqual(16);
  expect(paragraphGap).toBeLessThanOrEqual(19);

  const unitPaddingTop=px(await unit.evaluate(el=>getComputedStyle(el).paddingTop));
  const unitPaddingBottom=px(await unit.evaluate(el=>getComputedStyle(el).paddingBottom));
  expect(unitPaddingTop).toBeGreaterThanOrEqual(58);
  expect(unitPaddingTop).toBeLessThanOrEqual(72.5);
  expect(unitPaddingBottom).toBeGreaterThanOrEqual(50);
  expect(unitPaddingBottom).toBeLessThanOrEqual(62.5);
  expect(px(await label.evaluate(el=>getComputedStyle(el).marginBottom))).toBeGreaterThanOrEqual(28);
  expect(px(await label.evaluate(el=>getComputedStyle(el).marginBottom))).toBeLessThanOrEqual(32);
  expect(px(await note.evaluate(el=>getComputedStyle(el).marginTop))).toBeGreaterThanOrEqual(46);
  expect(px(await note.evaluate(el=>getComputedStyle(el).marginTop))).toBeLessThanOrEqual(50);
  expect(px(await note.evaluate(el=>getComputedStyle(el).marginBottom))).toBeGreaterThanOrEqual(46);
  expect(px(await note.evaluate(el=>getComputedStyle(el).marginBottom))).toBeLessThanOrEqual(50);
  expect(px(await referenceGrid.evaluate(el=>getComputedStyle(el).marginTop))).toBeGreaterThanOrEqual(50);
  expect(px(await referenceGrid.evaluate(el=>getComputedStyle(el).marginTop))).toBeLessThanOrEqual(54);
  expect(px(await referenceGrid.evaluate(el=>getComputedStyle(el).marginBottom))).toBeGreaterThanOrEqual(50);
  expect(px(await referenceGrid.evaluate(el=>getComputedStyle(el).marginBottom))).toBeLessThanOrEqual(54);
  expect(px(await processList.evaluate(el=>getComputedStyle(el).marginTop))).toBeGreaterThanOrEqual(50);
  expect(px(await processList.evaluate(el=>getComputedStyle(el).marginBottom))).toBeGreaterThanOrEqual(50);
  expect(px(await figure.evaluate(el=>getComputedStyle(el).marginTop))).toBeGreaterThanOrEqual(58);
  expect(px(await figure.evaluate(el=>getComputedStyle(el).marginBottom))).toBeGreaterThanOrEqual(58);

  const beforeBox=await beforeRegisterCopy.boundingBox();
  const registerUnitBox=await registerUnit.boundingBox();
  const registerLabelBox=await registerLabel.boundingBox();
  const beforeDivider=registerUnitBox.y-bottom(beforeBox);
  const afterDivider=registerLabelBox.y-registerUnitBox.y;
  const boundaryGap=registerLabelBox.y-bottom(beforeBox);
  expect(beforeDivider).toBeGreaterThanOrEqual(50);
  expect(beforeDivider).toBeLessThanOrEqual(63);
  expect(afterDivider).toBeGreaterThanOrEqual(58);
  expect(afterDivider).toBeLessThanOrEqual(74);
  expect(boundaryGap).toBeGreaterThanOrEqual(108);
  expect(boundaryGap).toBeLessThanOrEqual(137);

  expect(px(await note.evaluate(el=>getComputedStyle(el).fontSize))).toBeGreaterThanOrEqual(13);
  expect(px(await heading.evaluate(el=>getComputedStyle(el).fontSize))).toBeLessThanOrEqual(45);
  expect(px(await coverTitle.evaluate(el=>getComputedStyle(el).fontSize))).toBeGreaterThan(60);

  const overflow=await material.evaluate(el=>el.scrollWidth-el.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);
});

test('desktop meso rhythm separates titles and reference boxes on both sides',async({page})=>{
  await page.setViewportSize({width:1440,height:1000});
  await page.goto(url);

  const indexHeading=page.locator('#study-index-heading');
  const indexGrid=page.locator('#study-index-grid');
  const label=page.locator('#unit-label');
  const heading=page.locator('#unit-heading');
  const headingCopy=page.locator('#heading-copy');
  const paragraphTwo=page.locator('#paragraph-two');
  const internalHeading=page.locator('#internal-heading');
  const afterInternalHeading=page.locator('#after-internal-heading');
  const note=page.locator('#technical-note');
  const grid=page.locator('#reference-grid');
  const afterGrid=page.locator('#after-grid-copy');
  const processList=page.locator('#reference-process-list');
  const afterProcess=page.locator('#after-process-copy');
  const figure=page.locator('#reference-figure');
  const afterFigure=page.locator('#after-figure-copy');
  const registerLabel=page.locator('#register-label');
  const registerIntro=page.locator('#register-intro');
  const registerList=page.locator('#register-list');

  expect(await renderedGap(indexHeading,indexGrid)).toBeGreaterThanOrEqual(34);
  expect(await renderedGap(indexHeading,indexGrid)).toBeLessThanOrEqual(38);

  expect(await renderedGap(label,heading)).toBeGreaterThanOrEqual(28);
  expect(await renderedGap(label,heading)).toBeLessThanOrEqual(32);
  expect(await renderedGap(heading,headingCopy)).toBeGreaterThanOrEqual(34);
  expect(await renderedGap(heading,headingCopy)).toBeLessThanOrEqual(40);

  expect(await renderedGap(paragraphTwo,internalHeading)).toBeGreaterThanOrEqual(54);
  expect(await renderedGap(paragraphTwo,internalHeading)).toBeLessThanOrEqual(58);
  expect(await renderedGap(internalHeading,afterInternalHeading)).toBeGreaterThanOrEqual(20);
  expect(await renderedGap(internalHeading,afterInternalHeading)).toBeLessThanOrEqual(24);

  expect(await renderedGap(afterInternalHeading,note)).toBeGreaterThanOrEqual(46);
  expect(await renderedGap(afterInternalHeading,note)).toBeLessThanOrEqual(50);
  expect(await renderedGap(note,grid)).toBeGreaterThanOrEqual(50);
  expect(await renderedGap(note,grid)).toBeLessThanOrEqual(54);
  expect(await renderedGap(grid,afterGrid)).toBeGreaterThanOrEqual(50);
  expect(await renderedGap(grid,afterGrid)).toBeLessThanOrEqual(54);
  expect(await renderedGap(afterGrid,processList)).toBeGreaterThanOrEqual(50);
  expect(await renderedGap(afterGrid,processList)).toBeLessThanOrEqual(54);
  expect(await renderedGap(processList,afterProcess)).toBeGreaterThanOrEqual(50);
  expect(await renderedGap(processList,afterProcess)).toBeLessThanOrEqual(54);
  expect(await renderedGap(afterProcess,figure)).toBeGreaterThanOrEqual(58);
  expect(await renderedGap(afterProcess,figure)).toBeLessThanOrEqual(62);
  expect(await renderedGap(figure,afterFigure)).toBeGreaterThanOrEqual(58);
  expect(await renderedGap(figure,afterFigure)).toBeLessThanOrEqual(62);

  expect(await renderedGap(registerLabel,registerIntro)).toBeGreaterThanOrEqual(28);
  expect(await renderedGap(registerLabel,registerIntro)).toBeLessThanOrEqual(32);
  expect(await renderedGap(registerIntro,registerList)).toBeGreaterThanOrEqual(50);
  expect(await renderedGap(registerIntro,registerList)).toBeLessThanOrEqual(54);
});

test('study reference components stay distinct without breaking the reading flow',async({page})=>{
  await page.setViewportSize({width:1440,height:1000});
  await page.goto(url);

  const copy=page.locator('#direct-copy');
  const note=page.locator('#technical-note');
  const cards=page.locator('#reference-grid > .format-card');
  const firstLink=page.locator('#study-index-grid a').first();

  const copyBg=await copy.evaluate(el=>getComputedStyle(el).backgroundColor);
  const noteBg=await note.evaluate(el=>getComputedStyle(el).backgroundColor);
  expect(noteBg).not.toBe(copyBg);

  const cardBoxes=await expectSameRow(cards);
  expect(cardBoxes).toHaveLength(3);
  for(const box of cardBoxes)expect(box.width).toBeGreaterThan(210);

  await firstLink.focus();
  expect(await firstLink.evaluate(el=>getComputedStyle(el).outlineStyle)).not.toBe('none');
});

test('study material keeps the same reading axis and reduced but visible rhythm on tablet',async({page})=>{
  await page.setViewportSize({width:900,height:1000});
  await page.goto(url);

  const material=page.locator('#material');
  const unit=page.locator('#unit-reference');
  const unitGrid=page.locator('#unit-heading-grid');
  const copy=page.locator('#direct-copy');
  const label=page.locator('#unit-label');
  const note=page.locator('#technical-note');
  const indexCards=page.locator('#study-index-grid > .format-card');
  const indexHeading=page.locator('#study-index-heading');
  const indexGrid=page.locator('#study-index-grid');
  const internalHeading=page.locator('#internal-heading');
  const paragraphTwo=page.locator('#paragraph-two');
  const afterInternalHeading=page.locator('#after-internal-heading');
  const referenceGrid=page.locator('#reference-grid');
  const afterGrid=page.locator('#after-grid-copy');
  const processList=page.locator('#reference-process-list');
  const afterProcess=page.locator('#after-process-copy');
  const figure=page.locator('#reference-figure');
  const afterFigure=page.locator('#after-figure-copy');
  const beforeRegisterCopy=page.locator('#before-register-copy');
  const registerUnit=page.locator('#unit-register');
  const registerLabel=page.locator('#register-label');
  const registerIntro=page.locator('#register-intro');
  const registerList=page.locator('#register-list');

  expect(await unit.evaluate(el=>getComputedStyle(el).display)).toBe('block');
  expect(await unitGrid.evaluate(el=>getComputedStyle(el).display)).toBe('block');
  const copyBox=await copy.boundingBox();
  const labelBox=await label.boundingBox();
  expect(copyBox.width).toBeGreaterThanOrEqual(760);
  expect(copyBox.width).toBeLessThanOrEqual(785);
  expect(Math.abs(copyBox.x-labelBox.x)).toBeLessThan(2);

  const unitPaddingTop=px(await unit.evaluate(el=>getComputedStyle(el).paddingTop));
  const unitPaddingBottom=px(await unit.evaluate(el=>getComputedStyle(el).paddingBottom));
  expect(unitPaddingTop).toBeGreaterThanOrEqual(58);
  expect(unitPaddingTop).toBeLessThanOrEqual(60);
  expect(unitPaddingBottom).toBeGreaterThanOrEqual(54);
  expect(unitPaddingBottom).toBeLessThanOrEqual(56);
  expect(px(await note.evaluate(el=>getComputedStyle(el).marginTop))).toBeGreaterThanOrEqual(42);
  expect(px(await note.evaluate(el=>getComputedStyle(el).marginTop))).toBeLessThanOrEqual(46);

  expect(await renderedGap(indexHeading,indexGrid)).toBeGreaterThanOrEqual(30);
  expect(await renderedGap(indexHeading,indexGrid)).toBeLessThanOrEqual(34);
  expect(await renderedGap(paragraphTwo,internalHeading)).toBeGreaterThanOrEqual(48);
  expect(await renderedGap(paragraphTwo,internalHeading)).toBeLessThanOrEqual(52);
  expect(await renderedGap(internalHeading,afterInternalHeading)).toBeGreaterThanOrEqual(18);
  expect(await renderedGap(internalHeading,afterInternalHeading)).toBeLessThanOrEqual(22);
  expect(await renderedGap(referenceGrid,afterGrid)).toBeGreaterThanOrEqual(46);
  expect(await renderedGap(referenceGrid,afterGrid)).toBeLessThanOrEqual(50);
  expect(await renderedGap(processList,afterProcess)).toBeGreaterThanOrEqual(46);
  expect(await renderedGap(processList,afterProcess)).toBeLessThanOrEqual(50);
  expect(await renderedGap(figure,afterFigure)).toBeGreaterThanOrEqual(50);
  expect(await renderedGap(figure,afterFigure)).toBeLessThanOrEqual(54);
  expect(await renderedGap(registerLabel,registerIntro)).toBeGreaterThanOrEqual(26);
  expect(await renderedGap(registerLabel,registerIntro)).toBeLessThanOrEqual(30);
  expect(await renderedGap(registerIntro,registerList)).toBeGreaterThanOrEqual(46);
  expect(await renderedGap(registerIntro,registerList)).toBeLessThanOrEqual(50);

  const beforeBox=await beforeRegisterCopy.boundingBox();
  const registerUnitBox=await registerUnit.boundingBox();
  const registerLabelBox=await registerLabel.boundingBox();
  const boundaryGap=registerLabelBox.y-bottom(beforeBox);
  expect(boundaryGap).toBeGreaterThanOrEqual(108);
  expect(boundaryGap).toBeLessThanOrEqual(118);

  const indexBoxes=await expectSameRow(indexCards);
  expect(indexBoxes).toHaveLength(3);
  for(const box of indexBoxes)expect(box.width).toBeGreaterThan(240);

  expect(await material.evaluate(el=>el.scrollWidth-el.clientWidth)).toBeLessThanOrEqual(1);
  expect(await page.evaluate(()=>document.documentElement.scrollWidth-window.innerWidth)).toBeLessThanOrEqual(1);
});

test('study material preserves a readable vertical hierarchy on small screens',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url);

  const material=page.locator('#material');
  const unit=page.locator('#unit-reference');
  const unitGrid=page.locator('#unit-heading-grid');
  const indexGrid=page.locator('#study-index-grid');
  const indexHeading=page.locator('#study-index-heading');
  const reference=page.locator('#reference-grid');
  const copy=page.locator('#direct-copy');
  const label=page.locator('#unit-label');
  const note=page.locator('#technical-note');
  const paragraphTwo=page.locator('#paragraph-two');
  const internalHeading=page.locator('#internal-heading');
  const afterInternalHeading=page.locator('#after-internal-heading');
  const afterGrid=page.locator('#after-grid-copy');
  const processList=page.locator('#reference-process-list');
  const afterProcess=page.locator('#after-process-copy');
  const figure=page.locator('#reference-figure');
  const afterFigure=page.locator('#after-figure-copy');
  const beforeRegisterCopy=page.locator('#before-register-copy');
  const registerUnit=page.locator('#unit-register');
  const registerLabel=page.locator('#register-label');
  const registerIntro=page.locator('#register-intro');
  const registerList=page.locator('#register-list');

  expect(await unit.evaluate(el=>getComputedStyle(el).display)).toBe('block');
  expect(await unitGrid.evaluate(el=>getComputedStyle(el).display)).toBe('block');
  expect(await gridColumns(indexGrid)).toBe(1);
  expect(await gridColumns(reference)).toBe(1);
  expect(px(await copy.evaluate(el=>getComputedStyle(el).fontSize))).toBeGreaterThanOrEqual(17.5);

  const unitPaddingTop=px(await unit.evaluate(el=>getComputedStyle(el).paddingTop));
  const unitPaddingBottom=px(await unit.evaluate(el=>getComputedStyle(el).paddingBottom));
  expect(unitPaddingTop).toBeGreaterThanOrEqual(48);
  expect(unitPaddingTop).toBeLessThanOrEqual(50);
  expect(unitPaddingBottom).toBeGreaterThanOrEqual(46);
  expect(unitPaddingBottom).toBeLessThanOrEqual(48);
  expect(px(await note.evaluate(el=>getComputedStyle(el).marginTop))).toBeGreaterThanOrEqual(34);
  expect(px(await note.evaluate(el=>getComputedStyle(el).marginTop))).toBeLessThanOrEqual(38);
  expect(px(await figure.evaluate(el=>getComputedStyle(el).marginTop))).toBeGreaterThanOrEqual(42);
  expect(px(await figure.evaluate(el=>getComputedStyle(el).marginTop))).toBeLessThanOrEqual(46);

  expect(await renderedGap(indexHeading,indexGrid)).toBeGreaterThanOrEqual(26);
  expect(await renderedGap(indexHeading,indexGrid)).toBeLessThanOrEqual(30);
  expect(await renderedGap(paragraphTwo,internalHeading)).toBeGreaterThanOrEqual(42);
  expect(await renderedGap(paragraphTwo,internalHeading)).toBeLessThanOrEqual(46);
  expect(await renderedGap(internalHeading,afterInternalHeading)).toBeGreaterThanOrEqual(16);
  expect(await renderedGap(internalHeading,afterInternalHeading)).toBeLessThanOrEqual(20);
  expect(await renderedGap(reference,afterGrid)).toBeGreaterThanOrEqual(38);
  expect(await renderedGap(reference,afterGrid)).toBeLessThanOrEqual(42);
  expect(await renderedGap(processList,afterProcess)).toBeGreaterThanOrEqual(38);
  expect(await renderedGap(processList,afterProcess)).toBeLessThanOrEqual(42);
  expect(await renderedGap(figure,afterFigure)).toBeGreaterThanOrEqual(42);
  expect(await renderedGap(figure,afterFigure)).toBeLessThanOrEqual(46);
  expect(await renderedGap(registerLabel,registerIntro)).toBeGreaterThanOrEqual(22);
  expect(await renderedGap(registerLabel,registerIntro)).toBeLessThanOrEqual(26);
  expect(await renderedGap(registerIntro,registerList)).toBeGreaterThanOrEqual(38);
  expect(await renderedGap(registerIntro,registerList)).toBeLessThanOrEqual(42);

  const beforeBox=await beforeRegisterCopy.boundingBox();
  const registerUnitBox=await registerUnit.boundingBox();
  const registerLabelBox=await registerLabel.boundingBox();
  const boundaryGap=registerLabelBox.y-bottom(beforeBox);
  expect(boundaryGap).toBeGreaterThanOrEqual(90);
  expect(boundaryGap).toBeLessThanOrEqual(100);

  const copyBox=await copy.boundingBox();
  const labelBox=await label.boundingBox();
  expect(Math.abs(copyBox.x-labelBox.x)).toBeLessThan(2);
  expect(copyBox.width).toBeGreaterThan(340);
  expect(await material.evaluate(el=>el.scrollWidth-el.clientWidth)).toBeLessThanOrEqual(1);
  expect(await page.evaluate(()=>document.documentElement.scrollWidth-window.innerWidth)).toBeLessThanOrEqual(1);
});
