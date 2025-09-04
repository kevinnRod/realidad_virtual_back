<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::create('studies', function (Blueprint $table) {
$table->id();
$table->string('name',150);
$table->text('description')->nullable();
$table->date('start_date')->nullable();
$table->date('end_date')->nullable();
$table->enum('status', ['draft','active','closed'])->default('draft');
$table->timestamps();
});
}
public function down(): void { Schema::dropIfExists('studies'); }
};