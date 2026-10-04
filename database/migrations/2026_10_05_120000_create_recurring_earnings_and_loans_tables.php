<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->bigInteger('amount'); // per payroll run, centavos
            $table->string('tax_treatment', 20); // taxable | de_minimis | non_taxable
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'starts_on']);
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('reference_no')->nullable();
            $table->bigInteger('principal');
            $table->bigInteger('amortization'); // per payroll run
            $table->bigInteger('balance');
            $table->date('starts_on');
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });

        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('amount');
            $table->bigInteger('balance_after');
            $table->timestamps();

            $table->unique(['loan_id', 'payroll_run_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('recurring_earnings');
    }
};
