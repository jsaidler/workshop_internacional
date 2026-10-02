const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-lab-stock-product-audit.html';
const screens=['inventory','empty','movement','preparations','editor'];
const viewports={desktop:{width:1440,height:1100},phone:{width:390,height:844}};
for(const [device,viewport] of Object.entries(viewports)){
  for(const screen of screens){
    test(`lab stock product visual audit ${device} ${screen}`,async({page})=>{
      await page.setViewportSize(viewport);
      await page.goto(`${base}?screen=${screen}`,{waitUntil:'networkidle'});
      await page.evaluate(()=>document.documentElement.setAttribute('data-theme','dark'));
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
      expect(overflow,`${screen} horizontal overflow on ${device}`).toBeLessThanOrEqual(1);
      if(screen==='inventory'){
        await expect(page.getByText('existem fisicamente',{exact:false})).toBeVisible();
        await expect(page.getByText('180',{exact:true})).toBeVisible();
        await expect(page.getByText('Registrar entrada ou saída').first()).toBeVisible();
        await expect(page.getByRole('button',{name:'Arquivar'}).first()).toBeVisible();
      }
      if(screen==='empty')await expect(page.getByText('Seu inventário ainda está vazio.')).toBeVisible();
      if(screen==='movement'){
        await expect(page.getByText('Entrada aumenta o saldo; saída reduz.',{exact:false})).toBeVisible();
        await expect(page.getByRole('button',{name:'Registrar movimentação'})).toBeVisible();
      }
      if(screen==='preparations'){
        await expect(page.getByText('não representa estoque físico',{exact:false}).first()).toBeVisible();
        await expect(page.getByText('Parodinal 10/550 · EI 200')).toBeVisible();
        await expect(page.getByRole('button',{name:'Excluir'}).first()).toBeVisible();
      }
      if(screen==='editor'){
        await expect(page.getByText('salvando parâmetros, não preparando estoque',{exact:false})).toBeVisible();
        await expect(page.getByRole('button',{name:'Salvar alterações'})).toBeVisible();
      }
      await page.screenshot({path:`student-visual-audit/${device}/lab-stock-${screen}.png`,fullPage:true,animations:'disabled'});
    });
  }
}
