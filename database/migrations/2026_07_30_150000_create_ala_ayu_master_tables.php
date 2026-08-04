<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ala_ayu_masters', function (Blueprint $table): void {
            $table->id();
            $table->string('nama')->unique();
            $table->text('deskripsi');
            $table->longText('komposisi_teks');
            $table->json('komposisi')->nullable();
            $table->string('sumber_nama');
            $table->text('sumber_url');
            $table->timestamp('diimpor_pada');
            $table->timestamps();
        });

        Schema::create('ala_ayu_activity_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('activity_id')->constrained('wariga_activities')->cascadeOnDelete();
            $table->foreignId('ala_ayu_master_id')->constrained('ala_ayu_masters')->cascadeOnDelete();
            $table->enum('effect', ['positive', 'negative']);
            $table->unsignedTinyInteger('alahing_priority')->nullable();
            $table->text('source_url');
            $table->timestamps();

            $table->unique(
                ['activity_id', 'ala_ayu_master_id', 'effect'],
                'ala_ayu_activity_rule_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ala_ayu_activity_rules');
        Schema::dropIfExists('ala_ayu_masters');
    }
};
