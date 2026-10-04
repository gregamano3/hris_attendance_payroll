import { expect, test } from '@playwright/test';
import { acceptNextDialog, login, logout, previousCutoff } from './helpers';

test.describe('payroll', () => {
  test('payroll officer runs, finalizes and the employee receives a payslip', async ({ page }) => {
    const { from, to } = previousCutoff();

    await login(page, 'payroll');
    await page.goto('/payroll/runs/create');
    const form = page.locator('#regular');
    await form.getByLabel('Period start').fill(from);
    await form.getByLabel('Period end').fill(to);
    await form.getByLabel('Pay date').fill(to);
    await form.getByRole('button', { name: 'Create run' }).click();
    await expect(page.locator('.alert-success')).toContainText('Payroll run created');

    // Computation runs on the queue; the page polls and reloads when it is done.
    await page.locator('#compute-button').click();
    await expect(page.locator('#payslips-table')).toContainText('Dela Cruz, Juan', { timeout: 60_000 });
    await expect(page.locator('#finalize-button')).toBeVisible();

    // Register export
    const download = page.waitForEvent('download');
    await page.getByRole('link', { name: 'Register' }).click();
    expect((await download).suggestedFilename()).toMatch(/^payroll-register-\d{8}\.csv$/);

    acceptNextDialog(page);
    await page.locator('#finalize-button').click();
    await expect(page.locator('.alert-success')).toContainText('Payroll finalized');
    await expect(page.locator('#compute-button')).toHaveCount(0);
    await logout(page);

    await login(page, 'employee');
    await page.goto('/payroll/my-payslips');
    await page.getByRole('link', { name: 'View' }).first().click();
    await expect(page.getByText('NET PAY')).toBeVisible();
    await expect(page.getByText('SSS contribution')).toBeVisible();
    await expect(page.getByText('Withholding tax')).toBeVisible();

    const pdf = page.waitForEvent('download');
    await page.getByRole('link', { name: 'Download PDF' }).click();
    expect((await pdf).suggestedFilename()).toMatch(/^payslip-.+\.pdf$/);
  });

  test('payroll officer can review statutory rates', async ({ page }) => {
    await login(page, 'payroll');
    await page.goto('/payroll/statutory-rates');

    await expect(page.getByRole('heading', { name: 'SSS' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'PhilHealth' })).toBeVisible();
    await expect(page.getByRole('heading', { name: /Withholding tax — semi-monthly/ })).toBeVisible();
  });
});
