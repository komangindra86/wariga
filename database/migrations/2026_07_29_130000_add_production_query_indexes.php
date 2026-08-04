<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saved_dewasa', function (Blueprint $table): void {
            $table->index(['user_id', 'created_at'], 'saved_dewasa_user_created_index');
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->index('created_at', 'audit_logs_created_at_index');
        });

        Schema::table('wariga_rules', function (Blueprint $table): void {
            $table->index(
                ['activity_id', 'verification_status', 'is_active', 'valid_from', 'valid_until'],
                'wariga_rules_effective_lookup',
            );
        });
    }

    public function down(): void
    {
        Schema::table('saved_dewasa', fn (Blueprint $table) => $table->dropIndex('saved_dewasa_user_created_index'));
        Schema::table('audit_logs', fn (Blueprint $table) => $table->dropIndex('audit_logs_created_at_index'));
        Schema::table('wariga_rules', fn (Blueprint $table) => $table->dropIndex('wariga_rules_effective_lookup'));
    }
};
