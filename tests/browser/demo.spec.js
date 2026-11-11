import { test, expect } from '@playwright/test';
import { mkdir } from 'node:fs/promises';
import { randomUUID } from 'node:crypto';

async function login(page, account) {
    await page.goto('/login');
    await page.getByLabel('Email', { exact: true }).fill(`${account}@webflix.example`);
    await page.getByLabel('Password', { exact: true }).fill('demo-password');
    await page.getByRole('button', { name: 'Sign In', exact: true }).click();
    await expect(page).not.toHaveURL(/\/login$/);
}

async function screenshot(page, name) {
    if (process.env.CAPTURE_DEMO_SCREENSHOTS !== '1') return;
    await mkdir('docs/screenshots', { recursive: true });
    await page.evaluate(() => document.fonts.ready);
    await page.screenshot({ path: `docs/screenshots/${name}.png`, fullPage: true });
}

async function checkout(page) {
    await page.goto('/subscribe/plans');
    await page.locator('.card').filter({ has: page.getByRole('heading', { name: 'Basic', exact: true }) })
        .getByRole('link', { name: 'Choose Plan', exact: true }).click();
    await expect(page.getByText('Rp55.999', { exact: true })).toBeVisible();
    await page.getByLabel('I have reviewed the plan and total.').check();
    await page.getByRole('button', { name: 'Continue to payment', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'No money will be charged', exact: true })).toBeVisible();
}

test('a member can browse the local catalog and open the plan-quality preview', async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await login(page, 'viewer');
    await page.goto('/movies');
    await expect(page.getByRole('heading', { name: 'Movies', exact: true })).toBeVisible();
    await expect(page.getByRole('img', { name: 'Night Signal', exact: true })).toBeVisible();
    await screenshot(page, 'catalog');
    await page.getByRole('link').filter({ has: page.getByRole('img', { name: 'Night Signal', exact: true }) }).click();
    await expect(page.getByRole('heading', { name: 'Night Signal', exact: true })).toBeVisible();
    await expect(page.frameLocator('iframe').getByText('Resolution: 1080p', { exact: true })).toBeVisible();
    await screenshot(page, 'movie');
    expect(errors).toEqual([]);
});

async function register(page) {
    await page.goto('/register');
    await page.getByLabel('Full Name', { exact: true }).fill('Browser Demo');
    await page.getByLabel('Email', { exact: true }).fill(`browser-${randomUUID()}@webflix.example`);
    await page.getByLabel('Password', { exact: true }).fill('demo-password');
    await page.getByLabel('Confirm Password', { exact: true }).fill('demo-password');
    await page.getByRole('button', { name: 'Register', exact: true }).click();
    await expect(page).toHaveURL(/\/subscribe\/plans$/);
}

test('cancellation does not activate a new account', async ({ page }) => {
    await register(page);
    await screenshot(page, 'plans');
    await checkout(page);
    await screenshot(page, 'checkout');
    await page.getByRole('button', { name: 'Simulate cancellation', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Payment failed', exact: true })).toBeVisible();
    await page.goto('/movies/night-signal');
    await expect(page).toHaveURL(/\/subscribe\/plans$/);
});

test('successful demo payment activates a new accounts selected plan', async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await register(page);
    await checkout(page);
    await page.getByRole('button', { name: 'Simulate successful payment', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Your membership is ready', exact: true })).toBeVisible();
    await page.getByRole('link', { name: 'Browse movies', exact: true }).click();
    await page.getByRole('link').filter({ has: page.getByRole('img', { name: 'Night Signal', exact: true }) }).click();
    await expect(page.frameLocator('iframe').getByText('Resolution: 720p', { exact: true })).toBeVisible();
    expect(errors).toEqual([]);
});

for (const account of ['new', 'expired']) {
    test(`${account} account cannot open a movie by its URL`, async ({ page }) => {
        await login(page, account);
        await page.goto('/movies/night-signal');
        await expect(page).toHaveURL(/\/subscribe\/plans$/);
        await expect(page.getByText('Choose an active plan to watch this movie.', { exact: true })).toBeVisible();
    });
}
