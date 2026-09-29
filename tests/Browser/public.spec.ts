import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
test('catalog navigation, keyboard focus and safe public content', async ({
    page,
}) => {
    await page.goto('/');
    await expect(
        page.getByRole('heading', { name: /Make room for/ }),
    ).toBeVisible();
    await page.keyboard.press('Tab');
    await expect(
        page.getByRole('link', { name: 'Skip to content' }),
    ).toBeFocused();
    await page.getByRole('link', { name: 'Explore the courses' }).click();
    await expect(page.getByLabel('Search courses')).toBeVisible();
    await page.getByLabel('Search courses').fill('Build a clear project brief');
    await page.getByRole('button', { name: 'Find courses' }).click();
    await expect(page).toHaveURL(/q=Build/);
    await page
        .getByRole('link', { name: 'Explore course ↗', exact: true })
        .first()
        .click();
    await expect(
        page.getByRole('heading', { name: 'A clear set of outcomes.' }),
    ).toBeVisible();
    await expect(page.getByText('PROTECTED-LESSON-BODY')).toHaveCount(0);
    expect(
        await page.evaluate(
            () => document.documentElement.scrollWidth > window.innerWidth,
        ),
    ).toBe(false);
});
test('registration labels and consent exist', async ({ page }) => {
    await page.goto('/register');
    await expect(page.getByLabel('Name', { exact: true })).toBeVisible();
    await expect(page.getByLabel('Email address')).toBeVisible();
    await expect(page.getByRole('checkbox')).toBeVisible();
});
test('registration verification learning and private certificate journey', async ({
    page,
}, info) => {
    test.setTimeout(60000);
    await page.goto('/register');
    await page
        .getByLabel('Name', { exact: true })
        .fill('Browser Journey Student');
    await page
        .getByLabel('Email address')
        .fill(`browser-${Date.now()}@example.test`);
    await page
        .getByLabel('Password', { exact: true })
        .fill('a-long-browser-password');
    await page.getByLabel('Confirm password').fill('a-long-browser-password');
    await page.getByRole('checkbox').check();
    await page.getByRole('button', { name: 'Create account' }).click();
    await expect(page).toHaveURL(/email\/verify/);
    const log = readFileSync('storage/logs/laravel.log', 'utf8');
    const links = [
        ...log.matchAll(
            /http:\/\/127\.0\.0\.1:8000\/email\/verify\/\d+\/[a-f0-9]+\?expires=\d+&signature=[a-f0-9]+/g,
        ),
    ];
    expect(links.length).toBeGreaterThan(0);
    await page.goto(links.at(-1)![0]);
    await expect(page).toHaveURL(/dashboard/);
    await page.goto('/courses/sample-course-1');
    await page.getByRole('button', { name: 'Enroll free' }).click();
    await expect(
        page.getByRole('heading', { name: 'Understand the brief' }),
    ).toBeVisible();
    await page.screenshot({
        path: `.tools/classroom-${info.project.name}.png`,
        fullPage: true,
    });
    await page.getByRole('checkbox').check();
    await page.getByRole('button', { name: 'Mark complete' }).click();
    await expect(
        page.getByText('Lesson completed.', { exact: true }),
    ).toBeVisible();
    await page.getByRole('link', { name: 'Next lesson' }).click();
    await expect(
        page.getByRole('heading', { name: 'Make your first outline' }),
    ).toBeVisible();
    await page.getByRole('checkbox').check();
    await page.getByRole('button', { name: 'Mark complete' }).click();
    await page.getByRole('button', { name: 'Request certificate' }).click();
    await expect(page).toHaveURL(/certificates\//);
    execFileSync(
        process.env.PHP_BIN || 'php',
        ['artisan', 'queue:work', '--stop-when-empty', '--tries=1'],
        { stdio: 'pipe' },
    );
    await page.reload();
    await expect(
        page.getByRole('link', { name: 'Download PDF' }),
    ).toBeVisible();
    await page.getByRole('checkbox').check();
    await expect(page.getByRole('checkbox')).toBeChecked();
    await expect(
        page.getByRole('button', { name: 'Copy verification link' }),
    ).toBeEnabled();
});
