const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/student-reciprocity.html';

test('reciprocity time is calculated on the client with the research exponent',async({page})=>{
  await page.goto(url);
  const source=page.locator('input[name="calculated_time"]');
  const output=page.locator('input[name="reciprocity_time"]');

  await expect(output).toHaveAttribute('readonly','');
  await expect(output).toHaveValue('');
  expect(await page.evaluate(()=>window.StudentReciprocity.RECIPROCITY_EXPONENT)).toBe(1.38542662);

  await source.fill('4');
  await expect(output).toHaveValue('6.825');

  await source.fill('4 s');
  await expect(output).toHaveValue('6.825 s');

  await source.fill('00:00:04');
  await expect(output).toHaveValue('00:00:06.825');

  await source.fill('00:00:32');
  await expect(output).toHaveValue('00:02:01.696');
});

test('student time notation is preserved whenever the notation can represent the result',async({page})=>{
  await page.goto(url);
  const source=page.locator('input[name="calculated_time"]');
  const output=page.locator('input[name="reciprocity_time"]');

  await source.fill('2 min');
  await expect(output).toHaveValue('12.659 min');

  await source.fill('4,0 s');
  await expect(output).toHaveValue('6,825 s');

  await source.fill('1/30s');
  await expect(output).toHaveValue('1/30s');

  await source.fill('0:00:00,5');
  await expect(output).toHaveValue('0:00:00,5');
});

test('invalid time input cannot leave a stale reciprocity value',async({page})=>{
  await page.goto(url);
  const source=page.locator('input[name="calculated_time"]');
  const output=page.locator('input[name="reciprocity_time"]');

  await source.fill('4 s');
  await expect(output).toHaveValue('6.825 s');
  await source.fill('quatro segundos');
  await expect(output).toHaveValue('');
  await expect(output).toHaveAttribute('data-reciprocity-invalid','true');
  await expect(output).toHaveAttribute('placeholder','Formato de tempo não reconhecido');
});
