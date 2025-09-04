<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::create('questionnaire_items', function (Blueprint $table) {
$table->id();
$table->foreignId('questionnaire_id')->constrained()->cascadeOnDelete();
$table->string('code',20);
$table->text('text');
$table->integer('sort_order');
$table->integer('scale_min');
$table->integer('scale_max');
$table->boolean('reverse_scored')->default(false);
$table->timestamps();
$table->unique(['questionnaire_id','code'],'ux_qitem');
});
}
public function down(): void { Schema::dropIfExists('questionnaire_items'); }
};