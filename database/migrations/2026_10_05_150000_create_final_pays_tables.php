<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->boolean('is_convertible')->default(false)->after('is_paid');
        });
        DB::table('leave_types')->whereIn('code', ['VL', 'SIL'])->update(['is_convertible' => true]);

        Schema::create('final_pays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained()->restrictOnDelete();
            $table->date('separation_date');
            $table->string('status', 20)->default('draft');
            $table->bigInteger('total_earnings')->default(0);
            $table->bigInteger('total_deductions')->default(0);
            $table->bigInteger('net_pay')->default(0);
            $table->json('lines')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
        });

        Schema::create('final_pay_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('final_pay_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('label');
            $table->bigInteger('amount');
            $table->boolean('taxable')->default(true);
            $table->timestamps();
        });

        Schema::table('loan_payments', function (Blueprint $table) {
            $table->foreignId('payroll_run_id')->nullable()->change();
            $table->foreignId('final_pay_id')->nullable()->after('payroll_run_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('loan_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('final_pay_id');
        });
        Schema::dropIfExists('final_pay_adjustments');
        Schema::dropIfExists('final_pays');
        Schema::table('leave_types', fn (Blueprint $table) => $table->dropColumn('is_convertible'));
    }
};
