<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('break_minutes')->default(60);
            $table->unsignedSmallInteger('grace_minutes')->default(0);
            $table->json('work_days'); // ISO-8601 weekdays, 1 = Monday ... 7 = Sunday
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('employee_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained()->restrictOnDelete();
            $table->date('effective_from');
            $table->timestamps();

            $table->unique(['employee_id', 'effective_from']);
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('name');
            $table->string('type', 30);
            $table->timestamps();
        });

        Schema::create('time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->timestamp('logged_at');
            $table->string('type', 3); // in | out
            $table->string('source', 10); // web | manual | import
            $table->string('remarks')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'logged_at']);
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->boolean('is_paid')->default(true);
            $table->unsignedSmallInteger('days_per_year')->default(0); // 0 = no yearly cap
            $table->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days', 5, 1);
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_remarks')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status', 'start_date']);
        });

        Schema::create('attendance_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20);
            $table->timestamp('time_in')->nullable();
            $table->timestamp('time_out')->nullable();
            $table->unsignedSmallInteger('worked_minutes')->default(0);
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->unsignedSmallInteger('undertime_minutes')->default(0);
            $table->unsignedSmallInteger('overtime_minutes')->default(0);
            $table->unsignedSmallInteger('night_diff_minutes')->default(0);
            $table->boolean('is_rest_day')->default(false);
            $table->string('holiday_type', 30)->nullable();
            $table->foreignId('leave_request_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_days');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('time_logs');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('employee_shifts');
        Schema::dropIfExists('shifts');
    }
};
