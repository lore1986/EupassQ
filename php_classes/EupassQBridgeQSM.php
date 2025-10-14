<?php 

namespace EupassQ\PhpClasses;

class EupassQBridgeQSM {

    public function __construct() {
        
        add_action('qsm_quiz_submitted', [$this, 'EupassQ_intercept_QSM_Submit'], 100, 4);
        add_action('qsm_saved_the_quiz', [$this, 'EupassQ_intercept_QSM_Save'], 10, 4);
        add_action('qsm_after_results_page', [$this, 'EupassQ_intercept_inject'], 1);
        // add_action('qsm_action_before_page', [$this, 'EupassQ_Anus_QSM'], 1000);
        add_filter('qsm_results_page_content', [$this, 'eupassq_retake_button_filter'], 10, 2);
        add_action('wp_ajax_nopriv_eupassq_anubi_qsm', array($this, 'EupassQ_T_QSM'));
        add_action('wp_ajax_eupassq_anubi_qsm', array($this, 'EupassQ_T_QSM'));
        //qmn_process_quiz
    }

    function eupassq_retake_button_filter($content, $quiz_id) {
        // Desired URL for retake
        $custom_url = apply_filters('eupassq_retake_button_url', get_permalink($quiz_id));

        // Replace the existing retake button href
        $content = preg_replace(
            '/(<a[^>]+id="qsm_retake_button"[^>]+href=")[^"]+(")/',
            '$1' . esc_url($custom_url) . '$2',
            $content
        );

        return $content;
    }

    public function EupassQ_T_QSM()
    {
        $s = 1;

        $quiz_id = isset($_POST['id']) ? intval($_POST['quiz_id']) : 0;

        // Build a response object compatible with QSM expectations
        $response = [
            'idq' => $_POST['id'],
            'uidq' => $_POST['uuid']
        ];

        // Output JSON directly (QSM expects raw JSON, not wrapped in success:true)
        wp_send_json_success($response);
    }

    public function EupassQ_intercept_inject()
    {
        //qmn_quiz_id
        $s= 0;
        $_REQUEST['action'] = 'eupass_te_incula';
         ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('qsm_retake_button');
            console.log(btn)
            // console.log('called  ' + Date.UTC());
            // if (btn) {
            //     btn.textContent = 'Continue';
            //     btn.onclick = function() {
            //         window.location.href = "<?php echo esc_url( home_url('/dashboard/') ); ?>";
            //     };
            // }
        });
        </script>
        <?php
        
    }

    public function EupassQ_intercept_QSM_Save( $quiz_id, $entry_id, $quiz_options, $results ) {

        $S = 0;
        // // Example: check quiz ID or result before redirect
        // if ( $quiz_id == 5 ) {
        //     $redirect_url = home_url('/'); // or a custom URL
        //     wp_safe_redirect( $redirect_url );
        //     exit; // 🔴 required after redirect to stop page processing
        // }
        
       

        
    }


    function EupassQ_intercept_QSM_Submit( $results_array, $results_id, $qmn_quiz_options, $qmn_array_for_variables ) {
        global $wpdb;

        //qmn_array_for_variables quiz_id / quiz_name / user_email / user_id / timer / time taken H:m:s m/d/Y
        // total_points / total_score / total_correct / total_questions / results_id / result_unique_id

        //results_id is key of  wp_mlw_results table

        //qmn_quiz_options // submit_button_text

        // $table_sessions = $wpdb->prefix . 'qsm_sessions';
        $table_results = $wpdb->prefix . 'mlw_results';
        $table_quizzes  = $wpdb->prefix . 'qsm_quizzes';
        
        
        // 
        // $redirect_url = home_url('/'); // or a custom URL
        //     wp_safe_redirect( $redirect_url );
        //     exit; // 🔴 required after redirect to stop page processing

        // $session = $wpdb->get_row(
        //     $wpdb->prepare(
        //         "SELECT user, points as score, correct, total_questions 
        //         FROM $table_sessions 
        //         WHERE session_id = %d",
        //         $session_id
        //     )
        // );

        // $quiz_title = $wpdb->get_var(
        //     $wpdb->prepare(
        //         "SELECT quiz_name 
        //         FROM $table_quizzes 
        //         WHERE quiz_id = %d",
        //         $quiz_id
        //     )
        // );

        // if ( $session && $quiz_title ) {
        //     $student_id = $session->user;
        //     $score = $session->score;


        //     // $wpdb->insert(
        //     //     $wpdb->prefix . 'myplugin_student_results',
        //     //     [
        //     //         'student_id' => $student_id,
        //     //         'quiz_id'    => $quiz_id,
        //     //         'quiz_title' => $quiz_title,
        //     //         'score'      => $score,
        //     //         'date'       => current_time('mysql'),
        //     //     ]
        //     // );

        //     // // Optional: Log for testing
        //     // error_log("QSM Result saved: $student_id | $quiz_title ($quiz_id) | Score: $score");
        // }
    }


}
