<?php

namespace EupassQ\PhpClasses;

class EupassqQuestionManager {
    

    private $dbGb, $nc;

    public function __construct($_dbGb, $_nc) {

        $this->dbGb = $_dbGb;
        $this->nc = $_nc;

        // add_shortcode( 'eupassq_quiz', [$this, 'EupassqQuestion_Generate_Quiz_Form'] );
        
        add_action('wp_ajax_eupass_qform_submit', [$this, 'Eupassq_handle_form_submission']);
        add_action('wp_ajax_nopriv_eupass_qform_submit', [$this, 'Eupassq_handle_form_submission']);
    }



    public function Eupassq_EnqueueQuestionScripts()
    {
        wp_enqueue_style('eupassq_bootstrap_css', plugin_dir_url(__DIR__) . 'assets/css/bootstrap.eupassq.css');
        wp_enqueue_style('eupassq_css', plugin_dir_url(__DIR__) . 'assets/css/eupassq.css');
        wp_enqueue_script('eq_qs_script',  plugin_dir_url(__DIR__) . 'assets/js/eq_qs_script.js', array('jquery'), null, false );

       
        wp_localize_script('eq_qs_script', 'EupQ_Ajax_Obj', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'templatesUrl'  => plugin_dir_url(__DIR__)  . 'assets/templates/html/',
            'nonce' => [
                'quiz_out' => $this->nc::create($this->nc::QUIZ_SUBMIT) 
            ]
        ));
    }

    function Eupassq_Pick_Random_Questions($arrayq, $return_arr, $n_questions)
    {
        if($n_questions > count($arrayq)) {return null;}


        for ($i=0; $i < $n_questions; $i++) { 
            
            $rand_i = random_int(0, count($arrayq) - 1);
            
            $qs = $arrayq[$rand_i];

            if(in_array($qs, $return_arr))
            {
                while(in_array($qs, $return_arr))
                {
                    $rand_i = random_int(0, count($arrayq) - 1);
                    $qs = $arrayq[$rand_i];
                }
            }

            $return_arr[$i] = $qs;
        }

        return $return_arr;
    }

    function EupassqQuestion_Generate_Quiz_Form($atts) {

        $levelin = isset($atts['level']) ? sanitize_text_field($atts['level']) : 'A1';
        $textqs_n = isset($atts['textqs']) ? intval($atts['textqs']) : 3; 
        $audioqs_n = isset($atts['audioqs']) ? intval($atts['audioqs']) : 3;

        $qsm_unique_id = isset($atts['qsm_id']) ? sanitize_text_field($atts['qsm_id']) : null;
        $user_info = isset($atts['user_info']) ? sanitize_text_field($atts['user_info']) : null;
        $question_pool = $this->dbGb->Eupassq_Get_All_Questions_Of_Level($levelin);

        $arr_val = ['user_info' => $user_info, 'qsm_unique_id' => $qsm_unique_id, 'question_pool' => null];

        if($question_pool == null)
        {
            return $arr_val;
        }

        $audio_pool = [];
        $text_pool = [];

        $audioqs_pool = array_filter($question_pool, function($q){ return $q['euqtpe'] == 'audio';});
        $textqs_pool = array_filter($question_pool, function($q){return $q['euqtpe'] == 'text';});
        
        shuffle($audioqs_pool);
        shuffle($textqs_pool);

        $audio_pool = $this->Eupassq_Pick_Random_Questions($audioqs_pool, $audio_pool, $audioqs_n);
        $text_pool = $this->Eupassq_Pick_Random_Questions($textqs_pool, $text_pool, $textqs_n);

        $question_pool = array_merge($audio_pool, $text_pool);
        shuffle($question_pool);

        $arr_val = ['user_info' => $user_info, 'qsm_unique_id' => $qsm_unique_id, 'question_pool' => $question_pool];

        return $arr_val;
        
    }

    function EupassQ_Upload_File( $file ) {

        require_once(ABSPATH . 'wp-admin/includes/file.php');

        add_filter( 'upload_dir', [$this, 'EupassQ_Upload_Dir'] );


        $uploaded = wp_handle_upload( $file, array( 'test_form' => false ) );

    
        remove_filter( 'upload_dir',  [$this, 'EupassQ_Upload_Dir'] );

        return $uploaded;
    }

    function EupassQ_Upload_Dir( $dirs ) {

        $plugin_dir = WP_CONTENT_DIR . '/plugins/eupassq/assets/tmp';
        $plugin_url = content_url( '/plugins/eupassq/assets/tmp' );

        $dirs['path'] = $plugin_dir;
        $dirs['url']  = $plugin_url;
        $dirs['subdir'] = '';
        
        return $dirs;
    }

    function Eupassq_handle_form_submission() {
        
        // Security check
        $this->nc::die_if_invalid( $this->nc::QUIZ_SUBMIT, 'eupassqnc');

        

        $questions = isset($_POST['eupassq_qi']) ? (array) $_POST['eupassq_qi'] : [];
        $answers   = isset($_POST['eupassq_qansw'])   ? (array) $_POST['eupassq_qansw']   : [];


        $processed = [];


        foreach ($questions as $index => $qid) {

            $qid = intval($qid);
            $squ = $this->dbGb->Eupassq_Get_Single_Question($qid);

            $entry = [
                'question_id' => $qid,
                'answer' => null,
                'uid' => 3
            ];

            switch ($squ->euqtpe) {
                case 'text':
                    {
                        $entry['answer'] = isset($answers[$index]) ? sanitize_textarea_field($answers[$index]) : '';
                    }
                    break;
                case 'audio':
                    {
                        $file = null;
                        
                        foreach($_FILES as $key => $value) {

                            $splitted = explode('_', $key);
                            $lid = $splitted[2];
                            
                            if($lid == $squ->euqid)
                            {
                                $file = [
                                    'name'     => $key . '_' . $value['name'],
                                    'type'     => $value['type'],
                                    'tmp_name' => $value['tmp_name'],
                                    'error'    => $value['error'],
                                    'size'     => $value['size'],
                                ];
                                

                                $upload = $this->EupassQ_Upload_File($file);

                                if (!isset($upload['error'])) {
                                    $entry['answer'] = $upload['url']; //url of recording
                                }
                            }
                        }
                       
                    }
                    break;
                default:
                    ///here case type unknown return error/ unknown type should not be picked
                    break;
            }

            $processed[] = $entry;
        }


        $code = $this->dbGb->Eupassq_Insert_Quiz_Entry($processed);
            

        $pretty_url = home_url('/results/' . $code);

        $res_obj = [
            'redirect' => $pretty_url
        ];

        wp_send_json_success($res_obj);

    }


}