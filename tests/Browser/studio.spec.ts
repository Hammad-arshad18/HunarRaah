import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
const php = process.env.PHP_BIN || 'php';
const fixture = (...args: string[]) =>
    execFileSync(php, ['tests/Support/browser-admin.php', ...args], {
        encoding: 'utf8',
    });
test('studio authentication design, responsive layout and no starter links', async ({
    page,
}, info) => {
    for (const width of [360, 768, 1280, 1440]) {
        await page.setViewportSize({ width, height: 900 });
        await page.goto('/login');
        await expect(
            page.getByRole('heading', { name: 'Welcome back.' }),
        ).toBeVisible();
        await expect(
            page.getByRole('link', {
                name: /Laravel|Documentation|Repository/i,
            }),
        ).toHaveCount(0);
        expect(
            await page.evaluate(
                () => document.documentElement.scrollWidth > window.innerWidth,
            ),
        ).toBe(false);
        await page
            .getByLabel('Password', { exact: true })
            .fill('visible-password-test');
        await page.getByRole('button', { name: 'Show password' }).click();
        await expect(
            page.getByLabel('Password', { exact: true }),
        ).toHaveAttribute('type', 'text');
        if (width === 1280 || width === 360)
            await page.screenshot({
                path: `.tools/login-${width}-${info.project.name}.png`,
                fullPage: true,
            });
    }
    await page.goto('/register');
    await expect(
        page.getByRole('heading', { name: 'Make a fresh start.' }),
    ).toBeVisible();
});

async function administratorLogin(page: import('@playwright/test').Page) {
 const admin=JSON.parse(fixture());
 await page.goto('/admin');
 await page.getByLabel('Email address').fill(admin.email);
 await page.getByLabel('Password',{exact:true}).fill(admin.password);
 await page.getByRole('button',{name:'Log in',exact:true}).click();
 await expect(page).toHaveURL(/two-factor-challenge/);
 await page.getByLabel('Authentication code').fill(fixture('otp',admin.secret).trim());
 await page.getByRole('button',{name:'Continue',exact:true}).click();
 await expect(page).toHaveURL(/admin$|confirm-password/);
 if(page.url().includes('confirm-password')) {
  await page.getByLabel('Password',{exact:true}).fill(admin.password);
  await page.getByRole('button',{name:'Confirm password',exact:true}).click();
 }
 await expect(page).toHaveURL(/\/admin$/);
 return admin;
}

