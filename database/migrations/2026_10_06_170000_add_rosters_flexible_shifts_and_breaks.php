<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->boolean('is_flexible')->default(false)->after('grace_minutes');
            $table->time('core_start')->nullable()->after('is_flexible');
            $table->time('core_end')->nullable()->after('core_start');
            $table->unsignedSmallInteger('required_minutes')->nullable()->after('core_end');
        });

        Schema::create('roster_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_rest_day')->default(false);
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
        });

        Schema::table('attendance_days', function (Blueprint $table) {
            $table->unsignedSmallInteger('overbreak_minutes')->default(0)->after('undertime_minutes');
        });

        Schema::table('time_logs', function (Blueprint $table) {
            $table->string('type', 10)->change(); // in | out | break_out | break_in
        });
    }

    public function down(): void
    {
        Schema::table('attendance_days', fn (Blueprint $table) => $table->dropColumn('overbreak_minutes'));
        Schema::dropIfExists('roster_entries');
        Schema::table('shifts', fn (Blueprint $table) => $table->dropColumn(['is_flexible', 'core_start', 'core_end', 'required_minutes']));
    }
};
