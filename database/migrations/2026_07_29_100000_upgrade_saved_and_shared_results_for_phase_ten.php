<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $upgradingLegacyShare = Schema::hasTable('shared_dewasa') && ! Schema::hasTable('shared_results');

        if (Schema::hasColumn('saved_dewasa', 'date')) {
            Schema::table('saved_dewasa', fn (Blueprint $table) => $table->renameColumn('date', 'tanggal'));
        }

        Schema::table('saved_dewasa', function (Blueprint $table): void {
            if (! Schema::hasColumn('saved_dewasa', 'score_snapshot')) {
                $table->unsignedTinyInteger('score_snapshot')->default(0)->after('tanggal');
            }
            if (! Schema::hasColumn('saved_dewasa', 'status_snapshot')) {
                $table->string('status_snapshot', 50)->default('consideration')->after('score_snapshot');
            }
            if (! Schema::hasColumn('saved_dewasa', 'engine_version')) {
                $table->string('engine_version', 50)->default('0.2.0')->after('result_snapshot');
            }
            if (! Schema::hasColumn('saved_dewasa', 'rule_version')) {
                $table->string('rule_version', 50)->default('1.0.0')->after('engine_version');
            }
        });

        if ($upgradingLegacyShare) {
            Schema::table('shared_dewasa', function (Blueprint $table): void {
                $table->dropForeign(['user_id']);
            });
            Schema::rename('shared_dewasa', 'shared_results');
        }

        Schema::table('shared_results', function (Blueprint $table): void {
            if (Schema::hasColumn('shared_results', 'token')) {
                $table->renameColumn('token', 'public_token');
            }
            if (Schema::hasColumn('shared_results', 'user_id')) {
                $table->renameColumn('user_id', 'created_by');
            }
            if (! Schema::hasColumn('shared_results', 'activity_id')) {
                $table->foreignId('activity_id')->nullable()->after('public_token')
                    ->constrained('wariga_activities')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('shared_results', 'tanggal')) {
                $table->date('tanggal')->nullable()->after('activity_id');
            }
            if (! Schema::hasColumn('shared_results', 'engine_version')) {
                $table->string('engine_version', 50)->default('0.2.0')->after('result_snapshot');
            }
            if (! Schema::hasColumn('shared_results', 'rule_version')) {
                $table->string('rule_version', 50)->default('1.0.0')->after('engine_version');
            }
            if (! Schema::hasColumn('shared_results', 'view_count')) {
                $table->unsignedBigInteger('view_count')->default(0)->after('expires_at');
            }
        });

        if ($upgradingLegacyShare) {
            Schema::table('shared_results', function (Blueprint $table): void {
                $table->unsignedBigInteger('created_by')->nullable()->change();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if ($upgradingLegacyShare) {
            DB::table('shared_results')
                ->join('saved_dewasa', 'shared_results.saved_dewasa_id', '=', 'saved_dewasa.id')
                ->update([
                    'shared_results.activity_id' => DB::raw('saved_dewasa.activity_id'),
                    'shared_results.tanggal' => DB::raw('saved_dewasa.tanggal'),
                    'shared_results.engine_version' => DB::raw('saved_dewasa.engine_version'),
                    'shared_results.rule_version' => DB::raw('saved_dewasa.rule_version'),
                ]);
        }

        if (Schema::hasColumn('shared_results', 'saved_dewasa_id')) {
            $foreignKeys = DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'shared_results'
                 AND COLUMN_NAME = 'saved_dewasa_id' AND REFERENCED_TABLE_NAME IS NOT NULL"
            );
            foreach ($foreignKeys as $foreignKey) {
                DB::statement(sprintf(
                    'ALTER TABLE `shared_results` DROP FOREIGN KEY `%s`',
                    str_replace('`', '``', $foreignKey->CONSTRAINT_NAME),
                ));
            }
            Schema::table('shared_results', fn (Blueprint $table) => $table->dropColumn('saved_dewasa_id'));
        }

        Schema::table('shared_results', function (Blueprint $table): void {
            if (Schema::hasColumn('shared_results', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }

    public function down(): void
    {
        // Snapshot publik tidak dikembalikan ke struktur lama agar data tidak hilang.
    }
};
