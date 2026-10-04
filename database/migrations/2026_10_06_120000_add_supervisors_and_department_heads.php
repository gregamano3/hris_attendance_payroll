<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('supervisor_id')->nullable()->after('position_id')->constrained('employees')->nullOnDelete();
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('head_employee_id')->nullable()->after('name')->constrained('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departments', fn (Blueprint $table) => $table->dropConstrainedForeignId('head_employee_id'));
        Schema::table('employees', fn (Blueprint $table) => $table->dropConstrainedForeignId('supervisor_id'));
    }
};
