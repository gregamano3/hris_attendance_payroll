<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->boolean('annualize_tax')->default(false)->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', fn (Blueprint $table) => $table->dropColumn('annualize_tax'));
    }
};
