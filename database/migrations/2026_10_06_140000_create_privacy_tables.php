<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_notices', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20)->unique();
            $table->string('title');
            $table->text('body');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('privacy_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('privacy_notice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('acknowledged_at');

            $table->unique(['privacy_notice_id', 'user_id']);
        });

        Schema::create('data_subject_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);    // access | correction | erasure | objection
            $table->string('status', 20)->default('open'); // open | completed | rejected
            $table->text('details')->nullable();
            $table->text('response')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->timestamp('anonymized_at')->nullable()->after('separated_at');
        });
    }

    public function down(): void
    {
        Schema::table('employees', fn (Blueprint $table) => $table->dropColumn('anonymized_at'));
        Schema::dropIfExists('data_subject_requests');
        Schema::dropIfExists('privacy_acknowledgements');
        Schema::dropIfExists('privacy_notices');
    }
};
