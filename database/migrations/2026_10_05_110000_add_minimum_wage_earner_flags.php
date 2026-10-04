<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('is_minimum_wage_earner')->default(false)->after('basic_rate');
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->boolean('is_minimum_wage_earner')->default(false)->after('hourly_rate');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', fn (Blueprint $table) => $table->dropColumn('is_minimum_wage_earner'));
        Schema::table('employees', fn (Blueprint $table) => $table->dropColumn('is_minimum_wage_earner'));
    }
};
