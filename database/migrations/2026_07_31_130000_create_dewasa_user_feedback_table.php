<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dewasa_user_feedback', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained('wariga_activities')->cascadeOnDelete();
            $table->date('recommendation_date');
            $table->enum('rating', ['helpful', 'not_helpful', 'unsure']);
            $table->date('selected_date')->nullable();
            $table->text('reason')->nullable();
            $table->string('region', 120)->nullable();
            $table->string('tradition', 160)->nullable();
            $table->boolean('consulted_expert')->default(false);
            $table->string('expert_role', 120)->nullable();
            $table->enum('predicted_status', ['recommended', 'consideration', 'avoid']);
            $table->unsignedTinyInteger('predicted_score');
            $table->boolean('learning_consent')->default(false);
            $table->unsignedSmallInteger('submission_count')->default(1);
            $table->boolean('is_suspicious')->default(false);
            $table->timestamp('consented_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['user_id', 'activity_id', 'recommendation_date'],
                'user_feedback_once_per_result',
            );
            $table->index(
                ['activity_id', 'learning_consent', 'is_suspicious'],
                'user_feedback_learning_pool',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dewasa_user_feedback');
    }
};
