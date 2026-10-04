<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->timestamps();
        });

        Schema::table('time_logs', function (Blueprint $table) {
            // Privacy: only the distance from the branch is kept, never coordinates.
            $table->unsignedInteger('distance_m')->nullable()->after('ip_address');
            $table->foreignId('attendance_device_id')->nullable()->after('distance_m')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('time_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('attendance_device_id');
            $table->dropColumn('distance_m');
        });
        Schema::dropIfExists('attendance_devices');
    }
};
