<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::create('questionnaire_responses', function (Blueprint $table) {
$table->id();
$table->foreignId('assignment_id')->constrained('questionnaire_assignments')->cascadeOnDelete();
$table->foreignId('item_id')->constrained('questionnaire_items')->cascadeOnDelete();
$table->decimal('value', 6, 2);
$table->dateTime('answered_at');
$table->timestamps();
$table->unique(['assignment_id','item_id'], 'ux_resp');
});
}
public function down(): void { Schema::dropIfExists('questionnaire_responses'); }
};