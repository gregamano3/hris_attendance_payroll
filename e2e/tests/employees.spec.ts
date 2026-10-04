import { expect, test } from '@playwright/test';
import { acceptNextDialog, login } from './helpers';

test.describe('employee records', () => {
  test('HR creates, edits, finds and archives an employee', async ({ page }) => {
    const suffix = Date.now().toString().slice(-6);
    const employeeNo = `E2E-${suffix}`;

    await login(page, 'hr');
    await page.goto('/employees/create');

    await page.getByLabel('First name').fill('Gregoria');
    await page.getByLabel('Last name').fill(`De Jesus ${suffix}`);
    await page.getByLabel('Employee no.').fill(employeeNo);
    await page.getByLabel('Department').selectOption({ label: 'Information Technology' });
    await page.getByLabel('Employment type').selectOption('probationary');
    await page.getByLabel('Hire date').fill('2026-09-01');
    await page.getByLabel('Basic rate (₱)').fill('32000');
    await page.getByLabel('SSS No.').fill('34-0000000-1');
    await page.getByLabel('TIN').fill('123-456-789');
    await page.getByRole('button', { name: 'Create employee' }).click();

    await expect(page.locator('.alert-success')).toContainText('created');
    await expect(page.getByText('34-0000000-1')).toBeVisible();
    await expect(page.getByText('₱32,000.00 / month')).toBeVisible();

    await page.getByRole('link', { name: 'Edit' }).first().click();
    await page.locator('#status').selectOption('on_leave');
    await page.getByRole('button', { name: 'Save changes' }).click();
    await expect(page.locator('.badge', { hasText: 'On leave' }).first()).toBeVisible();

    await page.goto(`/employees?search=${employeeNo}`);
    await expect(page.getByRole('cell', { name: employeeNo })).toBeVisible();
    await page.getByRole('link', { name: new RegExp(`De Jesus ${suffix}`) }).click();

    acceptNextDialog(page);
    await page.getByRole('button', { name: 'Archive' }).click();
    await expect(page.locator('.alert-success')).toContainText('archived');

    await page.goto(`/employees?search=${employeeNo}`);
    await expect(page.getByText('No employees found.')).toBeVisible();
  });

  test('validates government ID formats', async ({ page }) => {
    await login(page, 'hr');
    await page.goto('/employees/create');

    await page.getByLabel('First name').fill('Invalid');
    await page.getByLabel('Last name').fill('Ids');
    await page.getByLabel('Basic rate (₱)').fill('20000');
    await page.getByLabel('PhilHealth No.').fill('123');
    await page.getByRole('button', { name: 'Create employee' }).click();

    await expect(page.getByText('The PhilHealth No. must have 12 digits.')).toBeVisible();
  });
});
