import { execSync } from 'node:child_process';

/**
 * Resets the database before the run. E2E_RESET_CMD is provided by
 * `make e2e` and the CI workflow; set E2E_SKIP_RESET=1 to keep the data.
 */
export default function globalSetup(): void {
  if (process.env.E2E_SKIP_RESET === '1') {
    return;
  }

  const command = process.env.E2E_RESET_CMD ?? 'docker compose exec -T app php artisan migrate:fresh --seed --force';

  console.log(`[e2e] Resetting database: ${command}`);
  execSync(command, { stdio: 'inherit', cwd: '..' });
}
