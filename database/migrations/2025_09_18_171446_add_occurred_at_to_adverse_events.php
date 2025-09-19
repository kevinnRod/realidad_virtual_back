<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('adverse_events', function (Blueprint $table) {
            $table->dateTime('occurred_at')->nullable()->after('session_id');
        });

        // Opcional: backfill con created_at para registros antiguos
        DB::statement('UPDATE adverse_events SET occurred_at = created_at WHERE occurred_at IS NULL');
    }

    public function down(): void
    {
        Schema::table('adverse_events', function (Blueprint $table) {
            $table->dropColumn('occurred_at');
        });
    }
};
