<?php


namespace EupassQ\PhpClasses;


use OpenAI;

class EupassQGrader 
{
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
        - Efficacia comunicativa: 0–4
        - Correttezza morfosintattica: 0–3
        - Adeguatezza e ricchezza lessicale: 0–2
        - Pronuncia e intonazione: 0–1
        Punteggio massimo: 10 punti.";

        try {

            $tempFile = tempnam(sys_get_temp_dir(), 'audio_') . '.webm';
            file_put_contents($tempFile, file_get_contents($audioUrl));

            $audioText = '';
            if ($tempFile && file_exists($tempFile)) {
                try {
                    $transcription = $this->client->audio()->transcribe([
                        'model' => 'whisper-1', 
                        'file'  => fopen($tempFile, 'r'),
                        'response_format' => 'verbose_json',
                    ]);
                    $audioText = $transcription->text;
                } catch (\Throwable $e) {
                    echo "<h2>Errore nella trascrizione audio</h2>";
                    echo "<pre>" . $e->getMessage() . "</pre>";
                }
            }
            
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
        
    }
}