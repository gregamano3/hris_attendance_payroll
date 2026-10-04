<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Effective-dated parameters of the SSS, PhilHealth and Pag-IBIG schemes.
        Schema::create('statutory_rates', function (Blueprint $table) {
            $table->id();
            $table->string('scheme', 20);
            $table->date('effective_from');
            $table->json('parameters');
            $table->timestamps();

            $table->unique(['scheme', 'effective_from']);
        });

        // BIR withholding tax table (amounts in centavos).
        Schema::create('tax_brackets', function (Blueprint $table) {
            $table->id();
            $table->date('effective_from');
            $table->string('frequency', 20);
            $table->unsignedBigInteger('lower_bound');
            $table->unsignedBigInteger('upper_bound')->nullable();
            $table->unsignedBigInteger('base_tax');
            $table->decimal('rate', 5, 4);
            $table->timestamps();

            $table->index(['frequency', 'effective_from']);
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('pay_date');
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('employee_count')->default(0);
            $table->bigInteger('total_gross')->default(0);
            $table->bigInteger('total_deductions')->default(0);
            $table->bigInteger('total_net')->default(0);
            $table->bigInteger('total_employer')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('computed_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique(['period_start', 'period_end']);
        });

        Schema::create('payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20); // earning | deduction
            $table->string('label');
            $table->bigInteger('amount');
            $table->boolean('taxable')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();

            // Snapshot of the employee at computation time.
            $table->string('employee_no', 30);
            $table->string('employee_name');
            $table->string('department')->nullable();
            $table->string('position')->nullable();
            $table->string('rate_type', 10);
            $table->bigInteger('basic_rate');
            $table->bigInteger('daily_rate');
            $table->bigInteger('hourly_rate');

            $table->bigInteger('gross_pay');
            $table->bigInteger('taxable_income');
            $table->bigInteger('total_deductions');
            $table->bigInteger('net_pay');
            $table->bigInteger('employer_contributions');
            $table->json('attendance');
            $table->json('warnings')->nullable();
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id']);
        });

        Schema::create('payslip_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20); // earning | deduction | employer
            $table->string('code', 30);
            $table->string('label');
            $table->decimal('quantity', 10, 2)->nullable();
            $table->string('unit', 10)->nullable();
            $table->bigInteger('amount');
            $table->boolean('taxable')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_lines');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_adjustments');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('tax_brackets');
        Schema::dropIfExists('statutory_rates');
    }
};