test('Filament administration, real MFA, curriculum and responsive navigation',async({page},info)=>{
 test.setTimeout(120000);
 await administratorLogin(page);
 await expect(page.getByRole('heading',{name:'Your teaching desk'})).toBeVisible();
 await expect(page.getByRole('link',{name:/Laravel|Documentation|Repository/})).toHaveCount(0);
 await page.emulateMedia({reducedMotion:'reduce'});
 await page.waitForFunction(()=>getComputedStyle(document.querySelector('main')).opacity==='1');
 await page.screenshot({path:`.tools/filament-desk-${info.project.name}.png`,fullPage:true});
 await page.goto('/admin/courses/create');
 const slug=`filament-browser-${Date.now()}`;
 const courseTitle=`Development Filament course ${Date.now()}`;
 await page.getByLabel('Course title').fill(courseTitle);
 await page.getByLabel('Public URL slug').fill(slug);
 await page.getByLabel('Short summary').fill('Development fixture: a real learning journey.');
 await page.getByLabel('Full description (Markdown)').fill('A browser-tested course with meaningful reading.');
 const outcomes=page.getByLabel('Learning outcomes');
 await outcomes.fill('Build a clear teaching outline');await outcomes.press('Enter');
 await page.getByLabel('Instructor display name').fill('Development instructor');
 await page.getByLabel('Instructor biography').fill('Clearly labelled development fixture biography.');
 await page.getByRole('checkbox',{name:/I confirm accessible/}).check();
 await page.getByRole('button',{name:'Create',exact:true}).click();
 await expect(page).toHaveURL(/admin\/courses\/\d+\/edit/);
 await page.screenshot({path:`.tools/filament-course-${info.project.name}.png`,fullPage:true});
 const courseId=page.url().match(/courses\/(\d+)/)![1];
 await page.goto('/admin/modules');
 await page.getByRole('button',{name:'Add chapter',exact:true}).click();
 let dialog=page.getByRole('dialog').filter({has:page.getByRole('heading',{name:'Add chapter'})});
 await dialog.getByLabel('Course').selectOption(courseId);
 await dialog.getByLabel('Title').fill('Start with intention');
 await dialog.getByRole('button',{name:'Submit',exact:true}).click();
 await expect(dialog).toBeHidden();
 await expect(page.getByText('Start with intention',{exact:true}).last()).toBeVisible();
 await page.goto('/admin/lessons');
 await page.getByRole('button',{name:'Add lesson',exact:true}).click();
 dialog=page.getByRole('dialog').filter({has:page.getByRole('heading',{name:'Add lesson'})});
 await dialog.getByLabel('Chapter').selectOption({label:courseTitle+' / Start with intention'});
 await dialog.getByLabel('Lesson title').fill('Read and practice');
 await dialog.getByLabel('Text lesson content (Markdown)').fill('## Your next step\n\nWrite a meaningful course outcome.');
 await dialog.getByRole('switch',{name:'Published',exact:true}).click();
 await dialog.getByRole('button',{name:'Submit',exact:true}).click();
 await expect(dialog).toBeHidden();
 await page.goto(`/admin/courses/${courseId}/edit`);
 await page.getByRole('button',{name:'Publish',exact:true}).click();
 dialog=page.getByRole('dialog').filter({has:page.getByRole('heading',{name:'Publish'})});
 await dialog.getByRole('button',{name:'Confirm',exact:true}).click();
 await expect(dialog).toBeHidden();
 await page.goto(`/courses/${slug}`);
 await expect(page.getByRole('heading',{name:courseTitle,exact:true})).toBeVisible();
 await expect(page.getByText('Read and practice · text',{exact:true})).toBeVisible();
 await expect(page.getByText('Write a meaningful course outcome.',{exact:true})).toHaveCount(0);
 for(const section of ['students','enrollments','orders','certificates','audit-logs','failed-jobs']) {
  await page.goto(`/admin/${section}`);
  await expect(page.locator('.fi-header-heading')).toBeVisible();
  expect(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth)).toBe(false);
 }
 await page.goto('/admin');
 if(info.project.name==='mobile') {
  await page.getByRole('button',{name:'Open sidebar'}).click();
  await expect(page.getByRole('link',{name:'Courses',exact:true})).toBeVisible();
  await page.getByRole('link',{name:'Courses',exact:true}).click();
  await expect(page).toHaveURL(/\/admin\/courses$/);
 }
});

test('Filament complimentary enrollment and account restrictions',async({page})=>{
 test.setTimeout(90000);
 const admin=await administratorLogin(page);
 await page.goto('/admin/students');
 await page.getByPlaceholder('Search').fill(admin.student.email);
 const row=page.getByRole('row').filter({hasText:admin.student.email});
 await expect(row).toBeVisible();
 await row.getByRole('button',{name:'Suspend account'}).click();
 let dialog=page.getByRole('dialog').filter({has:page.getByRole('heading',{name:'Suspend account'})});
 await dialog.getByLabel('Reason for this change').fill('Development access review');
 await dialog.getByRole('button',{name:'Submit',exact:true}).click();
 await expect(dialog).toBeHidden();
 await row.getByRole('button',{name:'Restore account'}).click();
 dialog=page.getByRole('dialog').filter({has:page.getByRole('heading',{name:'Restore account'})});
 await dialog.getByLabel('Reason for this change').fill('Development review resolved');
 await dialog.getByRole('button',{name:'Submit',exact:true}).click();
 await expect(dialog).toBeHidden();
 await expect(row.getByRole('button',{name:'Suspend account'})).toBeVisible();
 await page.goto('/admin/enrollments');
 await page.getByRole('button',{name:'Complimentary enrollment'}).click();
 dialog=page.getByRole('dialog').filter({has:page.getByRole('heading',{name:'Complimentary enrollment'})});
 await dialog.getByLabel('Course').selectOption(String(admin.course_id));
 await dialog.getByLabel('Student').selectOption(String(admin.student.id));
 await dialog.getByLabel('Reason for this change').fill('Owner approved development scholarship');
 await dialog.getByRole('button',{name:'Submit',exact:true}).click();
 await expect(dialog).toBeHidden();
 await page.getByPlaceholder('Search').fill(admin.student.name);
 await expect(page.getByRole('row').filter({hasText:admin.student.name})).toBeVisible();
});
