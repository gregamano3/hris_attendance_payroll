<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name')->unique();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('geofence_radius_m')->nullable();
            $table->text('allowed_ip_ranges')->nullable(); // comma separated CIDRs / IPs
            $table->timestamps();
        });

        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
        });

        Schema::table('holidays', function (Blueprint $table) {
            $table->dropUnique(['date']);
            $table->foreignId('branch_id')->nullable()->after('type')->constrained()->cascadeOnDelete(); // null = nationwide
            $table->unique(['date', 'branch_id']);
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->string('branch')->nullable()->after('position');
            $table->string('cost_center')->nullable()->after('branch');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', fn (Blueprint $table) => $table->dropColumn(['branch', 'cost_center']));
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropUnique(['date', 'branch_id']);
            $table->dropConstrainedForeignId('branch_id');
            $table->unique(['date']);
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cost_center_id');
            $table->dropConstrainedForeignId('branch_id');
        });
        Schema::dropIfExists('cost_centers');
        Schema::dropIfExists('branches');
    }
};
