<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::create('adverse_events', function (Blueprint $table) {
$table->id();
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
$table->foreignId('session_id')->constrained('vr_sessions')->cascadeOnDelete();
$table->enum('type', ['nausea','dizziness','headache','other']);
$table->tinyInteger('severity'); // 1..5
$table->boolean('related_to_vr')->default(true);
$table->string('action_taken',200)->nullable();
$table->dateTime('resolved_at')->nullable();
$table->text('notes')->nullable();
$table->timestamps();
$table->index('session_id','ix_ae_session');
});
}
public function down(): void { Schema::dropIfExists('adverse_events'); }
};