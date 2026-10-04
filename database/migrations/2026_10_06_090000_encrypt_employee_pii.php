<?php

use App\Shared\Security\BlindIndex;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const GOVERNMENT_IDS = ['sss_no', 'philhealth_no', 'pagibig_no', 'tin'];

    private const ENCRYPTED = ['birth_date', 'mobile', 'address', 'sss_no', 'philhealth_no', 'pagibig_no', 'tin', 'bank_account_name', 'bank_account_no'];

    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            foreach (self::GOVERNMENT_IDS as $column) {
                $table->dropUnique([$column]);
                $table->string("{$column}_bidx", 64)->nullable()->unique();
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            foreach (self::ENCRYPTED as $column) {
                $table->text($column)->nullable()->change();
            }
        });

        Schema::table('employee_documents', function (Blueprint $table) {
            $table->boolean('is_encrypted')->default(false)->after('size');
        });

        // Encrypt existing plaintext values (idempotent).
        DB::table('employees')->orderBy('id')->each(function (object $row) {
            $update = [];

            foreach (self::ENCRYPTED as $column) {
                $value = $row->{$column};

                if ($value === null || $value === '' || $this->isEncrypted($value)) {
                    continue;
                }

                $update[$column] = Crypt::encryptString((string) $value);
            }

            foreach (self::GOVERNMENT_IDS as $column) {
                $plain = $row->{$column} === null ? null : ($this->isEncrypted($row->{$column}) ? Crypt::decryptString($row->{$column}) : $row->{$column});
                $update["{$column}_bidx"] = BlindIndex::hash($column, $plain);
            }

            DB::table('employees')->where('id', $row->id)->update($update);
        });
    }

    public function down(): void
    {
        DB::table('employees')->orderBy('id')->each(function (object $row) {
            $update = [];

            foreach (self::ENCRYPTED as $column) {
                if ($row->{$column} !== null && $this->isEncrypted($row->{$column})) {
                    $update[$column] = Crypt::decryptString($row->{$column});
                }
            }

            if ($update !== []) {
                DB::table('employees')->where('id', $row->id)->update($update);
            }
        });

        Schema::table('employee_documents', fn (Blueprint $table) => $table->dropColumn('is_encrypted'));

        Schema::table('employees', function (Blueprint $table) {
            foreach (self::GOVERNMENT_IDS as $column) {
                $table->dropUnique(["{$column}_bidx"]);
                $table->dropColumn("{$column}_bidx");
                $table->unique([$column]);
            }
        });
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};
