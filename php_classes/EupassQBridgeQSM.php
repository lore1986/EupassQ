<?php 

namespace EupassQ\PhpClasses;

class EupassQBridgeQSM {

    private $dbGb;
    public function __construct($_dbGb) {
        
        $this->dbGb = $_dbGb;

        // add_action('qsm_quiz_submitted', [$this, 'EupassQ_intercept_QSM_Submit'], 100, 4);
        // add_action('qsm_saved_the_quiz', [$this, 'EupassQ_intercept_QSM_Save'], 10, 4);
        // add_action('qsm_after_results_page', [$this, 'EupassQ_intercept_inject'], 1);
        // add_action('qsm_action_before_page', [$this, 'EupassQ_Anus_QSM'], 1000);
        //add_filter('qsm_results_page_content', [$this, 'eupassq_retake_button_filter'], 10, 2);
        add_action('wp_ajax_nopriv_eupassq_anubi_qsm', array($this, 'EupassQ_T_QSM'));
        add_action('wp_ajax_eupassq_anubi_qsm', array($this, 'EupassQ_T_QSM'));
        //qmn_process_quiz
    }

    public function EupassQ_T_QSM()
    {
        sleep(1);
        $quiz_unique_id = isset($_POST['uuid']) ? sanitize_key($_POST['uuid']) : 0;
        $quiz_setting = $this->dbGb->EupassQ_Set_Quiz_Settings($quiz_unique_id);

        $response = [
            'exist' => $quiz_setting['exist'], 
            'uidq' => $quiz_unique_id
        ];

        wp_send_json_success($response);
    }

    


}
