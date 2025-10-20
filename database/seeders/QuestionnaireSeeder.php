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

        $pssScaleMin = 0; // Nunca
        $pssScaleMax = 4; // Muy a menudo

        $pssItems = [
            [1, '¿Con qué frecuencia se ha sentido molesto(a) por algo que ocurrió inesperadamente?', false],
            [2, '¿Con qué frecuencia se sintió incapaz de controlar las cosas importantes en su vida?', false],
            [3, '¿Con qué frecuencia se sintió nervioso(a) o estresado(a)?', false],
            [4, '¿Con qué frecuencia se sintió confiado(a) en su capacidad para manejar sus problemas personales?', true],
            [5, '¿Con qué frecuencia sintió que las cosas iban bien para usted?', true],
            [6, '¿Con qué frecuencia sintió que no podía afrontar todas las cosas que tenía que hacer?', false],
            [7, '¿Con qué frecuencia pudo controlar las dificultades de su vida?', true],
            [8, '¿Con qué frecuencia sintió que tenía todo bajo control?', true],
            [9, '¿Con qué frecuencia sintió que estaba enfadado(a) porque las cosas que le sucedían estaban fuera de su control?', false],
            [10,'¿Con qué frecuencia sintió que las dificultades se acumulaban tanto que no podía superarlas?', false],
        ];

        foreach ($pssItems as [$ord, $text, $rev]) {
            QuestionnaireItem::create([
                'questionnaire_id' => $pss->id,
                'code'             => "pss10_q{$ord}",
                'text'             => $text,
                'sort_order'       => $ord,
                'scale_min'        => $pssScaleMin,
                'scale_max'        => $pssScaleMax,
                'reverse_scored'   => $rev,
            ]);
        }

        // ====== Encuesta de Satisfacción (VR) ======
        $sat = Questionnaire::updateOrCreate(
            ['code' => 'satisf', 'version' => '1'],
            ['title' => 'Satisfacción de Sesión VR', 'is_active' => true]
        );

        QuestionnaireItem::where('questionnaire_id', $sat->id)->delete();

        $satScaleMin = 1; // Muy en desacuerdo
        $satScaleMax = 5; // Muy de acuerdo

        $satItems = [
            [1, 'Me sentí cómodo/a utilizando la realidad virtual', false],
            [2, 'La intervención con RV fue fácil de seguir y entender', false],
            [3, 'La duración y frecuencia de las sesiones fueron adecuadas', false],
            [4, 'Considero que la realidad virtual me ayudó a reducir el estrés académico', false],
            [5, 'Siento que mi bienestar emocional mejoró tras la intervención con RV', false],
            [6, 'La experiencia con RV fue agradable y estimulante', false],
            [7, 'Me gustaría que se repita este tipo de intervención en el futuro', false],
            [8, 'Estoy satisfecho/a con la experiencia general de la intervención con RV', false],
        ];

        foreach ($satItems as [$ord, $text, $rev]) {
            QuestionnaireItem::create([
                'questionnaire_id' => $sat->id,
                'code'             => "satisf_q{$ord}",
                'text'             => $text,
                'sort_order'       => $ord,
                'scale_min'        => $satScaleMin,
                'scale_max'        => $satScaleMax,
                'reverse_scored'   => $rev,
            ]);
        }

        // ====== Encuesta de Satisfacción (Video Naturalista) ======
        $video = Questionnaire::updateOrCreate(
            ['code' => 'satisf_video', 'version' => '1'],
            ['title' => 'Satisfacción tras video naturalista', 'is_active' => true]
        );

        QuestionnaireItem::where('questionnaire_id', $video->id)->delete();

        $videoItems = [
            [1, 'Me sentí cómodo/a viendo el video naturalista', false],
            [2, 'El contenido del video fue fácil de seguir y entender', false],
            [3, 'La duración del video fue adecuada', false],
            [4, 'Considero que el video me ayudó a reducir el estrés académico', false],
            [5, 'Siento que mi bienestar emocional mejoró tras ver el video', false],
            [6, 'La experiencia de ver el video fue agradable y estimulante', false],
            [7, 'Me gustaría ver más contenido como este en el futuro', false],
            [8, 'Estoy satisfecho/a con la experiencia general de ver el video', false],
        ];

        foreach ($videoItems as [$ord, $text, $rev]) {
            QuestionnaireItem::create([
                'questionnaire_id' => $video->id,
                'code'             => "satisf_video_q{$ord}",
                'text'             => $text,
                'sort_order'       => $ord,
                'scale_min'        => $satScaleMin, // misma escala que la VR
                'scale_max'        => $satScaleMax,
                'reverse_scored'   => $rev,
            ]);
        }
    }
}
