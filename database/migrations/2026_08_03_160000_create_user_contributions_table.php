<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_contributions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);
            $table->string('status', 30)->default('pending_review');
            $table->boolean('is_anonymous')->default(false);
            $table->string('contact_email')->nullable();
            $table->string('subject', 180);
            $table->text('message');
            $table->string('activity_name')->nullable();
            $table->date('related_date')->nullable();
            $table->string('region', 120)->nullable();
            $table->string('tradition', 160)->nullable();
            $table->string('source_reference', 500)->nullable();
            $table->timestamp('consented_at');
            $table->timestamps();
            $table->index(['status', 'type', 'created_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('user_contributions'); }
};
