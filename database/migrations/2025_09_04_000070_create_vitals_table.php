<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::create('vitals', function (Blueprint $table) {
$table->id();
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
$table->foreignId('session_id')->nullable()->constrained('vr_sessions')->cascadeOnDelete();
$table->dateTime('measured_at');
$table->enum('phase', ['baseline','pre','post','followup']);
$table->enum('posture', ['seated','standing'])->default('seated');
$table->smallInteger('bp_sys')->nullable();
$table->smallInteger('bp_dia')->nullable();
$table->smallInteger('pulse')->nullable();
$table->string('device_label',80)->nullable();
$table->timestamps();
$table->index(['user_id','measured_at'],'ix_vitals_user');
$table->index(['session_id','phase'],'ix_vitals_session');
});
}
public function down(): void { Schema::dropIfExists('vitals'); }
};