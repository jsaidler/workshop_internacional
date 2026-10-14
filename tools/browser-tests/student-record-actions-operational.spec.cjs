const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-record-actions-operational.php';
const nav=['/aluno/','/aluno/agenda.php','/aluno/cursos.php','/aluno/caderno.php','/aluno/ferramentas.php'];
for(const [name,width,height] of [['narrow',320,720],['phone',390,844],['desktop',1440,1000]]){
 for(const mode of ['step','delete','step-locked','step-error','delete-error']){
  test(name+' canonical record action '+mode,async({page})=>{
   await page.setViewportSize({width,height});
   const errors=[];page.on('pageerror',error=>errors.push(error.message));
   const response=await page.goto(base+'?mode='+mode,{waitUntil:'networkidle'});
   expect(response?.status()).toBe(200);
   await expect(page.locator('body.student-app .student-shell')).toHaveCount(1);
   const links=await page.locator('.student-mobile-nav a').evaluateAll(all=>all.map(a=>a.getAttribute('href')));
   expect(links).toEqual(nav);
   await expect(page.locator('.student-mobile-nav a[aria-current="page"]')).toHaveAttribute('href','/aluno/caderno.php');
   const step=mode.startsWith('step');
   await expect(page.locator('.student-appbar a.student-back, .student-record-context a.student-back')).toHaveAttribute('href',step?'/aluno/teste.php?id=7#processamento':'/aluno/teste.php?id=7');
   if(step){
    await expect(page.locator('.student-step-editor form [name="action"][value="save"]')).toHaveCount(1);
    await expect(page.locator('.student-step-editor form [name="_csrf"]')).toHaveCount(mode==='step-locked'?1:2); 
    if(mode==='step-error'){
      await expect(page.locator('.student-step-editor input[name="label"]')).toHaveValue('Primeira revelação corrigida');
      await expect(page.locator('.student-step-editor input[name="duration"]')).toHaveValue('06:20');
      await expect(page.locator('.student-step-editor textarea[name="notes"]')).toHaveValue('Rascunho não salvo <teste>');
    }
    if(mode==='step-locked'){
      await expect(page.locator('input[name="label"]')).toBeDisabled();
      await expect(page.locator('.student-step-danger')).toHaveCount(0);
      await expect(page.getByRole('button',{name:'Salvar alterações'})).toHaveCount(0);
    }else{
      await expect(page.getByRole('button',{name:'Salvar alterações'})).toBeVisible();
      await expect(page.locator('.student-step-danger')).toHaveCount(1);
      await page.locator('.student-step-danger summary').click();
      await expect(page.locator('.student-step-danger form [name="action"][value="delete"]')).toHaveCount(1);
      await expect(page.locator('.student-step-danger button')).toBeVisible();
    }
   }else{
    await expect(page.getByRole('button',{name:'Excluir registro definitivamente'})).toBeVisible();
    await expect(page.locator('.student-danger-zone form [name="_csrf"]')).toHaveCount(1);
    const destructive=page.locator('.student-danger-zone .button-danger');
    for(const theme of ['dark','light']){
      await page.evaluate(next=>document.documentElement.setAttribute('data-theme',next),theme);
      const colors=await destructive.evaluate(button=>{
        const style=getComputedStyle(button);
        const rgb=value=>{const numbers=(value.match(/[0-9.]+/g)||[]).slice(0,3).map(Number);return value.startsWith('color(srgb')?numbers.map(n=>n*255):numbers;};
        const luminance=value=>{
          const channels=rgb(value).map(n=>n/255).map(c=>c<=0.04045?c/12.92:Math.pow((c+0.055)/1.055,2.4));
          return .2126*channels[0]+.7152*channels[1]+.0722*channels[2];
        };
        const a=luminance(style.color),b=luminance(style.backgroundColor);
        return{contrast:(Math.max(a,b)+.05)/(Math.min(a,b)+.05),foreground:style.color,background:style.backgroundColor};
      });
      expect(colors.contrast,`${name} ${mode} ${theme}: destructive CTA must meet WCAG AA text contrast`).toBeGreaterThanOrEqual(4.5);
      const sizes=await destructive.evaluate(button=>({font:parseFloat(getComputedStyle(button).fontSize),height:button.getBoundingClientRect().height}));
      expect(sizes.font).toBeGreaterThanOrEqual(12);
      expect(sizes.height).toBeGreaterThanOrEqual(44);
    }
    await page.evaluate(()=>document.documentElement.setAttribute('data-theme','dark'));
   }
   if(mode.endsWith('error'))await expect(page.locator('[role="alert"]')).toBeVisible();
   const geom=await page.evaluate(()=>{const main=document.querySelector('.student-main'),n=document.querySelector('.student-mobile-nav');return{gutter:getComputedStyle(main).scrollbarGutter,overflow:main.scrollWidth-main.clientWidth,docOverflow:document.documentElement.scrollWidth-innerWidth,mainBottom:main.getBoundingClientRect().bottom,navTop:n.getBoundingClientRect().top};});
   expect(geom.gutter).toContain('stable');
   expect(geom.overflow).toBeLessThanOrEqual(1);
   expect(geom.docOverflow).toBeLessThanOrEqual(1);
   if(width<821)expect(geom.mainBottom).toBeLessThanOrEqual(geom.navTop+1);
   await page.screenshot({path:'student-visual-audit/record-actions/'+name+'-'+mode+'.png',animations:'disabled'});
   expect(errors).toEqual([]);
  });
 }
}
