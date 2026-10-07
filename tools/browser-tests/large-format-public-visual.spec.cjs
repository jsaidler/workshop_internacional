const {test,expect}=require('@playwright/test');
const url='http://127.0.0.1:8099/tools/browser-fixture/large-format-public-visual.html';

function rgb(value){
  const m=String(value).match(/rgba?\\((\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)/);
  if(!m)throw new Error('Unsupported color '+value);
  return [Number(m[1]),Number(m[2]),Number(m[3])];
}
function luminance([r,g,b]){
  const s=[r,g,b].map(v=>{v/=255;return v<=.04045?v/12.92:Math.pow((v+.055)/1.055,2.4);});
  return .2126*s[0]+.7152*s[1]+.0722*s[2];
}
function contrast(a,b){
  const l1=luminance(rgb(a)),l2=luminance(rgb(b));
  return (Math.max(l1,l2)+.05)/(Math.min(l1,l2)+.05);
}
async function effectiveColors(locator){
  return locator.evaluate(el=>{
    const fg=getComputedStyle(el).color;
    let node=el,bg='rgba(0, 0, 0, 0)';
    while(node){
      const candidate=getComputedStyle(node).backgroundColor;
      if(candidate&&!candidate.endsWith(', 0)')&&candidate!=='transparent'){bg=candidate;break;}
      node=node.parentElement;
    }
    return {fg,bg};
  });
}
async function stableHover(locator){
  const before=await locator.boundingBox();
  await locator.hover();
  const after=await locator.boundingBox();
  expect(before).not.toBeNull();expect(after).not.toBeNull();
  expect(Math.abs(before.width-after.width)).toBeLessThan(.5);
  expect(Math.abs(before.height-after.height)).toBeLessThan(.5);
}
async function expectHoverContrast(locator,min=4.5){
  await stableHover(locator);
  const {fg,bg}=await effectiveColors(locator);
  expect(contrast(fg,bg)).toBeGreaterThanOrEqual(min);
}

for(const theme of ['light','dark']){
  test('large-format public components remain legible on hover — '+theme,async({page})=>{
    await page.setViewportSize({width:1440,height:1000});
    await page.goto(url,{waitUntil:'networkidle'});
    await page.evaluate(theme=>document.documentElement.dataset.theme=theme,theme);

    await expect(page.locator('[data-cms-image-placeholder]')).toHaveCount(3);

    await expectHoverContrast(page.locator('#hero-cta'));
    await page.mouse.move(2,2);
    await expectHoverContrast(page.locator('#form-submit'));
    await page.mouse.move(2,2);
    await expectHoverContrast(page.locator('#choice-hover'));

    const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
    expect(overflow).toBeLessThanOrEqual(1);

    await page.screenshot({path:'test-results/visual/large-format-'+theme+'-desktop.png',fullPage:true});
  });
}

test('large-format public composition remains contained on phone',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url,{waitUntil:'networkidle'});
  await page.evaluate(()=>document.documentElement.dataset.theme='light');

  const placeholders=page.locator('[data-cms-image-placeholder]');
  await expect(placeholders).toHaveCount(3);
  for(let i=0;i<3;i++)await expect(placeholders.nth(i)).toBeVisible();

  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);

  await expectHoverContrast(page.locator('#hero-cta'));
  await page.mouse.move(2,2);
  await expectHoverContrast(page.locator('#form-submit'));

  await page.screenshot({path:'test-results/visual/large-format-light-mobile.png',fullPage:true});
});
