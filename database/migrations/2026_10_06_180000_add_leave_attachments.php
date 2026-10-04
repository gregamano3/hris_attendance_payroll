<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('reason');
            $table->string('attachment_name')->nullable()->after('attachment_path');
            $table->string('attachment_mime', 100)->nullable()->after('attachment_name');
        });

        Schema::table('leave_types', function (Blueprint $table) {
            // e.g. sick leave longer than 2 days needs a medical certificate
            $table->unsignedSmallInteger('attachment_required_after_days')->nullable()->after('carry_over_cap');
        });
        DB::table('leave_types')->where('code', 'SL')->update(['attachment_required_after_days' => 2]);
        DB::table('leave_types')->whereIn('code', ['ML', 'PL', 'SPL', 'VAWC'])->update(['attachment_required_after_days' => 0]);
    }

    public function down(): void
    {
        Schema::table('leave_types', fn (Blueprint $table) => $table->dropColumn('attachment_required_after_days'));
        Schema::table('leave_requests', fn (Blueprint $table) => $table->dropColumn(['attachment_path', 'attachment_name', 'attachment_mime']));
    }
};
