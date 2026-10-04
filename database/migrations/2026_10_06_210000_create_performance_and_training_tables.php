<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('due_on');
            $table->json('criteria'); // [{name, weight}]
            $table->string('status', 20)->default('open'); // open | closed
            $table->timestamps();
        });

        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('self_assessment')->nullable();
            $table->json('ratings')->nullable();
            $table->decimal('overall_rating', 3, 2)->nullable();
            $table->text('comments')->nullable();
            $table->string('status', 20)->default('pending'); // pending | completed | acknowledged
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->unique(['review_cycle_id', 'employee_id']);
        });

        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('provider')->nullable();
            $table->date('completed_on');
            $table->decimal('hours', 6, 2)->nullable();
            $table->date('expires_on')->nullable();
            $table->string('certificate_path')->nullable();
            $table->string('certificate_name')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'completed_on']);
            $table->index('expires_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainings');
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('review_cycles');
    }
};
