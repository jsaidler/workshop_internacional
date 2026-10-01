const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-process-runner.html';
async function mockWake(page){
  await page.addInitScript(()=>{
    window.__wakeRequests=0;window.__wakeReleases=0;window.__vibrations=[];
    Object.defineProperty(navigator,'wakeLock',{configurable:true,value:{request:async()=>{window.__wakeRequests+=1;let listener=null;return {released:false,addEventListener:(name,fn)=>{if(name==='release')listener=fn;},release:async function(){if(this.released)return;this.released=true;window.__wakeReleases+=1;listener?.();}};}}});
    Object.defineProperty(navigator,'vibrate',{configurable:true,value:pattern=>{window.__vibrations.push(pattern);return true;}});
    window.AudioContext=undefined;window.webkitAudioContext=undefined;
  });
}

test('lab runner keeps screen awake and never auto advances',async({page})=>{
  await mockWake(page);await page.goto(base,{waitUntil:'networkidle'});
  await expect.poll(()=>page.evaluate(()=>window.__wakeRequests)).toBeGreaterThan(0);
  await expect(page.locator('[data-runner-wake-status]')).toHaveText('Tela mantida ativa');
  const initialUrl=page.url();
  await page.locator('[data-runner-start]').click();
  await expect(page.locator('[data-runner-start]')).toBeDisabled();
  await page.waitForTimeout(2300);
  await expect(page.locator('[data-runner-clock]')).toHaveText('00:00');
  await expect(page.locator('[data-runner-cue]')).toHaveText('Tempo concluído');
  await expect(page.locator('[data-runner-complete]')).not.toHaveAttribute('aria-disabled','true');
  expect(page.url()).toBe(initialUrl);
  await expect(page.locator('.student-process-runner-next').getByText('Lavagem',{exact:true})).toBeVisible();
  expect(await page.evaluate(()=>window.__vibrations.length)).toBeGreaterThan(0);
});

test('pause releases wake lock and reset does not advance the process',async({page})=>{
  await mockWake(page);await page.goto(base,{waitUntil:'networkidle'});
  await page.locator('[data-runner-start]').click();await page.waitForTimeout(350);
  await page.locator('[data-runner-pause]').click();
  await expect.poll(()=>page.evaluate(()=>window.__wakeReleases)).toBeGreaterThan(0);
  await expect(page.locator('[data-runner-wake-status]')).toHaveText('Tela ativa pausada');
  const paused=await page.locator('[data-runner-clock]').textContent();
  await page.waitForTimeout(600);await expect(page.locator('[data-runner-clock]')).toHaveText(paused);
  await page.locator('[data-runner-reset]').click();
  await expect(page.locator('[data-runner-clock]')).toHaveText('00:02');
  await expect(page.locator('[data-runner-complete]')).toHaveAttribute('aria-disabled','true');
  await expect.poll(()=>page.evaluate(()=>window.__wakeRequests)).toBeGreaterThan(1);
});

test('lab runner stays within phone viewport',async({page})=>{
  await mockWake(page);await page.setViewportSize({width:390,height:844});await page.goto(base,{waitUntil:'networkidle'});
  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);
  await page.screenshot({path:'student-visual-audit/process-runner-phone.png',fullPage:true,animations:'disabled'});
});
