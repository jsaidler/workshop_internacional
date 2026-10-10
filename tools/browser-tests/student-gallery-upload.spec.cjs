const {test,expect}=require('@playwright/test');
const host='http://127.0.0.1:8099';
const jpg=n=>({name:`foto-${n}.jpg`,mimeType:'image/jpeg',buffer:Buffer.from([0xff,0xd8,0xff,0xd9])});

for(const [device,width,height] of [['phone',390,844],['desktop',1440,1100]]){
 test(`camera and multi-gallery are distinct in both record sections — ${device}`,async({page})=>{
    await page.setViewportSize({width,height});
    const fixture=await page.request.get(host+'/tools/browser-fixture/student-record-gallery.php');
    expect(fixture.ok()).toBeTruthy();
    const html=await fixture.text();
    const submissions=[];
    await page.route('**/aluno/teste.php**',async route=>{
      if(route.request().method()==='POST'){
        const raw=route.request().postDataBuffer()?.toString('latin1')||'';
        submissions.push(raw);
      }
      await route.fulfill({status:200,contentType:'text/html',body:html});
    });
    await page.goto(host+'/aluno/teste.php?gallery-fixture=1',{waitUntil:'networkidle'});
    const scene=page.locator('#exposicao'),result=page.locator('#resultado');
    for(const region of [scene,result]){
      const camera=region.locator('input[name="image"]');
      const gallery=region.locator('input[name="images[]"]');
      await expect(camera).toHaveAttribute('capture','environment');
      await expect(camera).not.toHaveAttribute('multiple');
      await expect(gallery).toHaveAttribute('multiple','');
      await expect(gallery).not.toHaveAttribute('capture');
      await expect(region.getByText('Fotografar',{exact:true})).toBeVisible();
      await expect(region.getByText('Escolher imagens',{exact:true})).toBeVisible();
      await expect(gallery).toHaveAttribute('accept','image/jpeg,image/png,image/webp');
      for(const label of await region.locator('.student-capture-form > label.student-capture-button').all()){
        const visual=await label.evaluate(el=>({height:el.getBoundingClientRect().height,display:getComputedStyle(el).display,border:getComputedStyle(el).borderTopWidth,font:parseFloat(getComputedStyle(el).fontSize)}));
        expect(visual.height,'tap target must be at least 48 px').toBeGreaterThanOrEqual(48);
        expect(['flex','inline-flex'],'layout must render as flex').toContain(visual.display);
        expect(visual.border).not.toBe('0px');
        expect(visual.font).toBeGreaterThanOrEqual(12);
      }
    }
    await scene.locator('input[name="images[]"]').setInputFiles([jpg(1),jpg(2)]);
    await expect.poll(()=>submissions.length).toBe(1);
    expect(submissions[0]).toContain('name="phase"');
    expect(submissions[0]).toContain('scene');
    expect(submissions[0]).toContain('filename="foto-1.jpg"');
    expect(submissions[0]).toContain('filename="foto-2.jpg"');
    expect(submissions[0]).toContain('name="images[]"');
    expect(submissions[0]).toContain('name="action"');
    expect(submissions[0]).toContain('upload');
    await result.locator('input[name="images[]"]').setInputFiles([jpg(3),jpg(4)]);
    await expect.poll(()=>submissions.length).toBe(2);
    expect(submissions[1]).toContain('result');
    expect(submissions[1]).toContain('filename="foto-3.jpg"');
    expect(submissions[1]).toContain('filename="foto-4.jpg"');
    await scene.locator('input[name="image"]').setInputFiles(jpg(5));
    await expect.poll(()=>submissions.length).toBe(3);
    expect(submissions[2]).toContain('name="image"');
    expect(submissions[2]).toContain('filename="foto-5.jpg"');
    expect(submissions[2]).toContain('scene');
    await scene.scrollIntoViewIfNeeded();
    await page.screenshot({path:`student-visual-audit/gallery/${device}-scene-buttons.png`,animations:'disabled'});
    await result.scrollIntoViewIfNeeded();
    await page.screenshot({path:`student-visual-audit/gallery/${device}-result-buttons.png`,animations:'disabled'});
 });
}
