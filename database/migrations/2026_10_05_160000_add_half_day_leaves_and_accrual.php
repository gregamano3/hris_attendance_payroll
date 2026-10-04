<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('day_part', 4)->default('full')->after('end_date'); // full | am | pm
        });

        Schema::table('attendance_days', function (Blueprint $table) {
            $table->decimal('leave_fraction', 2, 1)->default(0)->after('leave_request_id');
        });

        Schema::table('leave_types', function (Blueprint $table) {
            $table->decimal('accrual_per_month', 4, 2)->default(0)->after('days_per_year');
            $table->unsignedSmallInteger('carry_over_cap')->default(0)->after('accrual_per_month');
        });
        DB::table('leave_types')->whereIn('code', ['VL', 'SL'])->update(['accrual_per_month' => 1.25]);
        DB::table('leave_types')->where('code', 'VL')->update(['carry_over_cap' => 5]);

        Schema::create('leave_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('earned', 5, 2)->default(0);
            $table->decimal('carried_over', 5, 2)->default(0);
            $table->unsignedTinyInteger('accrued_through_month')->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_credits');
        Schema::table('leave_types', fn (Blueprint $table) => $table->dropColumn(['accrual_per_month', 'carry_over_cap']));
        Schema::table('attendance_days', fn (Blueprint $table) => $table->dropColumn('leave_fraction'));
        Schema::table('leave_requests', fn (Blueprint $table) => $table->dropColumn('day_part'));
    }
};
