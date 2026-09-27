const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/editor-inspector-coherence.html';

test('section inspector groups identity layout access and actions in one hierarchy',async({page})=>{
  await page.setViewportSize({width:1200,height:850});
  await page.goto(url);
  const inspector=page.locator('#inspector');
  await expect(inspector.locator('[data-cms-access-controls]')).toBeVisible();
  const panel=inspector.locator('.inspector-section').filter({has:page.locator('#s-name')});
  await expect(panel).toHaveClass(/cms-section-inspector-coherent/);
  for(const group of ['identity','layout','actions'])await expect(panel.locator(`[data-cms-inspector-group="${group}"]`)).toHaveCount(1);
  await expect(panel.locator('[data-cms-access-controls]')).toHaveCount(1);
  await expect(panel).toContainText('Como esta seção aparece no editor');
  await expect(panel).toContainText('Composição da seção');
  await expect(panel).toContainText('Audiência e disponibilidade');
  await expect(panel).toContainText('Ações da seção');
  await expect(panel.locator('#cms-access-audience option')).toHaveText(['Todos os visitantes','Usuários autenticados','Participantes deste curso','Turma específica']);
  await expect(panel.locator('#cms-access-availability option')).toHaveText(['Imediatamente','Em data programada','Conforme liberação da aula']);

  await panel.locator('#cms-access-audience').selectOption('cohort');
  await panel.locator('#cms-access-cohort').selectOption('7');
  await panel.locator('#cms-access-availability').selectOption('lesson');
  await panel.locator('#cms-access-lesson').selectOption('11');
  const frame=page.frameLocator('#page-frame');
  await expect(frame.locator('section[data-cms-section="lesson-1"]')).toHaveAttribute('data-cms-access','cohort');
  await expect(frame.locator('section[data-cms-section="lesson-1"]')).toHaveAttribute('data-cms-cohort-id','7');
  await expect(frame.locator('section[data-cms-section="lesson-1"]')).toHaveAttribute('data-cms-availability','lesson');
  await expect(frame.locator('section[data-cms-section="lesson-1"]')).toHaveAttribute('data-cms-lesson-id','11');
});

test('page access saves on change without a competing save button',async({page})=>{
  await page.goto(url);
  await expect(page.locator('[data-cms-access-controls]')).toBeVisible();
  await page.frameLocator('#page-frame').locator('section').evaluate(el=>el.classList.remove('cms-section-selected'));
  await page.locator('#inspector').evaluate(inspector=>{inspector.innerHTML='<div class="inspector-section"><header><p class="eyebrow">Página</p><h2>Configurações</h2></header><label>Título interno<input id="p-title" value="Material"></label></div>'});
  const access=page.locator('[data-cms-page-access]');
  await expect(access).toBeVisible();
  await expect(access.locator('#cms-page-access-save')).toHaveCount(0);
  await access.locator('#cms-page-access').selectOption('authenticated');
  await expect.poll(()=>page.evaluate(()=>window.__lastAccessPayload?.access||'')).toBe('authenticated');
  await expect(access.locator('[data-page-access-status]')).toHaveText('Salvo');
});
