<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('studies', function (Blueprint $table) {
            // Asegúrate de que la tabla environments ya exista antes de esta migración
            $table->foreignId('default_environment_id')
                  ->nullable()
                  ->constrained('environments')
                  ->nullOnDelete()
                  ->after('name'); // opcional: posición de la columna
        });
    }

    public function down(): void
    {
        Schema::table('studies', function (Blueprint $table) {
            $table->dropForeign(['default_environment_id']);
            $table->dropColumn('default_environment_id');
        });
    }
};
