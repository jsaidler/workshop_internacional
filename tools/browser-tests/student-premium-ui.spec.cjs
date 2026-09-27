const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/student-premium-ui.html';

test('student area shares the canonical theme preference',async({page})=>{
  await page.goto(url);
  await page.evaluate(()=>localStorage.setItem('workshop-theme','dark'));
  await page.reload();
  await expect(page.locator('html')).toHaveAttribute('data-theme','dark');
  await expect(page.locator('[data-theme-value="dark"]')).toHaveAttribute('aria-pressed','true');
  await page.locator('[data-theme-value="light"]').click();
  await expect(page.locator('html')).toHaveAttribute('data-theme','light');
  expect(await page.evaluate(()=>localStorage.getItem('workshop-theme'))).toBe('light');
});

test('student validation leaves an inline accessible error instead of a transient browser bubble',async({page})=>{
  await page.goto(url);
  await page.getByRole('button',{name:'Continuar'}).click();
  const email=page.locator('#fixture-email');
  await expect(email).toHaveAttribute('aria-invalid','true');
  await expect(email.locator('xpath=..').locator('.student-field-error')).toContainText('Preencha este campo');
  await expect(email).toBeFocused();
  await email.fill('invalido');
  await page.getByRole('button',{name:'Continuar'}).click();
  await expect(email.locator('xpath=..').locator('.student-field-error')).toContainText('e-mail válido');
  await email.fill('aluno@example.com');
  await expect(email).not.toHaveAttribute('aria-invalid','true');
});

test('student controls use governed geometry and normalized selection chrome',async({page})=>{
  await page.goto(url);
  const buttonBox=await page.getByRole('button',{name:'Continuar'}).boundingBox();
  expect(buttonBox.height).toBeGreaterThanOrEqual(53);
  const selectAppearance=await page.locator('#fixture-select').evaluate(node=>getComputedStyle(node).appearance);
  expect(selectAppearance).toBe('none');
  await page.getByText('Curso',{exact:true}).click();
  const courseChoice=page.locator('input[name="visibility"][value="course"]');
  await expect(courseChoice).toBeChecked();
});

test('premium controls remain usable without horizontal overflow on phone viewport',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url);
  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);
  const choiceBox=await page.getByText('Privado',{exact:true}).boundingBox();
  expect(choiceBox.height).toBeGreaterThanOrEqual(47);
  const topbarBox=await page.locator('.student-topbar').boundingBox();
  expect(topbarBox.width).toBeLessThanOrEqual(390);
});
