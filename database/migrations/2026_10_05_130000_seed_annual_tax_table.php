<?php

use Database\Seeders\PayrollSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the annual withholding tax table to existing installations (the
     * seeder only inserts missing tables).
     */
    public function up(): void
    {
        if (Schema::hasTable('tax_brackets') && DB::table('tax_brackets')->exists()) {
            (new PayrollSeeder)->run();
        }
    }

    public function down(): void
    {
        DB::table('tax_brackets')->where('frequency', 'annual')->delete();
    }
};
