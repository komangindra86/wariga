<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_dewasa', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained('wariga_activities')->cascadeOnDelete();
            $table->date('tanggal');
            $table->unsignedTinyInteger('score_snapshot');
            $table->string('status_snapshot', 50);
            $table->text('notes')->nullable();
            $table->json('result_snapshot');
            $table->string('engine_version', 50);
            $table->string('rule_version', 50);
            $table->timestamps();
            $table->unique(['user_id', 'activity_id', 'tanggal']);
        });

        Schema::create('shared_results', function (Blueprint $table): void {
            $table->id();
            $table->string('public_token', 64)->unique();
            $table->foreignId('activity_id')->constrained('wariga_activities')->cascadeOnDelete();
            $table->date('tanggal');
            $table->json('result_snapshot');
            $table->string('engine_version', 50);
            $table->string('rule_version', 50);
            $table->timestamp('expires_at')->nullable()->index();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_results');
        Schema::dropIfExists('saved_dewasa');
    }
};
