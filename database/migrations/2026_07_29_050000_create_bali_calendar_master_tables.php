<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = [
        'master_eka_wara',
        'master_dwi_wara',
        'master_tri_wara',
        'master_catur_wara',
        'master_panca_wara',
        'master_sad_wara',
        'master_sapta_wara',
        'master_asta_wara',
        'master_sanga_wara',
        'master_dasa_wara',
        'master_wuku',
        'master_sasih',
        'master_ingkel',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->string('kode')->unique();
                $table->string('nama');
                $table->string('nama_indonesia')->nullable();
                $table->string('nama_alternatif')->nullable();
                $table->unsignedSmallInteger('urutan');
                $table->unsignedSmallInteger('urip')->nullable();
                $table->text('deskripsi')->nullable();
                $table->json('metadata')->nullable();
                $table->enum('verification_status', [
                    'draft',
                    'review',
                    'verified',
                    'disputed',
                    'archived',
                ])->default('draft')->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();

                $table->index('urutan');
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            Schema::dropIfExists($tableName);
        }
    }
};
