<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::create('eligibility_screenings', function (Blueprint $table) {
$table->id();
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
$table->dateTime('screened_at');
$table->boolean('hypertension_dx')->default(false);
$table->smallInteger('bp_sys_rest')->nullable();
$table->smallInteger('bp_dia_rest')->nullable();
$table->boolean('antihypertensive_change_4w')->default(false);
$table->boolean('cardiovascular_disease')->default(false);
$table->boolean('epilepsy_photosensitive')->default(false);
$table->boolean('vestibular_disorder')->default(false);
$table->boolean('psychiatric_unstable')->default(false);
$table->boolean('psych_rx_change_4w')->default(false);
$table->boolean('pregnancy')->default(false);
$table->boolean('vr_intolerance')->default(false);
$table->boolean('caffeine_2h')->default(false);
$table->boolean('tobacco_2h')->default(false);
$table->boolean('alcohol_2h')->default(false);
$table->boolean('eligible');
$table->text('notes')->nullable();
$table->timestamps();
$table->index('user_id','ix_screen_user');
});
}
public function down(): void { Schema::dropIfExists('eligibility_screenings'); }
};