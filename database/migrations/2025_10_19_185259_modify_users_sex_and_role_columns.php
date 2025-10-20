<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Cambiar sex de ENUM a VARCHAR
            $table->string('sex', 20)->nullable()->change();
            
            // Cambiar role de ENUM a VARCHAR
            $table->string('role', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Restaurar a ENUM si necesitas revertir
            $table->enum('sex', ['M', 'F', 'O'])->nullable()->change();
            $table->enum('role', ['student', 'admin'])->nullable()->change();
        });
    }
};