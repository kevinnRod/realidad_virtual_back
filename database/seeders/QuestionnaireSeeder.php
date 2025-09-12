<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Questionnaire;
use App\Models\QuestionnaireItem;

class QuestionnaireSeeder extends Seeder
{
    public function run(): void
    {
        // ====== PSS-10 ======
        $pss = Questionnaire::updateOrCreate(
            ['code' => 'pss10', 'version' => '1'],
            ['title' => 'PSS-10 Estrés Percibido', 'is_active' => true]
        );

        QuestionnaireItem::where('questionnaire_id', $pss->id)->delete();

        $pssScaleMin = 0;  // Nunca
        $pssScaleMax = 4;  // Muy a menudo
        $pssItems = [
            [1,'En el último mes, ¿con qué frecuencia se ha sentido molesto(a) por algo que ocurrió inesperadamente?', false],
            [2,'En el último mes, ¿con qué frecuencia se sintió incapaz de controlar las cosas importantes en su vida?', false],
            [3,'En el último mes, ¿con qué frecuencia se sintió nervioso(a) o estresado(a)?', false],
            [4,'En el último mes, ¿con qué frecuencia se sintió confiado(a) en su capacidad para manejar sus problemas personales?', true],
            [5,'En el último mes, ¿con qué frecuencia sintió que las cosas iban bien para usted?', true],
            [6,'En el último mes, ¿con qué frecuencia sintió que no podía afrontar todas las cosas que tenía que hacer?', false],
            [7,'En el último mes, ¿con qué frecuencia pudo controlar las dificultades de su vida?', true],
            [8,'En el último mes, ¿con qué frecuencia sintió que tenía todo bajo control?', true],
            [9,'En el último mes, ¿con qué frecuencia sintió que estaba enfadado(a) porque las cosas que le sucedían estaban fuera de su control?', false],
            [10,'En el último mes, ¿con qué frecuencia sintió que las dificultades se acumulaban tanto que no podía superarlas?', false],
        ];

        foreach ($pssItems as [$ord,$text,$rev]) {
            QuestionnaireItem::create([
                'questionnaire_id' => $pss->id,
                'code'             => "pss10_q{$ord}",
                'text'             => $text,
                'sort_order'       => $ord,          // ✅ tu columna real
                'scale_min'        => $pssScaleMin,  // ✅ tu columna real
                'scale_max'        => $pssScaleMax,  // ✅ tu columna real
                'reverse_scored'   => $rev,
            ]);
        }

        // ====== Satisfacción ======
        $sat = Questionnaire::updateOrCreate(
            ['code' => 'satisf', 'version' => '1'],
            ['title' => 'Satisfacción de Sesión VR', 'is_active' => true]
        );

        QuestionnaireItem::where('questionnaire_id', $sat->id)->delete();

        $satScaleMin = 1; // Muy en desacuerdo
        $satScaleMax = 5; // Muy de acuerdo
        $satItems = [
            [1,'La sesión fue cómoda y fácil de seguir.', false],
            [2,'El entorno virtual me ayudó a relajarme.', false],
            [3,'Me sentí inmerso(a) durante la sesión.', false],
            [4,'El dispositivo fue cómodo de usar.', false],
            [5,'En general estoy satisfecho(a) con esta sesión.', false],
        ];

        foreach ($satItems as [$ord,$text,$rev]) {
            QuestionnaireItem::create([
                'questionnaire_id' => $sat->id,
                'code'             => "satisf_q{$ord}",
                'text'             => $text,
                'sort_order'       => $ord,          // ✅
                'scale_min'        => $satScaleMin,  // ✅
                'scale_max'        => $satScaleMax,  // ✅
                'reverse_scored'   => $rev,
            ]);
        }
    }
}
