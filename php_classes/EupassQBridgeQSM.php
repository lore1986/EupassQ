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

        $header = [
            'intro' => wp_kses_post( __( 'header-intro', 'eupassq' ) ),
        ];

        $section_overview = [
            'title'     => esc_html__( 'section-overview-title', 'eupassq' ),
            'intro'     => wp_kses_post( __( 'section-overview-intro', 'eupassq' ) ),
            'written'   => wp_kses_post( __( 'section-overview-written', 'eupassq' ) ),
            'oral'      => wp_kses_post( __( 'section-overview-oral', 'eupassq' ) ),
            'important' => wp_kses_post( __( 'section-overview-important', 'eupassq' ) ),
        ];

        $recording_demo = [
            'title'        => esc_html__( 'recording-demo-title', 'eupassq' ),
            'intro'        => wp_kses_post( __( 'recording-demo-intro', 'eupassq' ) ),
            'start'        => esc_html__( 'recording-demo-start', 'eupassq' ),
            'stop'         => esc_html__( 'recording-demo-stop', 'eupassq' ),
            'instructions' => wp_kses_post( __( 'recording-demo-instructions', 'eupassq' ) ),
            'error'        => wp_kses_post( __( 'recording-demo-error', 'eupassq' ) ),
        ];

        $results_info = [
            'title'  => esc_html__( 'results-info-title', 'eupassq' ),
            'item1'  => wp_kses_post( __( 'results-info-item1', 'eupassq' ) ),
            'item2'  => wp_kses_post( __( 'results-info-item2', 'eupassq' ) ),
            'item3'  => wp_kses_post( __( 'results-info-item3', 'eupassq' ) ),
            'item4a' => wp_kses_post( __( 'results-info-item4a', 'eupassq' ) ),
            'item4b' => wp_kses_post( __( 'results-info-item4b', 'eupassq' ) ),
            'item4c' => wp_kses_post( __( 'results-info-item4c', 'eupassq' ) ),
            'item5'  => wp_kses_post( __( 'results-info-item5', 'eupassq' ) ),
        ];

        $closing_message = [
            'text'   => wp_kses_post( __( 'closing-message-text', 'eupassq' ) ),
            'button' => esc_html__( 'closing-message-button', 'eupassq' ),
        ];


        $response = [
            'exist' => $quiz_setting['exist'],
            'header'            => $header,
            'section_overview'  => $section_overview,
            'recording_demo'    => $recording_demo,
            'results_info'      => $results_info,
            'closing_message'   => $closing_message,
            'link'  => $quiz_setting['link'],
        ];
       
        wp_send_json_success($response);
    }



}
