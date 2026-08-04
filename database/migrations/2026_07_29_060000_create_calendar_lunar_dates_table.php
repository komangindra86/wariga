<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_lunar_dates', function (Blueprint $table): void {
            $table->id();
            $table->date('tanggal_masehi')->unique();
            $table->foreignId('sasih_id')->constrained('master_sasih')->restrictOnDelete();
            $table->enum('fase', ['penanggal', 'pangelong']);
            $table->unsignedTinyInteger('nomor_hari');
            $table->boolean('is_purnama')->default(false);
            $table->boolean('is_tilem')->default(false);
            $table->boolean('is_nampih_sasih')->default(false);
            $table->string('sumber');
            $table->text('referensi');
            $table->text('catatan')->nullable();
            $table->enum('verification_status', [
                'draft', 'review', 'verified', 'disputed', 'archived',
            ])->default('draft')->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['tanggal_masehi', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_lunar_dates');
    }
};
