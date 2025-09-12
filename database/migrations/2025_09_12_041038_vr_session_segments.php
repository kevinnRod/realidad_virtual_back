<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
    Schema::create('vr_session_segments', function (Blueprint $table) {
      $table->id();

      $table->foreignId('vr_session_id')
            ->constrained('vr_sessions')->cascadeOnDelete();

      $table->foreignId('environment_id')
            ->constrained('environments')->cascadeOnDelete();

      $table->unsignedSmallInteger('sort_order');            // 1..N
      $table->unsignedSmallInteger('duration_minutes')->default(5);

      // Opcionales por si quieres registrar ejecución real:
      $table->dateTime('started_at')->nullable();
      $table->dateTime('ended_at')->nullable();

      // Opcional: transición visual entre ambientes (fade, crossfade…)
      $table->string('transition', 30)->nullable();

      $table->timestamps();

      $table->unique(['vr_session_id','sort_order'], 'ux_session_segment_order');
      $table->index(['vr_session_id']);
      $table->index(['environment_id']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('vr_session_segments');
  }
};
