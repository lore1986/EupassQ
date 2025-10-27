<?php 

namespace EupassQ\PhpClasses;

class EupassQBridgeQSM {

    private $dbGb;
    public function __construct($_dbGb) {
        
        $this->dbGb = $_dbGb;
        add_action('wp_ajax_nopriv_eupassq_anubi_qsm', array($this, 'EupassQ_T_QSM'));
        add_action('wp_ajax_eupassq_anubi_qsm', array($this, 'EupassQ_T_QSM'));
    }

    public function EupassQ_T_QSM()
    {
        sleep(1);
        $quiz_unique_id = isset($_POST['uuid']) ? sanitize_key($_POST['uuid']) : 0;
        $quiz_setting = $this->dbGb->EupassQ_Set_Quiz_Settings($quiz_unique_id);

        $response = [
            'exist' => $quiz_setting['exist'],
            'uidq'  => esc_attr__( 'proceed-next', 'eupassq' ),
            'link'  => $quiz_setting['link']
        ];
         
        wp_send_json_success($response);
    }



}
