<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::create('devices', function (Blueprint $table) {
$table->id();
$table->string('code',50)->unique();
$table->string('type',50)->nullable();
$table->string('serial',80)->nullable();
$table->string('location',120)->nullable();
$table->timestamps();
});
}
public function down(): void { Schema::dropIfExists('devices'); }
};