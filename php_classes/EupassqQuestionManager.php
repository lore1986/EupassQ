<?php

namespace EupassQ\PhpClasses;

class EupassqQuestionManager {

    private $dbGb;

    public function __construct($_dbGb) {

        $this->dbGb = $_dbGb;
        add_shortcode( 'eupassq_quiz', [$this, 'EupassqQuestion_Generate_Quiz_Form'] );
        add_action('wp_ajax_eupass_qform_submit', [$this, 'Eupassq_handle_form_submission']);
        add_action('wp_ajax_nopriv_eupass_qform_submit', [$this, 'Eupassq_handle_form_submission']);
        // add_action('admin_post_eupass_qform_submit', [$this, 'Eupassq_handle_form_submission']);
        // add_action('admin_post_nopriv_eupass_qform_submit', [$this, 'Eupassq_handle_form_submission']);
        
    }


    public function Eupassq_EnqueueQuestionScripts()
    {
        wp_enqueue_script('eq_qs_script',  plugin_dir_url(__DIR__) . 'assets/js/eq_qs_script.js', array('jquery'), null, false );
    
        wp_localize_script('eq_qs_script', 'EupQ_Ajax_Obj', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            //'templatesUrl' => plugin_dir_url(__FILE__)  . 'assets/html-templates/',
            'nonce' => wp_create_nonce('euq_pass_nonce')
            // 'tifpBootstrap' => 'tfipf-bootstrap'
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

        //pick all questions available for the specified level
        //create two array based on type (two big arrays)
        //select textqs/audioqs number of random question from each array
        //keep trace of the selected indexes 
        //serve quiz

        $question_pool = $this->dbGb->Eupassq_Get_All_Questions_Of_Level($levelin);

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

         ob_start();
        ?>
        <form id="eupassq_quiz_form" class="rq-form" >
            <input name="security" value="<?php echo wp_create_nonce('rq_ajax_nonce'); ?>">
            <?php foreach ($question_pool as $index => $question) : ?>
                <div class="eupassq-question" data-index="<?php echo $index; ?>" 
                    data-euqtpe="<?php echo $question['euqtpe']; ?>" data-euid="<?php echo $question['euqid']; ?>">

                    <label><strong><?php echo esc_html($question['euqcontent']); ?></strong></label><br>

                    <?php if($question['euqtpe'] == 'text') { ?>
                        <textarea name="eupassq_qansw[<?php echo $index; ?>]" rows="3" style="width:100%;"></textarea>
                    <?php } else { ?>
                        <button type="button" class="start-record">Start Recording</button>
                        <button type="button" class="stop-record" disabled>Stop Recording</button>
                        <audio controls class="audio-playback"></audio>
                        <button type="button" class="re-record" style="display:none;">Re-record</button>
                        <input  name="eupassq_qansw[<?php echo $index; ?>]" class="audio-data"/>
                    <?php } ?>

                    <input name="eupassq_qi[<?php echo $index; ?>]" value="<?php echo $question['euqid']; ?>"/>
                
                </div>
                <hr>
            <?php endforeach; ?>
            <button id="eupassq_quiz_form_submit" onclick="submit_form_temp()" type="button">Submit Answers</button>
            <div id="rq-response" style="margin-top:10px;"></div>
        </form>
        <?php

        return ob_get_clean();
        
        //return '<pre> Audio Pool: ' . print_r($question_pool, true) . '</pre>' ;
    }

    function EupassQ_Upload_File( $file ) {

        require_once(ABSPATH . 'wp-admin/includes/file.php');

        add_filter( 'upload_dir', 'EupassQ_Upload_Dir' );


        $uploaded = wp_handle_upload( $file, array( 'test_form' => false ) );

    
        remove_filter( 'upload_dir', 'EupassQ_Upload_Dir' );

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
        check_ajax_referer('rq_ajax_nonce', 'security');


        $questions = isset($_POST['eupassq_qi']) ? (array) $_POST['eupassq_qi'] : [];
        $answers   = isset($_POST['eupassq_qansw'])   ? (array) $_POST['eupassq_qansw']   : [];


        $processed = [];


        foreach ($questions as $index => $qid) {

            $qid = intval($qid);
            $squ = $this->dbGb->Eupassq_Get_Single_Question($qid);

            $entry = [
                'question_id' => $qid,
                'answer' => null,
            ];

            switch ($squ->euqtpe) {
                case 'text':
                    {
                        $entry['answer'] = isset($answers[$index]) ? sanitize_textarea_field($answers[$index]) : '';
                    }
                    break;
                case 'audio':
                    {
                        $file = [
                            'name'     => $_FILES['eupassq_qansw']['name'][$index],
                            'type'     => $_FILES['eupassq_qansw']['type'][$index],
                            'tmp_name' => $_FILES['eupassq_qansw']['tmp_name'][$index],
                            'error'    => $_FILES['eupassq_qansw']['error'][$index],
                            'size'     => $_FILES['eupassq_qansw']['size'][$index],
                        ];
                    }
                    break;
                default:
                    ///here case type unknown return error/ unknown type should not be picked
                    break;
            }
            
                

                // Use WordPress to safely handle upload
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                $upload = wp_handle_upload($file, ['test_form' => false]);

                if (!isset($upload['error'])) {
                    $entry['answer_audio'] = $upload['url']; // store URL or save to DB
                
            }

            $processed[] = $entry;
        }

        // TODO: Save results to database if needed
        // e.g., $wpdb->insert(...)

        wp_send_json_success('Answers received successfully');
    }

    
}