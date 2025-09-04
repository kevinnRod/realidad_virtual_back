<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::create('consents', function (Blueprint $table) {
$table->id();
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
$table->string('version',20);
$table->dateTime('accepted_at');
$table->string('signature_path',255)->nullable();
$table->text('notes')->nullable();
$table->timestamps();
$table->index('user_id','ix_consent_user');
});
}
public function down(): void { Schema::dropIfExists('consents'); }
};