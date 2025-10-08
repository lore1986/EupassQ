<?php


namespace EupassQ\PhpClasses;


use OpenAI;

class EupassQGrader 
{
    private $openaikey = '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';
    
    public function __construct() {
        
    }

    function EupassQ_Handle_Submissions($answers) {
        
        ob_start();

        ?>

        <pre> answers <?php echo var_dump($answers) ?></pre> 
        <?php

        return ob_get_clean();

        //$client = OpenAI::client($this->openaikey);

        // $studentText = sanitize_textarea_field($_POST['student_text']);
        // $audioFile   = $_FILES['student_audio']['tmp_name'] ?? null;

        // // --- 1. TRASCRIZIONE AUDIO ---
        // $audioText = '';
        // if ($audioFile && file_exists($audioFile)) {
        //     try {
        //         $transcription = $client->audio()->transcribe([
        //             'model' => 'whisper-1', // or gpt-4o-transcribe if supported
        //             'file'  => fopen($audioFile, 'r'),
        //             'response_format' => 'verbose_json',
        //         ]);
        //         $audioText = $transcription->text;
        //     } catch (\Throwable $e) {
        //         echo "<h2>Errore nella trascrizione audio</h2>";
        //         echo "<pre>" . $e->getMessage() . "</pre>";
        //     }
        // }

        // // --- 2. RUBRICHE ---
        // $rubricText = "Valuta SOLO la PRODUZIONE SCRITTA (livello B1). Criteri:
        // - Efficacia comunicativa: 0–4
        // - Correttezza morfosintattica: 0–3.5
        // - Adeguatezza e ricchezza lessicale: 0–1.5
        // - Ortografia e punteggiatura: 0–1
        // Punteggio massimo: 10 punti.";

        // $rubricAudio = "Valuta SOLO la PRODUZIONE ORALE (livello B1). Criteri:
        // - Efficacia comunicativa: 0–4
        // - Correttezza morfosintattica: 0–3
        // - Adeguatezza e ricchezza lessicale: 0–2
        // - Pronuncia e intonazione: 0–1
        // Punteggio massimo: 10 punti.";

        // // --- 3. VALUTAZIONE SCRITTA ---
        // $textResult = [];
        // try {
        //     $textGrade = $client->chat()->create([
        //         'model' => 'gpt-4.1',
        //         'messages' => [
        //             ['role' => 'system', 'content' => $rubricText],
        //             ['role' => 'user', 'content' => $studentText],
        //         ],
        //         'response_format' => [
        //             'type' => 'json_schema',
        //             'json_schema' => [
        //                 'name' => 'grading_scritta',
        //                 'schema' => [
        //                     'type' => 'object',
        //                     'properties' => [
        //                         'efficacia_comunicativa' => ['type' => 'number'],
        //                         'correttezza_morfosintattica' => ['type' => 'number'],
        //                         'lessico' => ['type' => 'number'],
        //                         'ortografia' => ['type' => 'number'],
        //                         'punteggio_totale' => ['type' => 'number'],
        //                         'feedback' => ['type' => 'string'],
        //                     ],
        //                     'required' => [
        //                         'efficacia_comunicativa',
        //                         'correttezza_morfosintattica',
        //                         'lessico',
        //                         'ortografia',
        //                         'punteggio_totale',
        //                         'feedback'
        //                     ],
        //                 ],
        //             ],
        //         ],
        //     ]);
        //     $textResult = json_decode($textGrade->choices[0]->message->content, true);
        // } catch (\Throwable $e) {
        //     echo "<h2>Errore nella valutazione scritta</h2>";
        //     echo "<pre>" . $e->getMessage() . "</pre>";
        // }

        // // --- 4. VALUTAZIONE ORALE ---
        // $audioResult = [];
        // if ($audioText) {
        //     try {
        //         $audioGrade = $client->chat()->create([
        //             'model' => 'gpt-4.1',
        //             'messages' => [
        //                 ['role' => 'system', 'content' => $rubricAudio],
        //                 ['role' => 'user', 'content' => $audioText],
        //             ],
        //             'response_format' => [
        //                 'type' => 'json_schema',
        //                 'json_schema' => [
        //                     'name' => 'grading_orale',
        //                     'schema' => [
        //                         'type' => 'object',
        //                         'properties' => [
        //                             'efficacia_comunicativa' => ['type' => 'integer'],
        //                             'correttezza_morfosintattica' => ['type' => 'integer'],
        //                             'lessico' => ['type' => 'integer'],
        //                             'pronuncia_intonazione' => ['type' => 'integer'],
        //                             'punteggio_totale' => ['type' => 'integer'],
        //                             'feedback' => ['type' => 'string'],
        //                         ],
        //                         'required' => [
        //                             'efficacia_comunicativa',
        //                             'correttezza_morfosintattica',
        //                             'lessico',
        //                             'pronuncia_intonazione',
        //                             'punteggio_totale',
        //                             'feedback'
        //                         ],
        //                     ],
        //                 ],
        //             ],
        //         ]);
        //         $audioResult = json_decode($audioGrade->choices[0]->message->content, true);
        //     } catch (\Throwable $e) {
        //         echo "<h2>Errore nella valutazione orale</h2>";
        //         echo "<pre>" . $e->getMessage() . "</pre>";
        //     }
        // }

        // // --- 5. RISULTATI ---
        // echo "<h2>Produzione Scritta</h2>";
        // echo "<pre>" . htmlspecialchars(json_encode($textResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";

        // if ($audioResult) {
        //     echo "<h2>Produzione Orale</h2>";
        //     echo "<pre>" . htmlspecialchars(json_encode($audioResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
        // }
    }
}