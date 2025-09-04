<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::create('study_enrollments', function (Blueprint $table) {
$table->id();
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
$table->foreignId('study_id')->constrained()->cascadeOnDelete();
$table->enum('status', ['invited','screened','enrolled','withdrawn','completed'])->default('invited');
$table->dateTime('enrolled_at')->nullable();
$table->text('withdrawal_reason')->nullable();
$table->timestamps();
$table->index(['user_id','study_id'], 'ix_enr_user_study');
});
}
public function down(): void { Schema::dropIfExists('study_enrollments'); }
};