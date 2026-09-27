const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/student-login-hierarchy.html';

test('activated account login is presented before first access',async({page})=>{
  await page.goto(url);
  const login=page.getByTestId('login-card');
  const activation=page.getByTestId('activation-card');
  await expect(login).toBeVisible();
  await expect(activation).toBeVisible();
  const order=await page.evaluate(()=>{
    const a=document.querySelector('[data-testid="login-card"]');
    const b=document.querySelector('[data-testid="activation-card"]');
    return Boolean(a&&b&&(a.compareDocumentPosition(b)&Node.DOCUMENT_POSITION_FOLLOWING));
  });
  expect(order).toBe(true);
  const loginBox=await login.boundingBox();
  const activationBox=await activation.boundingBox();
  expect(loginBox.y).toBeLessThan(activationBox.y);
  await expect(login.getByRole('button',{name:'Entrar'})).toBeVisible();
  await expect(activation.getByRole('button',{name:'Ativar conta'})).toBeVisible();
});

test('login hierarchy stays single-column and usable on phone viewport',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url);
  const loginBox=await page.getByTestId('login-card').boundingBox();
  const activationBox=await page.getByTestId('activation-card').boundingBox();
  expect(loginBox.y).toBeLessThan(activationBox.y);
  expect(Math.abs(loginBox.x-activationBox.x)).toBeLessThanOrEqual(1);
  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);
});
