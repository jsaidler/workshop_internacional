const {test,expect}=require('@playwright/test');
const path=require('node:path');
const fs=require('node:fs');
const root=path.resolve(__dirname,'../..');
const bundle=path.join(root,'assets/vendor/email-builder/lab.js');
const css=path.join(root,'assets/vendor/email-builder/grapes.min.css');

test('GrapesJS MJML builder: real components, compilation, round trip, safe rejection',async({page},info)=>{
  test.setTimeout(90000);
  expect(fs.existsSync(bundle),'Run npm run build:email-lab before browser validation').toBe(true);
  const errors=[];
  page.on('pageerror',e=>errors.push(e.message));
  await page.setViewportSize({width:1440,height:900});
  await page.setContent('<div data-communications-email-lab>'
    +'<button data-lab-add-text>Inserir texto</button><button data-lab-add-columns>Duas colunas</button>'
    +'<button data-lab-export>Gerar prévia</button><button data-lab-reopen>Testar reabertura</button>'
    +'<button data-lab-undo>Desfazer</button><button data-lab-redo>Refazer</button>'
    +'<button data-lab-desktop>Computador</button><button data-lab-mobile>Celular</button>'
    +'<p data-lab-status></p><div data-lab-canvas></div>'
    +'<select data-lab-media></select><input data-lab-media-alt><p data-lab-media-status></p>'
    +'<button data-lab-add-media>Imagem</button><iframe data-lab-preview sandbox></iframe>'
    +'<textarea data-lab-source></textarea></div>');
  await page.addStyleTag({path:css});
  await page.addScriptTag({path:bundle});
  await expect.poll(()=>page.evaluate(()=>Boolean(window.__communicationsEmailLab?.ready))).toBe(true);
  await expect.poll(()=>page.evaluate(()=>window.__communicationsEmailLab.editor.BlockManager.getAll().length)).toBeGreaterThan(1);
  await expect.poll(()=>page.evaluate(()=>window.__communicationsEmailLab.editor.getHtml().includes('<mjml'))).toBe(true);
  const first=await page.evaluate(()=>window.__communicationsEmailLab.exportEmail());
  expect(first.mjml).toContain('mj-section');
  expect(first.mjml).toContain('mj-button');
  expect(first.errors).toEqual([]);
  expect(first.html).toMatch(/<table\b/i);
  expect(first.html).not.toMatch(/<mj-column\b/i);
  await page.locator('[data-lab-add-columns]').click();
  console.log('TWO-COLUMN DIAGNOSTIC',JSON.stringify(await page.evaluate(()=>({mjml:window.__communicationsEmailLab.editor.getHtml(),status:document.querySelector('[data-lab-status]')?.textContent,columns:window.__communicationsEmailLab.editor.getWrapper().find('mj-column').map(x=>x.get('tagName'))}))));
  await expect.poll(()=>page.evaluate(()=>window.__communicationsEmailLab.editor.getHtml().match(/<mj-column\b/g)?.length||0)).toBeGreaterThanOrEqual(3);
  const changed=await page.evaluate(()=>window.__communicationsEmailLab.exportEmail());
  expect(changed.errors).toEqual([]);
  expect(changed.html).toMatch(/<table\b/i);
  const reopened=await page.evaluate(()=>window.__communicationsEmailLab.roundTrip());
  expect(reopened.before).toBe(reopened.after);
  await page.locator('[data-lab-export]').click();
  await expect(page.locator('[data-lab-status]')).toContainText('Nenhum e-mail foi enviado');
  expect(await page.locator('[data-lab-source]').inputValue()).toContain('<mjml');
  await page.screenshot({path:info.outputPath('mjml-builder-desktop.png'),fullPage:true});
  await page.setViewportSize({width:390,height:844});
  await page.screenshot({path:info.outputPath('mjml-builder-mobile.png'),fullPage:true});
  // Explicitly adding an external image must NEVER yield exportable HTML.
  const invalid=await page.evaluate(()=>{
    const ed=window.__communicationsEmailLab.editor;
    const col=ed.getWrapper().find('mj-column')[0];
    col.append('<mj-image src="https://tracking.example.org/pixel.png" alt="Tracker" />');
    try{window.__communicationsEmailLab.exportEmail();return 'accepted';}
    catch(e){return e.message;}
  });
  expect(invalid).toMatch(/Biblioteca pública/);
  expect(errors).toEqual([]);
});
