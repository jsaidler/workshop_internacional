const {test,expect}=require('@playwright/test');
const url='http://127.0.0.1:8099/tools/browser-fixture/student-shared-record-operational.php';
const nav=['/aluno/','/aluno/agenda.php','/aluno/cursos.php','/aluno/caderno.php','/aluno/ferramentas.php'];
for(const [device,width,height] of [['narrow',320,720],['phone',390,844],['desktop',1440,1000]]){
  for(const mode of ['filled','empty','unsafe']){
    test('shared classmate record '+device+' '+mode,async({page})=>{
      await page.setViewportSize({width,height});
      const errors=[];page.on('pageerror',error=>errors.push(error.message));
      const response=await page.goto(url+'?mode='+mode,{waitUntil:'networkidle'});
      expect(response?.status()).toBe(200);
      await expect(page.locator('body.student-app .student-shell')).toHaveCount(1);
      await expect(page.locator('link[href^="/assets/student-app-shell.css"]')).toHaveCount(1);
      const links=await page.locator('nav.student-mobile-nav a').evaluateAll(nodes=>nodes.map(n=>n.getAttribute('href')));
      expect(links).toEqual(nav);
      await expect(page.locator('nav.student-mobile-nav a[aria-current="page"]')).toHaveAttribute('href','/aluno/caderno.php');
      await expect(page.locator('.student-main .student-appbar a.student-back')).toHaveAttribute('href','/aluno/caderno.php');
      await expect(page.getByRole('heading',{name:'Avaliação e conversa'})).toBeVisible();
      await expect(page.locator('.student-main .student-review-grid article')).toHaveCount(2);
      await expect(page.locator('.student-main .student-review-media > div')).toHaveCount(2);
      const reviewColumns=await page.evaluate(()=>{
        const summary=document.querySelector('.student-review-grid'),media=document.querySelector('.student-review-media');
        return[summary,media].map(node=>getComputedStyle(node).gridTemplateColumns.split(' ').filter(Boolean).length);
      });
      expect(reviewColumns,width<=820?'phone reviews must stack':'desktop reviews stay side by side').toEqual(width<=820?[1,1]:[2,2]);
      await expect(page.locator('.student-main form')).toHaveCount(0);
      await expect(page.locator('.student-main button')).toHaveCount(0);
      await expect(page.locator('.student-main a[href*="excluir"]')).toHaveCount(0);
      if(mode==='empty'){
        await expect(page.getByText('Ainda não há mensagens neste registro.')).toBeVisible();
        await expect(page.locator('.student-process-summary li')).toHaveCount(0);
        await expect(page.locator('.student-review-grid')).toContainText('Branqueador');
      }else{
        await expect(page.locator('.student-process-summary li')).toHaveCount(3);
        await expect(page.locator('.student-thread article')).toHaveCount(2);
      }
      if(mode==='unsafe'){
        await expect(page.getByRole('heading',{name:'Título <script>alert(1)</script>'})).toBeVisible();
        await expect(page.locator('.student-main script,.student-main img[onerror],.student-main svg[onload]')).toHaveCount(0);
      }
      const metrics=await page.evaluate(()=>{
        const main=document.querySelector('.student-main'),navigation=document.querySelector('.student-mobile-nav');
        return{docOverflow:document.documentElement.scrollWidth-document.documentElement.clientWidth,
          overflow:main.scrollWidth-main.clientWidth,gutter:getComputedStyle(main).scrollbarGutter,
          mainBottom:main.getBoundingClientRect().bottom,navTop:navigation.getBoundingClientRect().top};
      });
      expect(metrics.docOverflow).toBeLessThanOrEqual(1);
      expect(metrics.overflow).toBeLessThanOrEqual(1);
      expect(metrics.gutter).toContain('stable');
      if(width<821)expect(metrics.mainBottom).toBeLessThanOrEqual(metrics.navTop+1);
      await page.screenshot({path:'student-visual-audit/shared-record/'+device+'-'+mode+'-start.png',animations:'disabled'});
      const scroll=page.locator('.student-main');
      await scroll.evaluate(el=>{el.scrollTop=el.scrollHeight/2;});
      await page.screenshot({path:'student-visual-audit/shared-record/'+device+'-'+mode+'-middle.png',animations:'disabled'});
      await scroll.evaluate(el=>{el.scrollTop=el.scrollHeight;});
      await page.screenshot({path:'student-visual-audit/shared-record/'+device+'-'+mode+'-end.png',animations:'disabled'});
      expect(errors).toEqual([]);
    });
  }
}
