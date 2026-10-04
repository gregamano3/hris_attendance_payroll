<?php

namespace App\Features\Security\Reencrypt;

use App\Features\Employees\Enums\GovernmentId;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Models\EmployeeDocument;
use App\Models\User;
use App\Shared\Security\BlindIndex;
use App\Shared\Security\EncryptedFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Re-encrypts all PII with the current APP_KEY and recomputes blind indexes.
 *
 * Key rotation: move the old key to APP_PREVIOUS_KEYS, set a new APP_KEY,
 * run this command, then remove the old key.
 */
class ReencryptCommand extends Command
{
    protected $signature = 'security:reencrypt {--dry-run : Only count the records}';

    protected $description = 'Re-encrypt PII (employees, 2FA secrets, documents) with the current key and refresh blind indexes.';

    private const EMPLOYEE_FIELDS = ['birth_date', 'mobile', 'address', 'sss_no', 'philhealth_no', 'pagibig_no', 'tin', 'bank_account_name', 'bank_account_no'];

    public function handle(): int
    {
        if ($this->option('dry-run')) {
            $this->info(sprintf('%d employees, %d users with 2FA, %d documents would be re-encrypted.',
                Employee::withTrashed()->count(), User::query()->whereNotNull('two_factor_secret')->count(), EmployeeDocument::query()->count()));

            return self::SUCCESS;
        }

        $counts = ['employees' => 0, 'users' => 0, 'documents' => 0];

        Employee::withTrashed()->chunkById(200, function ($employees) use (&$counts) {
            foreach ($employees as $employee) {
                $values = [];

                foreach (self::EMPLOYEE_FIELDS as $field) {
                    $values[$field] = $employee->getAttribute($field); // decrypted with the current or a previous key
                }

                $employee->forceFill($values);

                foreach (GovernmentId::cases() as $id) {
                    $employee->setAttribute($id->blindIndexColumn(), BlindIndex::hash($id->value, $employee->getAttribute($id->value)));
                }

                // Bypass model events: this is a technical rewrite, not a business change.
                DB::table('employees')->where('id', $employee->id)->update(array_intersect_key(
                    $employee->getAttributes(),
                    array_flip([...self::EMPLOYEE_FIELDS, ...array_map(fn (GovernmentId $id) => $id->blindIndexColumn(), GovernmentId::cases())]),
                ));
                $counts['employees']++;
            }
        });

        User::query()->whereNotNull('two_factor_secret')->each(function (User $user) use (&$counts) {
            $user->forceFill([
                'two_factor_secret' => $user->two_factor_secret,
                'two_factor_recovery_codes' => $user->two_factor_recovery_codes,
            ]);
            DB::table('users')->where('id', $user->id)->update(array_intersect_key($user->getAttributes(), array_flip(['two_factor_secret', 'two_factor_recovery_codes'])));
            $counts['users']++;
        });

        EmployeeDocument::query()->each(function (EmployeeDocument $document) use (&$counts) {
            $disk = Storage::disk(EmployeeDocument::DISK);

            if (! $disk->exists($document->path)) {
                return;
            }

            $contents = $document->is_encrypted ? EncryptedFiles::get(EmployeeDocument::DISK, $document->path) : (string) $disk->get($document->path);
            EncryptedFiles::put(EmployeeDocument::DISK, $document->path, $contents);
            DB::table('employee_documents')->where('id', $document->id)->update(['is_encrypted' => true]);
            $counts['documents']++;
        });

        $this->info("Re-encrypted {$counts['employees']} employees, {$counts['users']} 2FA secrets and {$counts['documents']} documents.");

        return self::SUCCESS;
    }
}
