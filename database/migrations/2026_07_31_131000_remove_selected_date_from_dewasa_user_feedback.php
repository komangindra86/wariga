<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dewasa_user_feedback', function (Blueprint $table): void {
            $table->dropColumn('selected_date');
        });
    }

    public function down(): void
    {
        Schema::table('dewasa_user_feedback', function (Blueprint $table): void {
            $table->date('selected_date')->nullable()->after('rating');
        });
    }
};
