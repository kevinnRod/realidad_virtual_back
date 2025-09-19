<?php
// php artisan make:migration alter_type_in_adverse_events_to_string --table=adverse_events
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('adverse_events', function (Blueprint $table) {
            $table->string('type', 120)->change(); // requiere doctrine/dbal si cambias tipo
        });
    }
    public function down(): void
    {
        Schema::table('adverse_events', function (Blueprint $table) {
            // vuelve a su tipo anterior si lo necesitas (enum/int, etc.)
        });
    }
};
