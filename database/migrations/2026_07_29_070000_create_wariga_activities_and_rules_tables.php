<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wariga_activities', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->string('slug')->unique();
            $table->string('kategori')->index();
            $table->text('deskripsi');
            $table->text('disclaimer');
            $table->string('icon')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('wariga_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('activity_id')->constrained('wariga_activities')->cascadeOnDelete();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->text('deskripsi');
            $table->enum('rule_type', ['positive', 'negative', 'prohibition', 'information']);
            $table->string('attribute');
            $table->enum('operator', [
                'equals', 'not_equals', 'in', 'not_in', 'greater_than',
                'greater_or_equal', 'less_than', 'less_or_equal', 'between',
                'contains', 'exists', 'not_exists',
            ]);
            $table->json('comparison_value')->nullable();
            $table->integer('score_effect')->default(0);
            $table->enum('severity', ['info', 'low', 'medium', 'high', 'critical']);
            $table->boolean('is_mandatory')->default(false);
            $table->unsignedInteger('priority')->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('source_name')->nullable();
            $table->text('source_reference')->nullable();
            $table->string('source_page')->nullable();
            $table->enum('verification_status', [
                'draft', 'review', 'verified', 'disputed', 'archived',
            ])->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['activity_id', 'verification_status', 'is_active'], 'wariga_rules_public_lookup');
            $table->index(['valid_from', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wariga_rules');
        Schema::dropIfExists('wariga_activities');
    }
};
