const {test,expect}=require('@playwright/test');
const fixture='http://127.0.0.1:8099/tools/browser-fixture/admin-cohort-transfer.php';
for(const width of [320,390,1440]){
 const device=width===1440?'desktop':width===390?'phone':'narrow';
 for(const mode of ['manual','registration']){
  test('real transfer panel '+mode+' '+device,async({page})=>{
   const errors=[];page.on('pageerror',error=>errors.push(error.message));
   await page.setViewportSize({width,height:width===320?720:width===390?844:1100});
   const res=await page.goto(fixture+'?mode='+mode,{waitUntil:'networkidle'});
   expect(res?.status()).toBe(200);
   const form=page.locator('.admin-student-transfer form');
   await expect(form).toHaveCount(1);
   await expect(form.getByRole('combobox',{name:'Turma de destino'})).toBeVisible();
   await expect(form.getByRole('button',{name:'Confirmar transferência'})).toBeVisible();
   const target=form.locator('[name="target_cohort_id"]');
   await expect(target).toHaveValue('');
   const options=await target.locator('option').evaluateAll(nodes=>nodes.map(n=>({value:n.value,name:n.textContent.trim()})));
   expect(options).toEqual([{value:'',name:'Selecione uma turma'},{value:'21',name:'Turma de outubro'},{value:'23',name:'Turma de novembro'}]);
   const controlStyle=await target.evaluate(node=>{
     const select=node.getBoundingClientRect(),button=node.closest('form').querySelector('button[type="submit"]').getBoundingClientRect();
     return{selectHeight:select.height,buttonHeight:button.height,selectWidth:select.width,buttonWidth:button.width};
   });
   expect(controlStyle.selectHeight,'destination choice must have a 44px touch target').toBeGreaterThanOrEqual(44);
   expect(controlStyle.buttonHeight,'transfer submit must have a 44px touch target').toBeGreaterThanOrEqual(44);
   if(width<=800)expect(controlStyle.buttonWidth,'mobile transfer action should be full-width').toBeGreaterThanOrEqual(controlStyle.selectWidth-1);
   await target.selectOption('23');
   const request=await form.evaluate(form=>Object.fromEntries(new FormData(form)));
   expect(request.action).toBe(mode==='manual'?'move_manual_enrollment':'move_registration');
   expect(request.target_cohort_id).toBe('23');
   expect(request.course_id).toBe('11');
   expect(request._csrf).toBe('fixture-token-students-admin');
   if(mode==='manual'){
     expect(request.enrollment_id).toBe('41');
     expect(request.submission_id).toBeUndefined();
     await expect(page.getByRole('button',{name:'Desativar participação'})).toBeVisible();
     await expect(page.getByRole('link',{name:'Ver inscrição original'})).toHaveCount(0);
   }else{
     expect(request.submission_id).toBe('88');
     expect(request.enrollment_id).toBeUndefined();
     await expect(page.getByRole('link',{name:'Ver inscrição original'})).toBeVisible();
     await expect(page.getByRole('button',{name:'Desativar participação'})).toHaveCount(0);
   }
   const layout=await page.evaluate(()=>{
      const el=document.querySelector('.admin-student-transfer'),r=el.getBoundingClientRect();
      return{body:document.body.scrollWidth,doc:document.documentElement.scrollWidth,viewport:innerWidth,left:r.left,right:r.right,
      select:el.querySelector('select').getBoundingClientRect().right};
   });
   expect(layout.body).toBeLessThanOrEqual(layout.viewport+1);
   expect(layout.doc).toBeLessThanOrEqual(layout.viewport+1);
   expect(layout.left).toBeGreaterThanOrEqual(-1);
   expect(layout.right).toBeLessThanOrEqual(layout.viewport+1);
   expect(layout.select).toBeLessThanOrEqual(layout.viewport+1);
   await page.screenshot({path:'admin-visual-audit/cohort-transfer/'+device+'-'+mode+'.png',animations:'disabled'});
   expect(errors).toEqual([]);
  });
 }
}
test('no valid target is an explained unavailable action, not an invalid submit',async({page})=>{
 await page.setViewportSize({width:390,height:844});
 await page.goto(fixture+'?mode=no-target',{waitUntil:'networkidle'});
 await expect(page.getByText('Não há outra turma ativa neste curso para receber a transferência.')).toBeVisible();
 await expect(page.locator('.admin-student-transfer form')).toHaveCount(0);
 await page.screenshot({path:'admin-visual-audit/cohort-transfer/phone-no-target.png',animations:'disabled'});
});
