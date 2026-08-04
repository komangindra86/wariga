<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kalender_bali_validation_months', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('activity_id')->constrained('wariga_activities')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->json('recommended_dates');
            $table->text('source_url');
            $table->timestamp('validated_at');
            $table->timestamps();
            $table->unique(['activity_id', 'year', 'month'], 'kb_validation_month_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kalender_bali_validation_months');
    }
};
