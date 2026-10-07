const {test,expect}=require('@playwright/test');
const url='http://127.0.0.1:8099/tools/browser-fixture/large-format-public-visual.html';

function rgba(value){
  const text=String(value).trim();
  let m=text.match(/rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)(?:\s*,\s*([\d.]+))?\s*\)/);
  if(m)return [Number(m[1]),Number(m[2]),Number(m[3]),m[4]===undefined?1:Number(m[4])];
  m=text.match(/color\(srgb\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)(?:\s*\/\s*([\d.]+))?\s*\)/);
  if(m)return [Number(m[1])*255,Number(m[2])*255,Number(m[3])*255,m[4]===undefined?1:Number(m[4])];
  if(text==='transparent')return [0,0,0,0];
  throw new Error('Unsupported color '+value);
}
function composite(top,bottom){
  const a=top[3]+bottom[3]*(1-top[3]);
  if(a<=0)return [0,0,0,0];
  return [
    (top[0]*top[3]+bottom[0]*bottom[3]*(1-top[3]))/a,
    (top[1]*top[3]+bottom[1]*bottom[3]*(1-top[3]))/a,
    (top[2]*top[3]+bottom[2]*bottom[3]*(1-top[3]))/a,
    a,
  ];
}
function luminance([r,g,b]){
  const s=[r,g,b].map(v=>{v/=255;return v<=.04045?v/12.92:Math.pow((v+.055)/1.055,2.4);});
  return .2126*s[0]+.7152*s[1]+.0722*s[2];
}
function contrast(a,b){
  const l1=luminance(a),l2=luminance(b);
  return (Math.max(l1,l2)+.05)/(Math.min(l1,l2)+.05);
}
async function effectiveColors(locator){
  const data=await locator.evaluate(el=>{
    const backgrounds=[];
    let node=el;
    while(node){backgrounds.push(getComputedStyle(node).backgroundColor);node=node.parentElement;}
    return {fg:getComputedStyle(el).color,backgrounds};
  });
  let bg=[255,255,255,1];
  for(const value of data.backgrounds.slice().reverse())bg=composite(rgba(value),bg);
  return {fg:rgba(data.fg),bg};
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
