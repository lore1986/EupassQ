<?php


namespace EupassQ\PhpClasses;

use OpenAI;

class EupassQGrader 
{
    private $client;
    private $dbGb;

    public function __construct() {

        $this->dbGb = new EupassqDatabase();
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


    function EupassQ_Handle_Submissions($result_code, $qsmId) {
      
        // $totalGradesText = [
        //     'efficacia_comunicativa' => 0,
        //     'correttezza_morfosintattica' => 0,
        //     'lessico' => 0,
        //     'ortografia' => 0,
        //     'punteggio_totale' => 0,
        //     'feedback' => [],
        // ];

        // $totalGradesAudio = [
        //     'efficacia_comunicativa' => 0,
        //     'correttezza_morfosintattica' => 0,
        //     'lessico' => 0,
        //     'pronuncia_intonazione' => 0,
        //     'punteggio_totale' => 0,
        //     'feedback' => [],
        // ];

       $answers = $this->dbGb->EupassQ_Get_User_EupassQ_Answers($result_code);
        
        $res_array = [
            'qsm' => [
                'total' => 0,
                'partial' => 0,
                'qanda' => []
            ],
            'eupassQ_text' => [
                'total' => 0,
                'partial' => 0,
                'qanda' => []
            ],
            'eupassQ_audio' => [
                'total' => 0,
                'partial' => 0,
                'qanda' => []
            ],
            'user_score' => 0,
            'user_percentage' => 0
        ];
    
        foreach ($answers as $answer) {
            
            //answer could be empty in that case count as not answered ==> 0 point no feedback

            $question = $this->dbGb->Eupassq_Get_Single_Question( intval($answer['eupqid']));

            $single_q = [];
            $single_q['type'] = $question->euqtpe;
            $single_q['question_text'] = stripslashes(wp_kses_post($question->euqcontent));
            $single_q['question_id']   = $answer['eupqid'];

            if($question->euqtpe == 'text')
            {
                $textResult = $this->EupassQ_Evaluate_Written_Production($answer, $question->euqlvl);
                $single_q['efficacia_comunicativa'] = $textResult['efficacia_comunicativa'];
                $single_q['correttezza_morfosintattica'] = $textResult['correttezza_morfosintattica'];
                $single_q['lessico'] = $textResult['lessico'];
                $single_q['ortografia'] = $textResult['ortografia'];
                $single_q['punteggio_totale'] = $textResult['punteggio_totale'];
                $single_q['feedback'] = $textResult['feedback'];


                $res_array['eupassQ_text']['qanda'] = $single_q;
                $res_array['eupassQ_text']['partial'] += $textResult['punteggio_totale'];
                $res_array['eupassQ_text']['total'] += 1;
                // $totalGradesText['efficacia_comunicativa'] += $textResult['efficacia_comunicativa'];
                // $totalGradesText['correttezza_morfosintattica'] += $textResult['correttezza_morfosintattica'];
                // $totalGradesText['lessico'] += $textResult['lessico'];
                // $totalGradesText['ortografia'] += $textResult['ortografia'];
                // $totalGradesText['punteggio_totale'] += $textResult['punteggio_totale'];

                // $totalGradesText['feedback'][] = $textResult['feedback'];
            }else
            {
                $audioResult = $this->EupassQ_Evaluate_Audio_Production($answer, $question->euqlvl);

                $single_q['efficacia_comunicativa'] = $audioResult['efficacia_comunicativa'];
                $single_q['correttezza_morfosintattica'] = $audioResult['correttezza_morfosintattica'];
                $single_q['lessico'] = $audioResult['lessico'];
                $single_q['pronuncia_intonazione'] = $audioResult['punteggio_totale'];
                $single_q['feedback'] = $audioResult['feedback'];


                $res_array['eupassQ_audio']['qanda'] = $single_q;
                $res_array['eupassQ_audio']['partial'] += $audioResult['punteggio_totale'];
                $res_array['eupassQ_audio']['total'] += 1;
                // $totalGradesAudio['efficacia_comunicativa'] += $audioResult['efficacia_comunicativa'];
                // $totalGradesAudio['correttezza_morfosintattica'] += $audioResult['correttezza_morfosintattica'];
                // $totalGradesAudio['lessico'] += $audioResult['lessico'];
                // $totalGradesAudio['pronuncia_intonazione'] += $audioResult['pronuncia_intonazione'];
                // $totalGradesAudio['punteggio_totale'] += $audioResult['punteggio_totale'];

                // $totalGradesAudio['feedback'][] = $audioResult['feedback'];
            }


        }


        $results_row = $this->dbGb->EupassQ_Query_QSM_Results($qsmId);

        $res_array['qsm']['total'] = intval($results_row->total) - 1;
        $res_array['qsm']['partial'] = intval($results_row->correct);
        $res_array['qsm']['qanda'] = $this->dbGb->EupassQ_Query_QSM_Question_Answer($results_row);
       
            // 'max_total' => 0,
            // 'user_score' =>
    

        $total_score_qsm = ($res_array['qsm']['partial'] * 10) / $res_array['qsm']['total'];
        $total_score_text = $res_array['eupassQ_text']['partial'] / $res_array['eupassQ_text']['total'];
        $total_score_audio = $res_array['eupassQ_audio']['partial'] / $res_array['eupassQ_audio']['total'];
       
        $tot = ($total_score_audio + $total_score_text + $total_score_qsm);
        $res_array['user_score'] = round($tot, 2);
        $res_array['user_percentage'] = round(($tot / 30),2) * 100;
        
        

        

        // $combinedGradeText = [
        //     'efficacia_comunicativa' => $totalGradesText['efficacia_comunicativa']/2,
        //     'correttezza_morfosintattica' => $totalGradesText['correttezza_morfosintattica']/2,
        //     'lessico' => $totalGradesText['lessico']/2,
        //     'ortografia' => $totalGradesText['ortografia']/2,
        //     'punteggio_totale' => $totalGradesText['punteggio_totale']/2 ,
        //     'feedback' => implode("\n---\n", $totalGradesText['feedback']), 
        // ];
        // $combinedGradeText['punteggio_totale'] = min(10, $combinedGradeText['punteggio_totale']);

        // $combinedGradeAudio = [
        //     'efficacia_comunicativa' => $totalGradesAudio['efficacia_comunicativa'] /2,
        //     'correttezza_morfosintattica' => $totalGradesAudio['correttezza_morfosintattica']/2,
        //     'lessico' => $totalGradesAudio['lessico']/2,
        //     'pronuncia_intonazione' => $totalGradesAudio['pronuncia_intonazione']/2,
        //     'punteggio_totale' => $totalGradesAudio['punteggio_totale']/2 ,
        //     'feedback' => implode("\n---\n", $totalGradesAudio['feedback']), // combine feedback
        // ];
        // $combinedGradeAudio['punteggio_totale'] = min(10, $combinedGradeAudio['punteggio_totale']);


        

        // $obj = [
        //     'textresults' => $combinedGradeText,
        //     'audioresults' => $combinedGradeAudio
        //     // 'feedback' => $combinedGrade['feedback'] 
        // ];

        return $res_array;
        
    }
}