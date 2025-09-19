<?php
namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;


class QuestionnairesSeeder extends Seeder
{
public function run(): void
{
// PSS-10 (versión estándar)
$pssId = DB::table('questionnaires')->updateOrInsert(
['code' => 'PSS-10', 'version' => 'v1'],
['title' => 'Perceived Stress Scale 10', 'is_active' => true, 'updated_at' => now(), 'created_at' => now()]
);
$pss = DB::table('questionnaires')->where(['code' => 'PSS-10','version' => 'v1'])->first();
if ($pss) {
$items = [
['Q1','En el último mes, ¿con qué frecuencia se ha sentido molesto por algo que pasó inesperadamente?'],
['Q2','En el último mes, ¿con qué frecuencia se sintió incapaz de controlar las cosas importantes en su vida?'],
['Q3','En el último mes, ¿con qué frecuencia se sintió nervioso o estresado?'],
['Q4','En el último mes, ¿con qué frecuencia se sintió confiado sobre su habilidad para manejar sus problemas personales?'],
['Q5','En el último mes, ¿con qué frecuencia sintió que las cosas le salían bien?'],
['Q6','En el último mes, ¿con qué frecuencia sintió que no podía afrontar todas las cosas que tenía que hacer?'],
['Q7','En el último mes, ¿con qué frecuencia pudo controlar las irritaciones en su vida?'],
['Q8','En el último mes, ¿con qué frecuencia sintió que estaba al tanto de todo?'],
['Q9','En el último mes, ¿con qué frecuencia se enojó porque ocurrieron cosas que estaban fuera de su control?'],
['Q10','En el último mes, ¿con qué frecuencia sintió que las dificultades se acumulaban tanto que no podía superarlas?'],
];
$reverse = ['Q4','Q5','Q7','Q8'];
foreach ($items as $i => [$code,$text]) {
DB::table('questionnaire_items')->updateOrInsert([
'questionnaire_id' => $pss->id,
'code' => $code,
], [
'text' => $text,
'sort_order' => $i+1,
'scale_min' => 0,
'scale_max' => 4,
'reverse_scored' => in_array($code, $reverse),
'updated_at' => now(),
'created_at' => now(),
]);
}
}


// CSQ-8 (Satisfacción)
$csqId = DB::table('questionnaires')->updateOrInsert(
['code' => 'CSQ-8', 'version' => 'v1'],
['title' => 'Client Satisfaction Questionnaire 8', 'is_active' => true, 'updated_at' => now(), 'created_at' => now()]
);
$csq = DB::table('questionnaires')->where(['code' => 'CSQ-8','version' => 'v1'])->first();
if ($csq) {
    $items = [
        ['Q1','¿Qué tan satisfecho está con la ayuda que recibió?'],
        ['Q2','¿Recibió el tipo de ayuda que deseaba?'],
        ['Q3','¿En qué medida nuestro programa satisfizo sus necesidades?'],
        ['Q4','Si un amigo necesitara ayuda similar, ¿recomendaría nuestro programa?'],
        ['Q5','¿Qué tan satisfecho está con la cantidad de ayuda recibida?'],
        ['Q6','¿Ha ayudado el programa a lidiar mejor con sus problemas?'],
        ['Q7','En general, ¿qué tan satisfecho está con el servicio recibido?'],
        ['Q8','¿Volvería a usar este programa si necesitara ayuda nuevamente?'],
    ];

    foreach ($items as $i => [$code, $text]) {
        DB::table('questionnaire_items')->updateOrInsert([
            'questionnaire_id' => $csq->id,
            'code' => $code,
        ], [
            'text' => $text,
            'sort_order' => $i + 1,
            'scale_min' => 1,
            'scale_max' => 5, // ✅ AJUSTADO
            'reverse_scored' => false,
            'updated_at' => now(),
            'created_at' => now(),
        ]);
    }
}

}
}