<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedSmallInteger('minutes');
            $table->text('reason');
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_remarks')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'date', 'status']);
        });

        // New overtime permissions for existing installations.
        (new RolesAndPermissionsSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_requests');
    }
};
