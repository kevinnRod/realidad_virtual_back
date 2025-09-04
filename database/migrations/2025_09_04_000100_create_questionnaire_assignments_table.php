<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::create('questionnaire_assignments', function (Blueprint $table) {
$table->id();
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
$table->foreignId('questionnaire_id')->constrained()->cascadeOnDelete();
$table->foreignId('study_id')->nullable()->constrained()->nullOnDelete();
$table->foreignId('session_id')->nullable()->constrained('vr_sessions')->nullOnDelete();
$table->enum('context', ['baseline','pre','post','followup']);
$table->dateTime('assigned_at');
$table->dateTime('due_at')->nullable();
$table->dateTime('completed_at')->nullable();
$table->timestamps();
$table->index(['user_id','questionnaire_id','context'],'ix_qas_user');
});
}
public function down(): void { Schema::dropIfExists('questionnaire_assignments'); }
};