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
  await page.locator('.student-notes-panel').screenshot({path:'student-visual-audit/phone/material-notes-question.png',animations:'disabled'});
});




test('selection annotation composer is mobile-first and keeps the note action before optional question fields',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url,{waitUntil:'networkidle'});
  await page.evaluate(()=>{
    const panel=document.querySelector('.student-notes-panel');
    const compose=document.querySelector('[data-inline-note-compose]');
    panel.open=true;panel.classList.add('is-composing');compose.hidden=false;
  });
  const panel=page.locator('.student-notes-panel');
  const compose=page.locator('[data-inline-note-compose]');
  await expect(panel).toHaveClass(/is-composing/);
  await expect(page.locator('.student-notes-head')).toBeHidden();
  await expect(compose.locator('[data-note-question]')).not.toHaveAttribute('open','');
  const layout=await page.evaluate(()=>{
    const quote=document.querySelector('.student-inline-note-selected blockquote');
    const actions=document.querySelector('.student-inline-note-primary-actions');
    const question=document.querySelector('[data-inline-note-compose] [data-note-question]');
    const sheet=document.querySelector('.student-notes-sheet');
    const qr=quote.getBoundingClientRect(),ar=actions.getBoundingClientRect(),sr=sheet.getBoundingClientRect();
    return{
      quoteHeight:qr.height,
      quoteOverflow:getComputedStyle(quote).overflowY,
      actionTop:ar.top,
      actionBottom:ar.bottom,
      sheetTop:sr.top,
      sheetBottom:sr.bottom,
      actionBeforeQuestion:!!(actions.compareDocumentPosition(question)&Node.DOCUMENT_POSITION_FOLLOWING)
    };
  });
  expect(layout.quoteHeight,'selected quote must not consume most of the phone').toBeLessThanOrEqual(114);
  expect(['auto','scroll']).toContain(layout.quoteOverflow);
  expect(layout.actionBeforeQuestion,'saving the note must precede optional question fields').toBe(true);
  expect(layout.actionTop).toBeGreaterThanOrEqual(layout.sheetTop-1);
  expect(layout.actionBottom).toBeLessThanOrEqual(layout.sheetBottom+1);
  await page.screenshot({path:'student-visual-audit/phone/material-note-compose.png',fullPage:true,animations:'disabled'});
});

test('material desktop keeps question creation inside the notes panel',async({page})=>{
  await page.setViewportSize({width:1440,height:1000});
  await page.goto(url,{waitUntil:'networkidle'});
  await page.locator('.student-notes-panel>summary').click();
  await expect(page.locator('.student-notes-panel')).toHaveAttribute('open','');
  await page.getByText('Transformar em dúvida',{exact:true}).click();
  await expect(page.getByLabel('Título da dúvida').first()).toBeVisible();
  const panel=page.locator('.student-notes-panel');const box=await panel.boundingBox();expect(box).not.toBeNull();expect(box.x).toBeGreaterThanOrEqual(0);expect(box.x+box.width).toBeLessThanOrEqual(1440);
  await panel.screenshot({path:'student-visual-audit/desktop/material-notes-question.png',animations:'disabled'});
});