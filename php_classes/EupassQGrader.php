<?php


namespace EupassQ\PhpClasses;


use OpenAI;

class EupassQGrader 
{
    private $openaikey = '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';
    private $client;

    public function __construct() {
        $this->client = OpenAI::client($this->openaikey);
    }

    function EupassQ_Evaluate_Written_Production($answer, $level)
    {
        $rubricText = "Sei un insegnante di italiano. Valuta questo testo scritto da uno studente di livello 
        $level. Valuta in modo coerente con gli standard di certificazione linguistica ufficiale.
        Valuta SOLO la PRODUZIONE SCRITTA. Criteri:
        - Efficacia comunicativa: 0–4
        - Correttezza morfosintattica: 0–3.5
        - Adeguatezza e ricchezza lessicale: 0–1.5
        - Ortografia e punteggiatura: 0–1
        Punteggio massimo: 10 punti.";

        $studentText = $answer['euqanswer'];

        try {
            $textGrade = $this->client->chat()->create([
                'model' => 'gpt-4.1',
                'messages' => [
                    ['role' => 'system', 'content' => $rubricText],
                    ['role' => 'user', 'content' => $studentText],
                ],
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'grading_scritta',
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'efficacia_comunicativa' => ['type' => 'number'],
                                'correttezza_morfosintattica' => ['type' => 'number'],
                                'lessico' => ['type' => 'number'],
                                'ortografia' => ['type' => 'number'],
                                'punteggio_totale' => ['type' => 'number'],
                                'feedback' => ['type' => 'string'],
                            ],
                            'required' => [
                                'efficacia_comunicativa',
                                'correttezza_morfosintattica',
                                'lessico',
                                'ortografia',
                                'punteggio_totale',
                                'feedback'
                            ],
                        ],
                    ],
                ],
            ]);
            
            return json_decode($textGrade->choices[0]->message->content, true);
        } 
        catch (\Throwable $e) {
            // $totalGrades['feedback'][] = "Errore: " . $e->getMessage();
        }

    }

    function EupassQ_Evaluate_Audio_Production($answer, $level) {
        
        $audioResult = [];
        $audioUrl = $answer['euqanswer'];

        $rubricAudio = "Sei un insegnante di italiano. Valuta la trascrizione di questo audio registrato da uno studente di livello 
        $level. Valuta in modo coerente con gli standard di certificazione linguistica ufficiale.
        Valuta considerando che questo testo è la trascrizione di un messaggio vocale.
        - Efficacia comunicativa: 0–4
        - Correttezza morfosintattica: 0–3
        - Adeguatezza e ricchezza lessicale: 0–2
        - Pronuncia e intonazione: 0–1
        Punteggio massimo: 10 punti.";

        try {
            // 1. Download the audio temporarily
            $tempFile = tempnam(sys_get_temp_dir(), 'audio_') . '.webm';
            file_put_contents($tempFile, file_get_contents($audioUrl));

            // 2. Transcribe the audio
            // $transcription = $this->client->audio()->transcriptions()->create([
            //     'file' => fopen($tempFile, 'r'),
            //     'model' => 'gpt-4o-mini-transcribe', // or 'whisper-1'
            // ]);
            $audioText = '';
            if ($tempFile && file_exists($tempFile)) {
                try {
                    $transcription = $this->client->audio()->transcribe([
                        'model' => 'whisper-1', // or gpt-4o-transcribe if supported
                        'file'  => fopen($tempFile, 'r'),
                        'response_format' => 'verbose_json',
                    ]);
                    $audioText = $transcription->text;
                } catch (\Throwable $e) {
                    echo "<h2>Errore nella trascrizione audio</h2>";
                    echo "<pre>" . $e->getMessage() . "</pre>";
                }
            }
            

            // $audioText = $transcription->text;

            // 3. Grade the transcription
            if ($audioText) {
                $audioGrade = $this->client->chat()->create([
                    'model' => 'gpt-4.1',
                    'messages' => [
                        ['role' => 'system', 'content' => $rubricAudio],
                        ['role' => 'user', 'content' => $audioText],
                    ],
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'grading_orale',
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'efficacia_comunicativa' => ['type' => 'integer'],
                                    'correttezza_morfosintattica' => ['type' => 'integer'],
                                    'lessico' => ['type' => 'integer'],
                                    'pronuncia_intonazione' => ['type' => 'integer'],
                                    'punteggio_totale' => ['type' => 'integer'],
                                    'feedback' => ['type' => 'string'],
                                ],
                                'required' => [
                                    'efficacia_comunicativa',
                                    'correttezza_morfosintattica',
                                    'lessico',
                                    'pronuncia_intonazione',
                                    'punteggio_totale',
                                    'feedback'
                                ],
                            ],
                        ],
                    ],
                ]);

                $audioResult = json_decode($audioGrade->choices[0]->message->content, true);
            }

            // 4. Clean up temporary file
            unlink($tempFile);

        } catch (\Throwable $e) {
            error_log("Errore nella valutazione audio: " . $e->getMessage());
        }

        return $audioResult;
    }


    function EupassQ_Handle_Submissions($answers) {
      

        $studentLevel = 'A1'; 
        
        $totalGradesText = [
            'efficacia_comunicativa' => 0,
            'correttezza_morfosintattica' => 0,
            'lessico' => 0,
            'ortografia' => 0,
            'punteggio_totale' => 0,
            'feedback' => [],
        ];

        $totalGradesAudio = [
            'efficacia_comunicativa' => 0,
            'correttezza_morfosintattica' => 0,
            'lessico' => 0,
            'pronuncia_intonazione' => 0,
            'punteggio_totale' => 0,
            'feedback' => [],
        ];
        

        foreach ($answers as $answer) {
            
            global $wpdb;
            $qTable = $wpdb->prefix . 'eupqs';

            $question = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $qTable WHERE euqid = %d", intval($answer['eupqid']))
            );

            if($question->euqtpe == 'text')
            {
                $textResult = $this->EupassQ_Evaluate_Written_Production($answer, $studentLevel);
                $totalGradesText['efficacia_comunicativa'] += $textResult['efficacia_comunicativa'];
                $totalGradesText['correttezza_morfosintattica'] += $textResult['correttezza_morfosintattica'];
                $totalGradesText['lessico'] += $textResult['lessico'];
                $totalGradesText['ortografia'] += $textResult['ortografia'];
                $totalGradesText['punteggio_totale'] += $textResult['punteggio_totale'];

                $totalGradesText['feedback'][] = $textResult['feedback'];
            }else
            {
                $audioResult = $this->EupassQ_Evaluate_Audio_Production($answer, $studentLevel);
                $totalGradesAudio['efficacia_comunicativa'] += $audioResult['efficacia_comunicativa'];
                $totalGradesAudio['correttezza_morfosintattica'] += $audioResult['correttezza_morfosintattica'];
                $totalGradesAudio['lessico'] += $audioResult['lessico'];
                $totalGradesAudio['pronuncia_intonazione'] += $audioResult['pronuncia_intonazione'];
                $totalGradesAudio['punteggio_totale'] += $audioResult['punteggio_totale'];

                $totalGradesAudio['feedback'][] = $audioResult['feedback'];

            }
        }

        $combinedGradeText = [
            'efficacia_comunicativa' => $totalGradesText['efficacia_comunicativa']/2,
            'correttezza_morfosintattica' => $totalGradesText['correttezza_morfosintattica']/2,
            'lessico' => $totalGradesText['lessico']/2,
            'ortografia' => $totalGradesText['ortografia']/2,
            'punteggio_totale' => $totalGradesText['punteggio_totale']/2 ,
            'feedback' => implode("\n---\n", $totalGradesText['feedback']), // combine feedback
        ];
        $combinedGradeText['punteggio_totale'] = min(10, $combinedGradeText['punteggio_totale']);

        $combinedGradeAudio = [
            'efficacia_comunicativa' => $totalGradesAudio['efficacia_comunicativa'] /2,
            'correttezza_morfosintattica' => $totalGradesAudio['correttezza_morfosintattica']/2,
            'lessico' => $totalGradesAudio['lessico']/2,
            'pronuncia_intonazione' => $totalGradesAudio['pronuncia_intonazione']/2,
            'punteggio_totale' => $totalGradesAudio['punteggio_totale']/2 ,
            'feedback' => implode("\n---\n", $totalGradesAudio['feedback']), // combine feedback
        ];
        $combinedGradeAudio['punteggio_totale'] = min(10, $combinedGradeAudio['punteggio_totale']);

        $obj = [
            'textresults' => $combinedGradeText,
            'audioresults' => $combinedGradeAudio
            // 'feedback' => $combinedGrade['feedback'] 
        ];

        return $obj;
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