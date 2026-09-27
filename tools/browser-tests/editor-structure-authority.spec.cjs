const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/editor-structure-authority.html';

test('sidebar tree remains canonical and duplicate inspector trees are removed',async({page})=>{
  await page.goto(url,{waitUntil:'domcontentloaded'});
  await expect(page.locator('#page-structure-tree')).toContainText('Bloco canônico');
  await expect(page.locator('#inspector .cms-structure-tree')).toHaveCount(0);
  await expect(page.locator('#inspector')).toHaveAttribute('data-cms-structure-authority','sidebar');
});

test('duplicate inspector tree cannot reappear after inspector mutations',async({page})=>{
  await page.goto(url,{waitUntil:'domcontentloaded'});
  await page.evaluate(()=>{
    const tree=document.createElement('div');
    tree.className='cms-structure-tree';
    tree.textContent='Duplicada tardia';
    document.querySelector('#inspector').append(tree);
  });
  await expect(page.locator('#inspector .cms-structure-tree')).toHaveCount(0);
  await expect(page.locator('#page-structure-tree')).toContainText('Bloco canônico');
});
