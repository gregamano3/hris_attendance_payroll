<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compensation_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('effective_from');
            $table->string('rate_type', 10);
            $table->unsignedBigInteger('basic_rate');
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'effective_from']);
        });

        // Starting point for existing employees: their current rate since hire.
        DB::table('employees')->orderBy('id')->each(function (object $employee) {
            DB::table('compensation_changes')->insert([
                'employee_id' => $employee->id,
                'effective_from' => $employee->hired_at,
                'rate_type' => $employee->rate_type,
                'basic_rate' => $employee->basic_rate,
                'reason' => 'Initial rate',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compensation_changes');
    }
};
