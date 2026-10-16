const {test,expect}=require('@playwright/test');
const host='http://127.0.0.1:8099/tools/browser-fixture/';
const samples=[
  {name:'Início',url:'student-home.php?state=ongoing',root:'[data-student-home]',title:'.student-v2-heading h1',body:'.student-v2-row-text strong',card:'.student-v2-next',secondary:'.student-v2-secondary-link'},
  {name:'Cursos',url:'student-courses.php?state=detail',root:'[data-student-courses]',title:'.student-v2-course-detail-heading h1',body:'.student-academic-material-title',card:'.student-v2-course-page.is-next',secondary:'.student-v2-course-local-nav'},
  {name:'Caderno',url:'student-notebook.php?state=mixed',root:'[data-student-notebook]',title:'.student-v2-notebook-header h1',body:'.student-v2-notebook-card .student-record-summary span',card:'.student-v2-notebook-card',secondary:'.student-v2-notebook-card .student-record-menu>summary'},
  {name:'Processamentos',url:'student-process-saved-cards.php?mode=library',root:'[data-process-saved-card]',title:'.student-process-template-main h3',body:'.student-process-template-main p',card:'.student-process-template-card',secondary:'.student-process-card-options>summary'}
];
for(const [viewport,width,height] of [['phone',390,844],['desktop',1440,1100]]){
  for(const sample of samples){
    test(sample.name+' mantém Design CMS e remove contornos concorrentes — '+viewport,async({page})=>{
      await page.setViewportSize({width,height});
      const response=await page.goto(host+sample.url,{waitUntil:'networkidle'});
      expect(response?.status()).toBe(200);
      await expect(page.locator(sample.root).first()).toBeVisible();
      const layout=await page.evaluate(sample=>{
        const css=selector=>getComputedStyle(document.querySelector(selector));
        const root=getComputedStyle(document.documentElement);
        const first=font=>font.split(',')[0].trim().replace(/^[\"']|[\"']$/g,'').toLowerCase();
        const border=style=>['Top','Right','Bottom','Left'].map(s=>parseFloat(style['border'+s+'Width'])||0);
        const title=css(sample.title),body=css(sample.body),card=css(sample.card),secondary=css(sample.secondary);
        return {title:first(title.fontFamily),titleToken:first(root.getPropertyValue('--title')),
          body:first(body.fontFamily),bodyToken:first(root.getPropertyValue('--body')),
          cardBorders:border(card),secondaryBorders:border(secondary),
          cardRadius:parseFloat(card.borderTopLeftRadius)||0,
          overflowing:document.documentElement.scrollWidth>document.documentElement.clientWidth+1};
      },sample);
      expect(layout.title).toBe(layout.titleToken);
      expect(layout.body).toBe(layout.bodyToken);
      expect(layout.overflowing).toBe(false);
      if(sample.name==='Início'){
        expect(layout.cardBorders).toEqual([0,0,0,3]);
        expect(layout.secondaryBorders).toEqual([0,0,0,0]);
      }
      if(sample.name==='Cursos'){
        expect(layout.cardBorders[1]).toBe(0);
        expect(layout.cardBorders[3]).toBe(3);
        expect(layout.secondaryBorders[1]).toBe(0);
      }
      if(sample.name==='Caderno'){
        expect(layout.cardBorders).toEqual([0,0,1,0]);
        expect(layout.secondaryBorders).toEqual([0,0,0,0]);
      }
      if(sample.name==='Processamentos'){
        expect(layout.cardBorders).toEqual([0,0,1,0]);
        expect(layout.cardRadius).toBe(0);
        expect(layout.secondaryBorders).toEqual([0,0,0,0]);
      }
    });
  }
}
test('uma revisão solicitada é mais urgente que encontro distante',async({page})=>{
  await page.goto(host+'student-home.php?state=active');
  await expect(page.locator('[data-home-feedback-feature]')).toBeVisible();
  await expect(page.locator('[data-home-upcoming-meeting]')).toBeVisible();
  await expect(page.locator('[data-home-upcoming-meeting]')).toHaveAttribute('href','/aluno/agenda.php');
  await expect(page.locator('[data-home-meeting]')).toHaveCount(0);
});
test('encontro em andamento mantém acesso prioritário',async({page})=>{
  await page.goto(host+'student-home.php?state=ongoing');
  await expect(page.locator('[data-home-meeting]')).toBeVisible();
  await expect(page.locator('[data-home-meeting] .student-v2-cta')).toHaveAttribute('href','https://example.org/private-meeting');
});
test('Cursos oferece uma leitura liberada como próxima ação, sem promover conteúdo bloqueado',async({page})=>{
  await page.goto(host+'student-courses.php?state=detail');
  await expect(page.locator('.student-v2-course-page.is-next')).toHaveCount(1);
  await expect(page.locator('.student-v2-course-page.is-next')).toContainText('Abrir material');
  await expect(page.locator('.student-v2-course-page.is-waiting.is-next')).toHaveCount(0);
});
