const {test,expect}=require('@playwright/test');

const host='http://127.0.0.1:8099';
const viewports={desktop:{width:1440,height:1100},phone:{width:390,height:844}};
const unitKeys=[
  'caderno-20a-agora-e-a-vez',
  'caderno-21-fluxo-pesquisa','caderno-22-exposicao','caderno-23-processamento',
  'caderno-24-resultado-avaliacao','caderno-25-comparacao','caderno-26-continuar',
  'caderno-27-ferramentas','caderno-28-rotina',
];
const practiceKeys=[
  'caderno-aula-3','caderno-21-fluxo-pesquisa','caderno-22-exposicao','caderno-23-processamento',
  'caderno-24-resultado-avaliacao','caderno-25-comparacao','caderno-26-continuar',
  'caderno-27-ferramentas','caderno-28-rotina',
];
// These assertions protect both screenshot readability and the pedagogical reading
// hierarchy. Human inspection of the full-page desktop and phone renders is still required.

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
    await expect(page.getByRole('heading',{name:'Preparação para o terceiro encontro',exact:true})).toHaveCount(0);

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

    // Although the historical DOM keys still say aula3, student-facing Prática is work
    // done after Aula 2. Its material must therefore unlock with Aula 2, not with the
    // third live meeting.
    for(const key of practiceKeys){
      const section=page.locator(`[data-cms-section="${key}"]`);
      await expect(section,`${key} must be released with Aula 2`).toHaveAttribute('data-cms-lesson-id','2');
    }

    const forbiddenHeadings=[
      'Depois de acompanhar o processo completo, o próximo passo é fazer as próprias fotografias e produzir os próprios resultados.',
      'Não é preciso chegar com uma fotografia “certa”. Precisamos chegar com resultados que possamos observar, reconstruir e discutir.',
      'O registro só vale a pena quando ajuda a entender o que aconteceu e a escolher conscientemente o que mudar depois.',
      'Registre a exposição que foi feita, não apenas a conta que levou até ela.',
      'Roteiro associado e processamento realizado são duas coisas diferentes.',
      'Comparar serve para enxergar diferenças registradas. Não para inventar uma causa que o experimento não demonstrou.',
      'Use uma tentativa anterior como ponto de partida, mas declare o que pretende investigar antes de produzir o próximo resultado.',
    ];
    const headingTexts=await page.locator('[data-cms-section^="caderno-2"] h2').allTextContents();
    for(const heading of forbiddenHeadings)expect(headingTexts).not.toContain(heading);

    const closing=page.locator('[data-cms-section="caderno-28-rotina"]');
    await expect(closing).toContainText('No terceiro encontro, vamos partir das experiências realizadas durante este período.');
    await expect(closing).toContainText('Não é necessário chegar a um resultado “correto”');

    await expect(page.locator('[data-aula3-screenshot]')).toHaveCount(9);
    await expect(page.locator('[data-aula3-screenshot] .cms-media-placeholder')).toHaveCount(0);
    const images=page.locator('[data-aula3-screenshot] img');
    await expect(images).toHaveCount(9);
    const metrics=await images.evaluateAll(nodes=>nodes.map(img=>({naturalWidth:img.naturalWidth,naturalHeight:img.naturalHeight,width:img.getBoundingClientRect().width,height:img.getBoundingClientRect().height})));
    for(const [index,metric] of metrics.entries()){
      expect(metric.naturalWidth,`screenshot ${index+1} source is too narrow`).toBeGreaterThanOrEqual(1000);
      expect(metric.naturalHeight,`screenshot ${index+1} source is unexpectedly shallow`).toBeGreaterThanOrEqual(300);
      expect(metric.width,`screenshot ${index+1} is rendered too small on ${device}`).toBeGreaterThanOrEqual(device==='desktop'?700:300);
    }
    const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
    expect(overflow,`Aula 2/Prática horizontal overflow on ${device}`).toBeLessThanOrEqual(1);
    await page.screenshot({path:`student-visual-audit/material/${device}/aula2-aula3-final.png`,fullPage:true,animations:'disabled'});
  });
}
