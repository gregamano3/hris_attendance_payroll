import { expect, test } from '@playwright/test';
import { login, logout, menuLink } from './helpers';

test.describe('authentication', () => {
  test('redirects guests to the login page', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/login$/);
  });

  test('rejects invalid credentials', async ({ page }) => {
    await page.goto('/login');
    await page.locator('#email').fill('admin@example.com');
    await page.locator('#password').fill('wrong-password');
    await page.getByRole('button', { name: /sign in/i }).click();

    await expect(page.locator('.invalid-feedback')).toContainText(/credentials do not match/i);
  });

  test('admin logs in, sees every module and logs out', async ({ page }) => {
    await login(page, 'admin');

    for (const item of ['Dashboard', 'Employees', 'Time logs', 'Payroll runs', 'Statutory rates', 'Users']) {
      await expect(menuLink(page, item)).toBeVisible();
    }

    await logout(page);
  });

  test('employees only see self-service menus', async ({ page }) => {
    await login(page, 'employee');

    for (const item of ['Time clock', 'My attendance', 'My leaves', 'My payslips']) {
      await expect(menuLink(page, item)).toBeVisible();
    }
    for (const item of ['Employees', 'Users', 'Payroll runs', 'Time logs']) {
      await expect(menuLink(page, item)).toHaveCount(0);
    }

    const response = await page.goto('/employees');
    expect(response?.status()).toBe(403);
  });

  test('HR sees HRIS and attendance management but not payroll or users', async ({ page }) => {
    await login(page, 'hr');

    await expect(menuLink(page, 'Employees')).toBeVisible();
    await expect(menuLink(page, 'Leave approvals')).toBeVisible();
    await expect(menuLink(page, 'Payroll runs')).toHaveCount(0);
    await expect(menuLink(page, 'Users')).toHaveCount(0);
  });
});
