import { expect, type Locator, type Page } from '@playwright/test';

/** Demo accounts created by database/seeders/DemoSeeder.php. */
export const users = {
  admin: 'admin@example.com',
  hr: 'hr@example.com',
  payroll: 'payroll@example.com',
  employee: 'employee@example.com',
} as const;

export const DEMO_PASSWORD = 'password';

export async function login(page: Page, role: keyof typeof users): Promise<void> {
  await page.goto('/login');
  await page.locator('#email').fill(users[role]);
  await page.locator('#password').fill(DEMO_PASSWORD);
  await page.getByRole('button', { name: /sign in/i }).click();
  await expect(page).toHaveURL(/\/dashboard$/);
}

export async function logout(page: Page): Promise<void> {
  await page.locator('.user-menu > a.dropdown-toggle').click();
  await page.locator('.user-menu').getByRole('link', { name: /log ?out/i }).click();
  await expect(page).toHaveURL(/\/login$/);
}

/** A sidebar menu entry by its exact text (the link name also contains an icon glyph). */
export function menuLink(page: Page, text: string): Locator {
  return page
    .locator('.app-sidebar a.nav-link')
    .filter({ has: page.locator('p').getByText(text, { exact: true }) });
}

/** Accept the next window.confirm() dialog. */
export function acceptNextDialog(page: Page): void {
  page.once('dialog', (dialog) => dialog.accept());
}

/** The semi-monthly cutoff before the one containing today. */
export function previousCutoff(today = new Date()): { from: string; to: string } {
  const y = today.getFullYear();
  const m = today.getMonth();
  const pad = (n: number) => String(n).padStart(2, '0');
  const iso = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

  if (today.getDate() > 15) {
    return { from: iso(new Date(y, m, 1)), to: iso(new Date(y, m, 15)) };
  }

  return { from: iso(new Date(y, m - 1, 16)), to: iso(new Date(y, m, 0)) };
}
