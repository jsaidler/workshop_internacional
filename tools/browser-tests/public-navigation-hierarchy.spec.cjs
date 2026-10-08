const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/public-navigation-hierarchy.html';

test('desktop public navigation presents child pages as submenu',async({page})=>{
  await page.setViewportSize({width:1440,height:900});
  await page.goto(url,{waitUntil:'networkidle'});

  const parent=page.locator('[data-cms-nav-parent]').first();
  const submenu=parent.locator('.cms-nav-submenu-wrap');
  const toggle=parent.locator('[data-cms-submenu-toggle]');

  await expect(page.locator('#cms-primary-nav')).toBeVisible();
  await expect(submenu).toBeHidden();
  await parent.hover();
  await expect(submenu).toBeVisible();
  await expect(submenu.getByText('Inscrição',{exact:true})).toHaveAttribute('aria-current','page');

  await page.screenshot({path:'test-results/public-navigation-hierarchy-desktop.png',animations:'disabled'});
  await toggle.click();
  await expect(toggle).toHaveAttribute('aria-expanded','true');
  await expect(parent).toHaveClass(/is-submenu-open/);
});

test('mobile public navigation expands hierarchy vertically without overflow',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url,{waitUntil:'networkidle'});

  const header=page.locator('[data-cms-public-header]');
  const menu=page.locator('.cms-nav-toggle');
  const parent=page.locator('[data-cms-nav-parent]').first();
  const toggle=parent.locator('[data-cms-submenu-toggle]');
  const submenu=parent.locator('.cms-nav-submenu-wrap');

  await expect(page.locator('#cms-primary-nav')).toBeHidden();
  await menu.click();
  await expect(header).toHaveClass(/cms-nav-open/);
  await expect(page.locator('#cms-primary-nav')).toBeVisible();
  await expect(parent.getByText('Workshop de positivo',{exact:true})).toBeVisible();
  await expect(submenu).toBeHidden();

  await toggle.click();
  await expect(toggle).toHaveAttribute('aria-expanded','true');
  await expect(toggle).toHaveAttribute('aria-label','Fechar submenu de Workshop de positivo');
  await expect(submenu).toBeVisible();
  await expect(submenu.getByText('Inscrição',{exact:true})).toBeVisible();

  const geometry=await page.evaluate(()=>({
    overflow:document.documentElement.scrollWidth-document.documentElement.clientWidth,
    parentWidth:document.querySelector('[data-cms-nav-parent]').getBoundingClientRect().width,
    navWidth:document.querySelector('#cms-primary-nav').getBoundingClientRect().width
  }));
  expect(geometry.overflow).toBeLessThanOrEqual(1);
  expect(geometry.parentWidth).toBeLessThanOrEqual(geometry.navWidth+1);

  await page.screenshot({path:'test-results/public-navigation-hierarchy-mobile.png',animations:'disabled'});

  await toggle.click();
  await expect(toggle).toHaveAttribute('aria-expanded','false');
  await expect(submenu).toBeHidden();
});
