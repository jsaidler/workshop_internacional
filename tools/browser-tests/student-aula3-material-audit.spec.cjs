const {test,expect}=require('@playwright/test');

const host='http://127.0.0.1:8099';
const viewports={desktop:{width:1440,height:1100},phone:{width:390,height:844}};
const unitKeys=[
  'caderno-20a-agora-e-a-vez',
  'caderno-21-fluxo-pesquisa','caderno-22-exposicao','caderno-23-processamento',
  'caderno-24-resultado-avaliacao','caderno-25-comparacao','caderno-26-continuar',
  'caderno-28-rotina',
];
const practiceKeys=[
  'caderno-aula-3','caderno-21-fluxo-pesquisa','caderno-22-exposicao','caderno-23-processamento',
  'caderno-24-resultado-avaliacao','caderno-25-comparacao','caderno-26-continuar','caderno-28-rotina',
];
const placeholderLabels=[
  'Infográfico — Exposição → Processamento → Resultado',
  'Infográfico — O Caderno como registro da prática',
  'Infográfico — O que registrar na exposição',
  'Infográfico — Roteiro previsto x processamento realmente realizado',
  'Infográfico — Da tentativa ao resultado avaliado',
  'Infográfico — Comparação descritiva entre duas tentativas',
  'Infográfico — Continuidade da pesquisa e próxima tentativa',
];

for(const [device,viewport] of Object.entries(viewports)){
  test(`Aula 2/Prática final material visual audit ${device}`,async({page})=>{
    await page.setViewportSize(viewport);
    await page.goto(host+'/tools/browser-fixture/student-aula3-material-audit.php',{waitUntil:'networkidle'});
    await expect(page.getByTestId('aula3-material-audit')).toBeVisible();

    const chapter=page.locator('[data-cms-section="caderno-aula-3"]');
    await expect(chapter).toHaveClass(/\bstudy-chapter\b/);
    await expect(chapter.locator('h2')).toHaveText('Prática');
    await expect(chapter.locator('.section-label')).toHaveText('Entre o segundo e o terceiro encontro');
    await expect(chapter).not.toContainText('Aula 03');

    await expect(page.locator('[data-cms-section="caderno-20b-antes-terceiro-encontro"]')).toHaveCount(0);
    await expect(page.locator('[data-cms-section="caderno-27-ferramentas"]')).toHaveCount(0);
    await expect(page.getByRole('heading',{name:'Preparação para o terceiro encontro',exact:true})).toHaveCount(0);
    await expect(page.getByRole('heading',{name:'Ferramentas da área do aluno',exact:true})).toHaveCount(0);

    for(const key of unitKeys){
      const unit=page.locator(`[data-cms-section="${key}"]`);
      await expect(unit,`${key} must use the study-material unit pattern`).toHaveClass(/\bstudy-unit\b/);
      const classes=await unit.getAttribute('class');
      expect((classes||'').split(/\s+/),`${key} must not fall back to generic landing-page .section`).not.toContain('section');
      const background=await unit.getAttribute('data-layout-background');
      expect(background,`${key} must not carry promotional surface/inverse bands`).toBeNull();
      const h2=unit.locator('h2').first();
      if(await h2.count()){
        const fontSize=parseFloat(await h2.evaluate(node=>getComputedStyle(node).fontSize));
        expect(fontSize,`${key} heading is too large for teaching material on ${device}`).toBeLessThanOrEqual(43);
      }
    }

    for(const key of practiceKeys){
      const section=page.locator(`[data-cms-section="${key}"]`);
      await expect(section,`${key} must be released with Aula 2`).toHaveAttribute('data-cms-lesson-id','2');
    }

    const exposure=page.locator('[data-cms-section="caderno-22-exposicao"]');
    await expect(exposure).toContainText('O registro deve guardar o que efetivamente aconteceu na fotografia.');
    await expect(exposure).not.toContainText('Abra um registro no Caderno');

    const processing=page.locator('[data-cms-section="caderno-23-processamento"]');
    await expect(processing).toContainText('só o que foi executado entra como fato experimental');
    await expect(processing).not.toContainText('Em Processamentos você pode guardar sequências');

    const continuation=page.locator('[data-cms-section="caderno-26-continuar"]');
    await expect(continuation.locator('[data-study-tools-invite]')).toHaveCount(1);
    await expect(continuation.locator('[data-study-tools-invite]')).toContainText('ferramentas complementares de cálculo, consulta e organização do laboratório');
    await expect(continuation.locator('[data-study-tools-invite]')).toContainText('não substituem o registro de cada tentativa');

    const closing=page.locator('[data-cms-section="caderno-28-rotina"]');
    await expect(closing).toContainText('No terceiro encontro, vamos partir das experiências realizadas durante este período.');
    await expect(closing).toContainText('Não é necessário chegar a um resultado “correto”');

    const placeholders=page.locator('[data-study-image-placeholder]');
    await expect(placeholders).toHaveCount(7);
    for(const label of placeholderLabels)await expect(page.getByText(label,{exact:true})).toHaveCount(1);
    await expect(page.locator('[data-aula3-screenshot]')).toHaveCount(0);
    await expect(page.locator('[data-private-media-slot^="aula3-"]')).toHaveCount(0);
    await expect(page.locator('[data-private-media-slot="aula2-caderno"]')).toHaveCount(0);

    const placeholderMetrics=await placeholders.evaluateAll(nodes=>nodes.map(node=>({width:node.getBoundingClientRect().width,height:node.getBoundingClientRect().height})));
    for(const [index,metric] of placeholderMetrics.entries()){
      expect(metric.width,`placeholder ${index+1} is rendered too narrow on ${device}`).toBeGreaterThanOrEqual(device==='desktop'?700:300);
      expect(metric.height,`placeholder ${index+1} needs enough editorial breathing room`).toBeGreaterThanOrEqual(180);
    }

    const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
    expect(overflow,`Aula 2/Prática horizontal overflow on ${device}`).toBeLessThanOrEqual(1);
    await page.screenshot({path:`student-visual-audit/material/${device}/aula2-aula3-final.png`,fullPage:true,animations:'disabled'});
  });
}
