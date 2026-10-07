const {test,expect}=require('@playwright/test');
const url='http://127.0.0.1:8099/tools/browser-fixture/large-format-public-visual.php';

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
async function interactiveStyle(locator){
  return locator.evaluate(el=>{
    const s=getComputedStyle(el);
    return {
      color:s.color,
      backgroundColor:s.backgroundColor,
      borderTopColor:s.borderTopColor,
      borderRightColor:s.borderRightColor,
      borderBottomColor:s.borderBottomColor,
      borderLeftColor:s.borderLeftColor,
    };
  });
}
async function expectHoverMechanics(locator){
  const beforeBox=await locator.boundingBox();
  const beforeStyle=await interactiveStyle(locator);
  await locator.hover();
  const afterBox=await locator.boundingBox();
  const afterStyle=await interactiveStyle(locator);
  expect(beforeBox).not.toBeNull();expect(afterBox).not.toBeNull();
  expect(Math.abs(beforeBox.width-afterBox.width)).toBeLessThan(.5);
  expect(Math.abs(beforeBox.height-afterBox.height)).toBeLessThan(.5);
  expect(afterStyle).not.toEqual(beforeStyle);
}
async function expectHoverContrast(locator,min=4.5){
  await expectHoverMechanics(locator);
  const {fg,bg}=await effectiveColors(locator);
  expect(contrast(fg,bg)).toBeGreaterThanOrEqual(min);
}
async function expectFocusVisible(locator){
  await locator.focus();
  const focus=await locator.evaluate(el=>{const s=getComputedStyle(el);return {style:s.outlineStyle,width:parseFloat(s.outlineWidth)||0};});
  expect(focus.style).not.toBe('none');
  expect(focus.width).toBeGreaterThanOrEqual(2);
}
async function expectHeroTitleContained(page){
  const overflow=await page.locator('.hero h1 span').evaluateAll(spans=>Math.max(...spans.map(el=>el.scrollWidth-el.clientWidth)));
  expect(overflow).toBeLessThanOrEqual(1);
}

for(const theme of ['light','dark']){
  test('large-format public components remain legible on hover — '+theme,async({page})=>{
    await page.setViewportSize({width:1440,height:1000});
    await page.goto(url,{waitUntil:'networkidle'});
    await page.evaluate(theme=>document.documentElement.dataset.theme=theme,theme);

    await expect(page.locator('[data-cms-image-placeholder]')).toHaveCount(4);
    for(const section of ['hero','diagnosis','camera-lab','journey','meetings','positive-processes','construction','offer','about','faq','interest']){
      await expect(page.locator('[data-cms-section="'+section+'"]')).toHaveCount(1);
    }
    await expectHeroTitleContained(page);

    const heroCta=page.locator('[data-cms-section="hero"] .button-primary');
    await expectHoverContrast(heroCta);
    if(theme==='light')await heroCta.screenshot({path:'test-results/visual/large-format-hover-hero-cta-light.png'});
    await page.mouse.move(2,2);
    const formSubmit=page.locator('#form-submit');
    await expectHoverContrast(formSubmit);
    if(theme==='light')await formSubmit.screenshot({path:'test-results/visual/large-format-hover-form-submit-light.png'});
    await page.mouse.move(2,2);
    const choice=page.locator('#choice-hover');
    await expectHoverContrast(choice);
    if(theme==='light')await choice.screenshot({path:'test-results/visual/large-format-hover-choice-light.png'});
    await page.mouse.move(2,2);
    await expectHoverContrast(page.locator('header nav a').last());
    await expectFocusVisible(page.locator('[data-cms-section="hero"] .button-primary'));
    await expectFocusVisible(page.locator('#form-submit'));

    const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
    expect(overflow).toBeLessThanOrEqual(1);

    await page.evaluate(()=>{document.activeElement?.blur();const topbar=document.querySelector('.topbar');if(topbar)topbar.style.position='static';window.scrollTo(0,0);});
    await page.mouse.move(2,2);
    await page.waitForTimeout(80);
    await page.screenshot({path:'test-results/visual/large-format-'+theme+'-desktop.png',fullPage:true});
  });
}

test('large-format public composition remains contained on phone',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url,{waitUntil:'networkidle'});
  await page.evaluate(()=>document.documentElement.dataset.theme='light');

  const placeholders=page.locator('[data-cms-image-placeholder]');
  await expect(placeholders).toHaveCount(4);
  for(let i=0;i<4;i++)await expect(placeholders.nth(i)).toBeVisible();
  await expectHeroTitleContained(page);

  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);

  await expectHoverContrast(page.locator('[data-cms-section="hero"] .button-primary'));
  await page.mouse.move(2,2);
  await expectHoverContrast(page.locator('#form-submit'));

  await page.evaluate(()=>{document.activeElement?.blur();const topbar=document.querySelector('.topbar');if(topbar)topbar.style.position='static';window.scrollTo(0,0);});
  await page.mouse.move(2,2);
  await page.waitForTimeout(80);
  await page.screenshot({path:'test-results/visual/large-format-light-mobile.png',fullPage:true});
});
