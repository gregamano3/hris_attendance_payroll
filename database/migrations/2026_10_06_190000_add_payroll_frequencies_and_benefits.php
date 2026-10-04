<?php

use Database\Seeders\PayrollSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_days', function (Blueprint $table) {
            $table->unsignedSmallInteger('night_diff_ot_minutes')->default(0)->after('night_diff_minutes');
        });

        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->string('frequency', 20)->default('semi_monthly')->after('type');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('pay_frequency', 20)->default('semi_monthly')->after('rate_type');
        });

        Schema::create('de_minimis_benefits', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->unsignedBigInteger('limit_amount'); // centavos
            $table->string('period', 10); // monthly | annual
            $table->timestamps();
        });

        Schema::table('recurring_earnings', function (Blueprint $table) {
            $table->foreignId('de_minimis_benefit_id')->nullable()->after('tax_treatment')->constrained()->nullOnDelete();
        });

        // Weekly withholding table and default de minimis ceilings for existing installs.
        if (DB::table('tax_brackets')->exists()) {
            (new PayrollSeeder)->run();
        }
    }

    public function down(): void
    {
        Schema::table('recurring_earnings', fn (Blueprint $table) => $table->dropConstrainedForeignId('de_minimis_benefit_id'));
        Schema::dropIfExists('de_minimis_benefits');
        Schema::table('employees', fn (Blueprint $table) => $table->dropColumn('pay_frequency'));
        Schema::table('payroll_runs', fn (Blueprint $table) => $table->dropColumn('frequency'));
        Schema::table('attendance_days', fn (Blueprint $table) => $table->dropColumn('night_diff_ot_minutes'));
        DB::table('tax_brackets')->where('frequency', 'weekly')->delete();
    }
};
