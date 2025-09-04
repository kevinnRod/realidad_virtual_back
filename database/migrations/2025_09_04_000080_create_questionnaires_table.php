<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::create('questionnaires', function (Blueprint $table) {
$table->id();
$table->string('code',30); // PSS-10, CSQ-8, VAS, SUDS
$table->string('version',15);
$table->string('title',150);
$table->boolean('is_active')->default(true);
$table->timestamps();
$table->unique(['code','version'],'ux_questionnaire');
});
}
public function down(): void { Schema::dropIfExists('questionnaires'); }
};