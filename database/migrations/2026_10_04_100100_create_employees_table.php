<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no', 30)->unique();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();

            // Personal information
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix', 10)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('civil_status', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('mobile', 30)->nullable();
            $table->text('address')->nullable();

            // Employment
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employment_type', 20);
            $table->string('status', 20)->default('active');
            $table->date('hired_at');
            $table->date('regularized_at')->nullable();
            $table->date('separated_at')->nullable();

            // Compensation (amounts in centavos)
            $table->string('rate_type', 10);
            $table->unsignedBigInteger('basic_rate');

            // Government IDs (digits only)
            $table->string('sss_no', 10)->nullable()->unique();
            $table->string('philhealth_no', 12)->nullable()->unique();
            $table->string('pagibig_no', 12)->nullable()->unique();
            $table->string('tin', 12)->nullable()->unique();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'department_id']);
            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
