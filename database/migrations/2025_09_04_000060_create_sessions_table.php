<?php

// database/migrations/xxxx_xx_xx_create_vr_sessions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('vr_sessions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->foreignId('study_id')->constrained()->cascadeOnDelete();
      $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();

      $table->unsignedSmallInteger('session_no');
      $table->dateTime('scheduled_at')->nullable();
      $table->dateTime('started_at')->nullable();
      $table->dateTime('ended_at')->nullable();

      // total opcional (puedes calcularlo como suma de segmentos en app)
      $table->unsignedSmallInteger('total_duration_minutes')->nullable();

      $table->string('vr_app_version',40)->nullable();
      $table->text('notes')->nullable();
      $table->timestamps();

      $table->unique(['user_id','study_id','session_no'], 'ux_user_study_sessno');
      $table->index('user_id','ix_vr_sess_user');
      $table->index('study_id','ix_vr_sess_study');
      $table->index('scheduled_at','ix_vr_sess_scheduled');
    });
  }

  public function down(): void {
    Schema::dropIfExists('vr_sessions');
  }
};

