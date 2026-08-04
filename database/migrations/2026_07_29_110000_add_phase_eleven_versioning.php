<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->renameColumn('auditable_type', 'entity_type');
            $table->renameColumn('auditable_id', 'entity_id');
        });

        Schema::table('wariga_rules', function (Blueprint $table): void {
            $table->string('rule_version', 50)->default('1.0.0')->after('verification_status')->index();
        });

        Schema::table('saved_dewasa', function (Blueprint $table): void {
            $table->string('dataset_version', 50)->default('1.0.0')->after('rule_version');
        });

        Schema::table('shared_results', function (Blueprint $table): void {
            $table->string('dataset_version', 50)->default('1.0.0')->after('rule_version');
        });
    }

    public function down(): void
    {
        Schema::table('shared_results', fn (Blueprint $table) => $table->dropColumn('dataset_version'));
        Schema::table('saved_dewasa', fn (Blueprint $table) => $table->dropColumn('dataset_version'));

        Schema::table('wariga_rules', function (Blueprint $table): void {
            $table->dropColumn('rule_version');
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->renameColumn('entity_type', 'auditable_type');
            $table->renameColumn('entity_id', 'auditable_id');
        });
    }
};
