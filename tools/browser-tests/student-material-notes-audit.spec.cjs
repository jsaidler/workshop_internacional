const {test,expect}=require('@playwright/test');
const url='http://127.0.0.1:8099/tools/browser-fixture/student-material-notes.html';

test('material mobile keeps closed notes control out of reading viewport',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url,{waitUntil:'networkidle'});
  const closed=await page.evaluate(()=>{
    const main=document.querySelector('.material-audit');
    const panel=document.querySelector('.student-notes-panel');
    const ps=getComputedStyle(panel);
    const mr=main.getBoundingClientRect();
    const pr=panel.getBoundingClientRect();
    return {position:ps.position,panelTop:pr.top,mainBottom:mr.bottom};
  });
  expect(closed.position,'closed notes control must not float over the material').toBe('static');
  expect(closed.panelTop,'closed notes control must begin after material content').toBeGreaterThanOrEqual(closed.mainBottom-1);
  await page.screenshot({path:'student-visual-audit/phone/material-reading.png',fullPage:true,animations:'disabled'});
  await page.locator('.student-notes-panel>summary').click();
  await expect(page.locator('.student-notes-panel')).toHaveAttribute('open','');
  const opened=await page.locator('.student-notes-panel').evaluate(el=>getComputedStyle(el).position);
  expect(opened,'notes panel may become modal only after explicit opening').toBe('fixed');
  await expect(page.getByText('Transformar em dúvida',{exact:true})).toBeVisible();
  await expect(page.getByText('Também é uma dúvida?',{exact:true})).toBeVisible();
  await page.getByText('Transformar em dúvida',{exact:true}).click();
  await expect(page.getByLabel('Título da dúvida').first()).toBeVisible();
  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);expect(overflow,'open notes/question horizontal overflow').toBeLessThanOrEqual(1);
  await page.screenshot({path:'student-visual-audit/phone/material-notes-question.png',fullPage:true,animations:'disabled'});
});
