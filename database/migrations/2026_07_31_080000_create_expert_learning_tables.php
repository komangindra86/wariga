<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expert_validation_cases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('activity_id')->constrained('wariga_activities')->cascadeOnDelete();
            $table->date('case_date');
            $table->enum('expected_status', ['recommended', 'consideration', 'avoid']);
            $table->enum('predicted_status', ['recommended', 'consideration', 'avoid']);
            $table->unsignedTinyInteger('predicted_score')->nullable();
            $table->json('matched_facts')->nullable();
            $table->string('source_name')->default('Kalender Bali Digital - referensi eksternal');
            $table->text('source_url')->nullable();
            $table->text('validator_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['activity_id', 'case_date', 'source_name'], 'expert_case_unique');
            $table->index(['activity_id', 'expected_status', 'predicted_status'], 'expert_case_decision_idx');
        });

        Schema::create('expert_learning_proposals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('activity_id')->constrained('wariga_activities')->cascadeOnDelete();
            $table->enum('proposal_type', ['review_positive', 'review_negative', 'review_priority']);
            $table->string('title');
            $table->text('rationale');
            $table->json('evidence');
            $table->decimal('confidence', 5, 2);
            $table->unsignedInteger('sample_size');
            $table->enum('status', ['suggested', 'review', 'approved', 'rejected'])->default('suggested');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->index(['activity_id', 'status'], 'expert_proposal_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_learning_proposals');
        Schema::dropIfExists('expert_validation_cases');
    }
};
