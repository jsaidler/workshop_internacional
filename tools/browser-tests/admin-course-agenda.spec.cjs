const {test,expect}=require('@playwright/test');
const fs=require('fs');
const url='http://127.0.0.1:8099/tools/browser-fixture/admin-course-agenda.php';
for(const [device,width,height] of [['phone',390,844],['narrow',320,720],['desktop',1440,1100]]){
 test('canonical admin cohort lesson editor and native save '+device,async({page})=>{
   await page.setViewportSize({width,height});
   const response=await page.goto(url+'?edit=101',{waitUntil:'networkidle'});
   expect(response?.status()).toBe(200);
   await expect(page.getByRole('heading',{name:'Aulas da turma'})).toBeVisible();
   const cards=page.locator('section.admin-release-card[id^="aula-"]');
   await expect(cards).toHaveCount(3);
   await expect(cards.nth(0)).toContainText('Link definido');
   await expect(cards.nth(1)).toContainText('Encontro sem data');
   await expect(cards.nth(1)).toContainText('Link pendente');
   await expect(cards.nth(2)).toContainText('Realizada');
   const first=page.locator('#aula-101');
   const editor=first.locator('details.admin-secondary-setup');
   await expect(editor).toHaveAttribute('open','');
   await expect(first.getByRole('heading',{name:'Características do filme e exposição'})).toBeVisible();
   await expect(editor.locator('[name="meeting_starts_at"]')).toHaveValue('2030-10-12T19:00');
   await expect(editor.locator('[name="meeting_url"]')).toHaveValue('https://example.org/meeting-private');
   await expect(editor.locator('[name="release_mode"]')).toHaveValue('keep');
   await expect(first).toContainText('Ver seções de material');
   const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-window.innerWidth);
   expect(overflow).toBeLessThanOrEqual(1);
   fs.mkdirSync('student-visual-audit/admin-agenda',{recursive:true});
   await first.scrollIntoViewIfNeeded();
   await page.screenshot({path:'student-visual-audit/admin-agenda/'+device+'-editor.png',animations:'disabled'});
   await editor.locator('[name="meeting_url"]').fill('https://example.org/updated');
   await editor.locator('[name="meeting_notes"]').fill('Usar link atualizado.');
   await editor.locator('[name="release_mode"]').selectOption('keep');
   const posts=[];
   await page.route('**/tools/browser-fixture/admin-course-agenda.php**',async route=>{
     if(route.request().method()==='POST'){
       posts.push(Object.fromEntries(new URLSearchParams(route.request().postData()||'')));
       await route.fulfill({status:303,headers:{location:'/tools/browser-fixture/admin-course-agenda.php?edit=101'}});
     }else await route.continue();
   });
   await editor.getByRole('button',{name:'Salvar aula da turma'}).click();
   await expect.poll(()=>posts.length).toBe(1);
   expect(posts[0].action).toBe('save_meeting');
   expect(posts[0].course_id).toBe('11');
   expect(posts[0].cohort_id).toBe('21');
   expect(posts[0].lesson_id).toBe('101');
   expect(posts[0].release_mode).toBe('keep');
   expect(posts[0].meeting_url).toBe('https://example.org/updated');
   const second=page.locator('#aula-102');
   await second.scrollIntoViewIfNeeded();
   await page.screenshot({path:'student-visual-audit/admin-agenda/'+device+'-undated.png',animations:'disabled'});
 });
}
