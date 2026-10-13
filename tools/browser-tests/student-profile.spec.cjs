const {test,expect}=require('@playwright/test');
const url='http://127.0.0.1:8099/tools/browser-fixture/student-profile.php';
for(const width of [320,390,1440]){
 const label=width===1440?'desktop':width===390?'phone':'narrow';
 test('operational profile reduces first-screen form burden '+label,async({page})=>{
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.setViewportSize({width,height:width===320?720:width===390?844:1100});
  let response=await page.goto(url,{waitUntil:'networkidle'});
  expect(response?.status()).toBe(200);
  const form=page.locator('[data-student-profile-form]');
  await expect(form).toHaveCount(1);
  await expect(form.locator('[name="name"]')).toBeVisible();
  await expect(form.locator('[name="email"]')).toBeVisible();
  await expect(form.locator('[name="cpf"]')).toBeVisible();
  await expect(form.locator('[name="_csrf"]')).toHaveCount(1);
  const contact=page.locator('[data-profile-contact]'),address=page.locator('[data-profile-address]');
  await expect(contact).not.toHaveAttribute('open','');
  await expect(address).not.toHaveAttribute('open','');
  await expect(form.locator('[name="phone"]')).toBeHidden();
  await form.locator('[name="name"]').fill('Aluno Exemplo · rascunho');
  await contact.locator('summary').focus();await page.keyboard.press('Enter');
  await expect(contact).toHaveAttribute('open','');
  await form.locator('[name="phone"]').fill('(24) 98888-1234');
  await address.locator('summary').click();
  await expect(form.locator('[name="address"]')).toHaveValue('Rua Exemplo, 10');
  await form.locator('[name="address"]').fill('Rua Nova, 20');
  await contact.locator('summary').click();
  await expect(form.locator('[name="phone"]')).toHaveValue('(24) 98888-1234');
  await expect(form.locator('[name="name"]')).toHaveValue('Aluno Exemplo · rascunho');
  await expect(form.locator('[name="address"]')).toHaveValue('Rua Nova, 20');
  const layout=await page.evaluate(()=>{
   const main=document.querySelector('.student-main'),form=document.querySelector('[data-student-profile-form]'),
     mobileNav=document.querySelector('.student-mobile-nav');
   return {overflow:main.scrollWidth-main.clientWidth,
     gutter:getComputedStyle(main).scrollbarGutter,
     inset:main.getBoundingClientRect().right-form.getBoundingClientRect().right,
     navTop:mobileNav.getBoundingClientRect().top,mainBottom:main.getBoundingClientRect().bottom};
  });
  expect(layout.overflow).toBeLessThanOrEqual(1);
  expect(layout.gutter).toContain('stable');
  expect(layout.inset).toBeGreaterThanOrEqual(8);
  if(width<821)expect(layout.mainBottom).toBeLessThanOrEqual(layout.navTop+1);
  expect(errors).toEqual([]);
  await page.screenshot({path:'student-visual-audit/profile/'+label+'-expanded.png',animations:'disabled'});
  await contact.locator('summary').click();await address.locator('summary').click();
  await page.locator('.student-main').evaluate(n=>{n.scrollTop=0;});
  await page.screenshot({path:'student-visual-audit/profile/'+label+'-initial.png',animations:'disabled'});
  await form.getByRole('button',{name:'Salvar dados'}).scrollIntoViewIfNeeded();
  const controls=await page.evaluate(()=>{
    const form=document.querySelector('[data-student-profile-form]'),button=form.querySelector('button[type="submit"]'),main=document.querySelector('.student-main');
    return {buttonBottom:button.getBoundingClientRect().bottom,mainBottom:main.getBoundingClientRect().bottom,position:getComputedStyle(button).position};
  });
  expect(controls.buttonBottom).toBeLessThanOrEqual(controls.mainBottom+1);
  expect(controls.position).toBe('static');
  await page.screenshot({path:'student-visual-audit/profile/'+label+'-end.png',animations:'disabled'});
 });
}
test('profile failed update keeps original input values and opens details without exposing raw HTML',async({page})=>{
 await page.setViewportSize({width:390,height:844});
 const response=await page.goto(url+'?mode=error',{waitUntil:'networkidle'});
 expect(response?.status()).toBe(200);
 await expect(page.locator('[data-profile-error]')).toBeVisible();
 await expect(page.locator('[name="name"]')).toHaveValue('Nome em edição');
 await expect(page.locator('[data-profile-contact]')).toHaveAttribute('open','');
 await expect(page.locator('[data-profile-address]')).toHaveAttribute('open','');
 await expect(page.locator('[name="phone"]')).toHaveValue('(24) 99999-1111');
 await page.screenshot({path:'student-visual-audit/profile/phone-error.png',animations:'disabled'});
});
test('profile dedicated account route keeps security and logout separate from identity fields',async({page})=>{
 await page.goto(url);
 await expect(page.getByRole('link',{name:'Alterar senha'})).toHaveAttribute('href','/aluno/senha.php?next=%2Faluno%2Fperfil.php');
 await expect(page.locator('form[action="/aluno/logout.php"] [name="_csrf"]')).toHaveCount(1);
 await expect(page.locator('form[action="/aluno/logout.php"] button')).toHaveText('Sair da conta');
 await expect(page.locator('.student-mobile-nav a')).toHaveCount(5);
});
