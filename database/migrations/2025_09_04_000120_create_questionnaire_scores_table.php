<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::create('questionnaire_scores', function (Blueprint $table) {
$table->id();
$table->foreignId('assignment_id')->constrained('questionnaire_assignments')->cascadeOnDelete();
$table->decimal('score_total', 8, 2);
$table->json('score_json')->nullable();
$table->timestamps();
$table->unique('assignment_id','ux_qscore');
});
}
public function down(): void { Schema::dropIfExists('questionnaire_scores'); }
};