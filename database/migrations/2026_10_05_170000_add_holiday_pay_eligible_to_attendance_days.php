<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_days', function (Blueprint $table) {
            // Only set for unworked regular holidays: null = not applicable.
            $table->boolean('holiday_pay_eligible')->nullable()->after('holiday_type');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_days', fn (Blueprint $table) => $table->dropColumn('holiday_pay_eligible'));
    }
};
