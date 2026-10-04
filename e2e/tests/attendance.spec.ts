import { expect, test } from '@playwright/test';
import { login, logout } from './helpers';

test.describe('attendance', () => {
  test('employee clocks in and out and sees today in their DTR', async ({ page }) => {
    await login(page, 'employee');
    await page.goto('/attendance/clock');

    await page.getByRole('button', { name: 'Clock in' }).click();
    await expect(page.locator('.alert-success')).toContainText('Time in recorded');

    await page.getByRole('button', { name: 'Clock out' }).click();
    await expect(page.locator('.alert-success')).toContainText('Time out recorded');
    await expect(page.getByRole('button', { name: 'Clock in' })).toBeVisible();

    // The day is recomputed by the queue worker; reload until it shows up.
    await expect(async () => {
      await page.goto('/attendance/mine');
      const today = new Date().toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', timeZone: 'Asia/Manila' });
      const row = page.locator('#dtr-table tbody tr', { hasText: today });
      await expect(row.getByRole('cell').nth(2)).not.toHaveText('—', { timeout: 1000 });
    }).toPass({ timeout: 30_000 });
  });

  test('HR records a manual punch with a reason', async ({ page }) => {
    await login(page, 'hr');
    await page.goto('/attendance/logs');

    const form = page.locator('form', { hasText: 'Add manual punch' });
    await form.getByLabel('Employee').selectOption({ index: 1 });
    await form.getByLabel('Date & time').fill('2026-09-15T08:00');
    await form.getByLabel('Type').selectOption('in');
    await form.getByLabel('Reason').fill('Biometric device offline');
    await form.getByRole('button', { name: 'Add punch' }).click();

    await expect(page.locator('.alert-success')).toContainText('Time log added');
  });

  test('employee requests leave and HR approves it', async ({ page }) => {
    // Pick next Monday so the request always contains a working day.
    const date = new Date();
    date.setDate(date.getDate() + ((8 - date.getDay()) % 7 || 7));
    const iso = date.toLocaleDateString('en-CA');

    await login(page, 'employee');
    await page.goto('/leaves');
    const form = page.locator('form', { hasText: 'Request leave' });
    await form.getByLabel('Leave type').selectOption({ label: 'Vacation Leave' });
    await form.getByLabel('From').fill(iso);
    await form.getByLabel('To').fill(iso);
    await form.getByLabel('Reason').fill('E2E family event');
    await form.getByRole('button', { name: 'Submit request' }).click();
    await expect(page.locator('.alert-success')).toContainText('Leave request for 1 day(s) submitted');
    await logout(page);

    await login(page, 'hr');
    await page.goto('/leaves/review');
    const row = page.locator('tr', { hasText: 'E2E family event' });
    await row.getByPlaceholder('Remarks').fill('Approved by E2E');
    await row.getByRole('button', { name: 'Approve' }).click();
    await expect(page.locator('.alert-success')).toContainText('Approved');
    await logout(page);

    await login(page, 'employee');
    await page.goto('/leaves');
    await expect(page.locator('tr', { hasText: 'Approved by E2E' }).locator('.badge')).toHaveText('Approved');
  });
});
