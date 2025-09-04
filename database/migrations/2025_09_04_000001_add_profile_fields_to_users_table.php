<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::table('users', function (Blueprint $table) {
$table->string('code', 32)->nullable()->unique()->after('id');
$table->date('birthdate')->nullable()->after('email');
$table->enum('sex', ['M','F','O'])->nullable()->after('birthdate');
$table->enum('role', ['student','admin'])->default('student')->after('sex');
});
}
public function down(): void {
Schema::table('users', function (Blueprint $table) {
$table->dropColumn(['code','birthdate','sex','role']);
});
}
};