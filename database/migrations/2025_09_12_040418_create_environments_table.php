<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('environments', function (Blueprint $table) {
      $table->id();
      $table->string('code')->unique();             // 'forest', 'beach', 'mountain'
      $table->string('name');
      $table->text('description')->nullable();
      $table->string('thumbnail_url')->nullable();  // para cards en el front
      $table->string('asset_bundle')->nullable();   // ruta/clave del paquete 3D
      $table->unsignedSmallInteger('recommended_duration_minutes')->default(10);
      $table->boolean('is_active')->default(true);
      $table->timestamps();
    });
  }
  public function down(): void {
    Schema::dropIfExists('environments');
  }
};