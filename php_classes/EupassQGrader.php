<?php


namespace EupassQ\PhpClasses;

use OpenAI;

class EupassQGrader 
{
    private $client;
    private $dbGb;

    public function __construct($_dbGb ) {

        $this->dbGb = $_dbGb;
        $this->client = OpenAI::client($this->openaikey);
        
    }

    function EupassQ_Delete_User_Results($result_code)
    {
        $arr_path = [];
        $user_answers = $this->dbGb->EupassQ_Get_User_EupassQ_Answers($result_code);

        foreach ($user_answers as $answer) {
            $question = $this->dbGb->Eupassq_Get_Single_Question($answer['eupqid']);
            
            if($question->euqtpe == 'audio')
            {
                array_push($arr_path, basename($answer['euqanswer']));
            }
        }

        foreach ($arr_path as $filename) {

            $file_path = WP_CONTENT_DIR . '/plugins/eupassq/assets/tmp/' . $filename;

            if (file_exists($file_path)) {
                unlink($file_path);
            } else {
                echo "File does not exist.";
            }
        }
        
        $deleted = $this->dbGb->EupassQ_Delete_User_EupassQ_Answers($result_code);

        return $deleted;
    }

    function EupassQ_Evaluate_Written_Production($answer, $level, $feedbackLang = 'English')
    {
        $feedback_language_instruction = '';

        $level = strtoupper(trim($level));

        if (in_array($level, ['A1', 'A2'])) {
            $feedback_language_instruction = "Give the feedback in $feedbackLang, clearly and simply, suitable for a student of level $level.";
        } else {
            $feedback_language_instruction = "Dai il feedback in italiano naturale e professionale, adatto a uno studente di livello $level.";
        }

        // Rubric text
        $rubricText = "Sei un insegnante di italiano. Valuta questo testo scritto da uno studente di livello $level.
        Valuta in modo coerente con gli standard di certificazione linguistica ufficiale.
        Valuta SOLO la PRODUZIONE SCRITTA.
        Criteri:
        - Efficacia comunicativa: 0–4
        - Correttezza morfosintattica: 0–3.5
        - Adeguatezza e ricchezza lessicale: 0–1.5
        - Ortografia e punteggiatura: 0–1
        Punteggio massimo: 10 punti.
        $feedback_language_instruction";

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

        } catch (\Throwable $e) {
            error_log("Errore valutazione scritta: " . $e->getMessage());
            return [
                'efficacia_comunicativa' => 0,
                'correttezza_morfosintattica' => 0,
                'lessico' => 0,
                'ortografia' => 0,
                'punteggio_totale' => 0,
                'feedback' => 'Errore durante la valutazione.'
            ];
        }
    }


    function EupassQ_Evaluate_Audio_Production($answer, $level, $feedbackLang = 'English') {
        $audioResult = [];
        $audioUrl = $answer['euqanswer'];

        $level = strtoupper(trim($level));
        if (in_array($level, ['A1', 'A2'])) {
            $feedback_language_instruction = "Give the feedback in $feedbackLang, clearly and simply, suitable for a student of level $level.";
        } else {
            $feedback_language_instruction = "Dai il feedback in italiano naturale e professionale, adatto a uno studente di livello $level.";
        }

        $rubricAudio = "Sei un insegnante di italiano. Valuta la trascrizione di questo audio registrato da uno studente di livello $level.
        Valuta in modo coerente con gli standard di certificazione linguistica ufficiale.
        - Efficacia comunicativa: 0–4
        - Correttezza morfosintattica: 0–3
        - Adeguatezza e ricchezza lessicale: 0–2
        - Pronuncia e intonazione: 0–1
        Punteggio massimo: 10 punti.
        $feedback_language_instruction";

        try {
            // Download and save temporary audio file
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
                    error_log("Errore nella trascrizione audio: " . $e->getMessage());
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
                                    'efficacia_comunicativa' => ['type' => 'number'],
                                    'correttezza_morfosintattica' => ['type' => 'number'],
                                    'lessico' => ['type' => 'number'],
                                    'pronuncia_intonazione' => ['type' => 'number'],
                                    'punteggio_totale' => ['type' => 'number'],
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

            }else
            {
                $audioResult = [
                    'efficacia_comunicativa' => 0,
                    'correttezza_morfosintattica' => 0,
                    'lessico' => 0,
                    'pronuncia_intonazione' => 0,
                    'punteggio_totale' => 0,
                    'feedback' => esc_attr__( 'audio-not-elaborate', 'eupassq' )
                ];
            }

            unlink($tempFile);

        } catch (\Throwable $e) {
            error_log("Errore nella valutazione audio: " . $e->getMessage());
        }

        return $audioResult;
    }



    function EupassQ_Handle_Submissions($result_code, $qsmId) {
      

       $answers = $this->dbGb->EupassQ_Get_User_EupassQ_Answers($result_code);
       $locale = get_locale();

       $feedbackLang = match (substr($locale, 0, 2)) {
            'de' => 'German',
            'en' => 'English',
            'it' => 'Italian',
            'fr' => 'French',
            'es' => 'Spanish',
            default => 'English'
        };

        
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
            
            
            $question = $this->dbGb->Eupassq_Get_Single_Question( intval($answer['eupqid']));

            $single_q = [];
            $single_q['type'] = $question->euqtpe;
            $single_q['question_text'] = stripslashes(wp_kses_post($question->euqcontent));
            $single_q['question_id']   = $answer['eupqid'];
            
            if($answer["euqanswer"] == "" || $answer["euqanswer"] == null || strlen($answer["euqanswer"]) < 2)
            {
                $single_q['efficacia_comunicativa'] = 0;
                $single_q['correttezza_morfosintattica'] = 0;
                $single_q['lessico'] = 0;
                $single_q['punteggio_totale'] = 0;
                $single_q['feedback'] = "No answer was provided for the question";
                
                if($question->euqtpe == 'text')
                {
                    $single_q['ortografia'] = 0;
                    array_push($res_array['eupassQ_text']['qanda'], $single_q);
                    $res_array['eupassQ_text']['partial'] += 0;
                    $res_array['eupassQ_text']['total'] += 1;
                }
                else
                {
                    $single_q['pronuncia_intonazione'] = 0;
                    array_push($res_array['eupassQ_audio']['qanda'], $single_q);
                    $res_array['eupassQ_audio']['partial'] += 0;
                    $res_array['eupassQ_audio']['total'] += 1;
                }
                
            }else
            {
                if($question->euqtpe == 'text')
                {
                    
                    $textResult = $this->EupassQ_Evaluate_Written_Production($answer, $question->euqlvl, $feedbackLang);
                    $single_q['efficacia_comunicativa'] = $textResult['efficacia_comunicativa'];
                    $single_q['correttezza_morfosintattica'] = $textResult['correttezza_morfosintattica'];
                    $single_q['lessico'] = $textResult['lessico'];
                    $single_q['ortografia'] = $textResult['ortografia'];
                    $single_q['punteggio_totale'] = $textResult['punteggio_totale'];
                    $single_q['feedback'] = stripslashes($textResult['feedback']);

                    array_push($res_array['eupassQ_text']['qanda'], $single_q);
                    $res_array['eupassQ_text']['partial'] += $textResult['punteggio_totale'];
                    $res_array['eupassQ_text']['total'] += 1;

                }else
                {
        
                    $audioResult = $this->EupassQ_Evaluate_Audio_Production($answer, $question->euqlvl, $feedbackLang);

                    $single_q['efficacia_comunicativa'] = $audioResult['efficacia_comunicativa'];
                    $single_q['correttezza_morfosintattica'] = $audioResult['correttezza_morfosintattica'];
                    $single_q['lessico'] = $audioResult['lessico'];
                    $single_q['pronuncia_intonazione'] = $audioResult['pronuncia_intonazione'];
                    $single_q['feedback'] = stripslashes($audioResult['feedback']);

                    array_push($res_array['eupassQ_audio']['qanda'], $single_q);
                    $res_array['eupassQ_audio']['partial'] += $audioResult['punteggio_totale'];
                    $res_array['eupassQ_audio']['total'] += 1;

                }
            
            }


        }


        $results_row = $this->dbGb->EupassQ_Query_QSM_Results($qsmId);


        $res_array['qsm']['total'] = intval($results_row->total) - 1;
        $res_array['qsm']['partial'] = intval($results_row->correct); //check
        $res_array['qsm']['qanda'] = $this->dbGb->EupassQ_Query_QSM_Question_Answer($results_row);

        $total_score_qsm = ($res_array['qsm']['partial'] * 10) / $res_array['qsm']['total'];
        $total_score_text = $res_array['eupassQ_text']['partial'] / $res_array['eupassQ_text']['total'];
        $total_score_audio = $res_array['eupassQ_audio']['partial'] / $res_array['eupassQ_audio']['total'];
       
        $tot = ($total_score_audio + $total_score_text + $total_score_qsm);
        $res_array['user_score'] = round($tot, 2);
        $res_array['user_percentage'] = round(($tot / 30),2) * 100;
        
    

        return $res_array;
        
    }
}