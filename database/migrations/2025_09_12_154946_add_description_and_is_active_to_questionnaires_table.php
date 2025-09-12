<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('questionnaires', function (Blueprint $table) {
            // Agrega 'description' si no existe
            if (!Schema::hasColumn('questionnaires', 'description')) {
                $table->text('description')->nullable()->after('title');
            }

            // Agrega 'is_active' si no existe
            if (!Schema::hasColumn('questionnaires', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('questionnaires', function (Blueprint $table) {
            if (Schema::hasColumn('questionnaires', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('questionnaires', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
