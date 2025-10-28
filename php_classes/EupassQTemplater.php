<?php
namespace EupassQ\PhpClasses;



class EupassQTemplate
{
    private $dbGb, $qM, $grader;

    public function __construct($dbGb, $_qM, $_grad)
    {
        $this->dbGb = $dbGb;
        $this->qM = $_qM;
        $this->grader = $_grad;

        add_action('init', [$this, 'EupassQ_register_rewrite_rule']);
        add_filter('query_vars', [$this, 'EupassQ_register_query_var']);
        add_action('template_redirect', [$this, 'EupassQ_load_results_template']);
        add_action('template_redirect', [$this, 'EupassQ_load_quiz_template']);
        add_action('wp_mail_failed', function($error){
            error_log(print_r($error, true));
            $s= 0;
        });
    }

    /**
     * Register custom rewrite rule
     */
    public function EupassQ_register_rewrite_rule()
    {
        add_rewrite_rule(
            '^results/([a-z0-9]+)/([a-z0-9-]+)?$',
            'index.php?results_id=$matches[1]&qsmres=$matches[2]',
            'top'
        );

        add_rewrite_rule(
            '^europassQ/([a-z0-9]+)/?$',
            'index.php?uuid=$matches[1]',
            'top'
        );

        add_rewrite_rule(
            '^results-view/([a-z0-9]+)/([a-z0-9-]+)/?$',
            'index.php?results_view_id=$matches[1]&qsmres=$matches[2]',
            'top'
        );


    }


    /**
     * Make WordPress aware of our query variable
     */
    public function EupassQ_register_query_var($vars)
    {
        $vars[] = 'results_id';
        $vars[] = 'qsmres';
        $vars[] = 'uuid';
        $vars[] = 'results_view_id';
        return $vars;
    }


    
    /**
     * Handle loading of the results template
     */
    public function EupassQ_load_results_template()
    {
        $results_id = get_query_var('results_id');
        $qsm_id = get_query_var('qsmres');


        if (!empty($results_id) && !empty($qsm_id)) {

            $cached_results = get_transient('eupassq_result_' . $results_id);

            if ($cached_results === false) {

                $results = $this->grader->EupassQ_Handle_Submissions($results_id, $qsm_id);
                set_transient('eupassq_result_' . $results_id, $results, 600);
                $this->grader->EupassQ_Delete_User_Results($results_id);

                $redirect_url = home_url('/results-view/' . $results_id . '/' . $qsm_id . '/');
                wp_redirect($redirect_url);
                exit;

            } else {
  
                $redirect_url = home_url('/results-view/' . $results_id . '/' . $qsm_id . '/');
                wp_redirect($redirect_url);
                exit;
            }
        }


        $results_view_id = get_query_var('results_view_id');

        if (!empty($results_view_id) && !empty($qsm_id)) {
            
            $results = get_transient('eupassq_result_' . $results_view_id);

            if ($results === false) {

                //here
                $template_path = plugin_dir_path(__FILE__) . '../assets/templates/result-expired-template.php';

                if (!file_exists($template_path)) {
                    wp_die('Template not found: ' . esc_html($template_path));
                }


                include $template_path;

                exit;
                
            }


            $GLOBALS['eupassq_cached_results'] = $results;

            //send email to user
            $user_email = $this->dbGb->EupassQ_Query_QSM_Results($qsm_id)->email;
            $s = $this->EupassQ_send_results_email( $user_email, $results, $results_view_id );

            $template_path = plugin_dir_path(__FILE__) . '../assets/templates/results-view-template.php';
            if (!file_exists($template_path)) {
                wp_die('Template not found: ' . esc_html($template_path));
            }

            get_header();
            include $template_path;
            get_footer();
            exit;
        }

        return;
    }


    public function EupassQ_load_quiz_template()
    {
        $uuid = get_query_var('uuid');
        
        if (empty($uuid)) {
            return;
        }


        $quiz_setting = $this->dbGb->EupassQ_Set_Quiz_Settings($uuid);

        $quiz_form_data = $this->qM->EupassqQuestion_Generate_Quiz_Form($quiz_setting);

        $template_path = plugin_dir_path(__FILE__) . '../assets/templates/quiz-template.php';

        if (!file_exists($template_path)) {
            wp_die('Template not found: ' . esc_html($template_path));
        }

        $GLOBALS['user_info'] = $quiz_form_data['user_info'];
        $GLOBALS['qsm_unique_id'] = $quiz_form_data['qsm_unique_id'];
        $GLOBALS['question_pool'] = $quiz_form_data['question_pool'];

        get_header();
        include $template_path;
        get_footer();

        exit;
    }

    public function EupassQ_send_results_email( $to, $results, $results_view_id = '' ) {

        if ( empty( $to ) || empty( $results ) || ! is_email( $to ) ) {
            return false;
        }

        if ( ! session_id() ) {
            session_start();
        }

        // Prevent duplicate sends in the same session
        if ( isset( $_SESSION['eupassq_email_sent_' . $results_view_id] ) ) {
            return false;
        }

        $subject = 'Your EupassQ Test Results';
        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

        ob_start();
        ?>
        <div style="font-family: Arial, sans-serif; color:#333;">
            <h2 style="color:#2a7ae2;">Your EupassQ Test Results Summary</h2>
            <p><strong>Overall Score:</strong> <?php echo esc_html( $results['user_score'] ); ?></p>
            <p><strong>Percentage:</strong> <?php echo esc_html( $results['user_percentage'] ); ?>%</p>

            <hr style="border:0; border-top:1px solid #ddd; margin:20px 0;">
            <h3>Multiple Choice Questions</h3>
            <p><strong><?php echo esc_html( $results['qsm']['partial'] ); ?> / <?php echo esc_html( $results['qsm']['total'] ); ?></strong></p>
            <table style="width:100%; border-collapse:collapse;" border="1" cellpadding="6">
                <thead>
                    <tr style="background:#f4f4f4;">
                        <th>#</th>
                        <th>Question</th>
                        <th>Your Answer</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $results['qsm']['qanda'] as $i => $item ): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo wp_kses_post( $item['question_text'] ); ?></td>
                            <td><?php echo esc_html( $item['question_answer'] ); ?></td>
                            <td style="text-align:center;">
                                <?php echo ($item['iscorrect'] === 'correct')
                                    ? '<span style="color:green;">✔</span>'
                                    : '<span style="color:red;">✗</span>'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <hr style="border:0; border-top:1px solid #ddd; margin:20px 0;">
            <h3>Written Task</h3>
            <?php foreach ( $results['eupassQ_text']['qanda'] as $t ): ?>
                <div style="margin-bottom:15px;">
                    <p><strong>Question:</strong> <?php echo wp_kses_post( $t['question_text'] ); ?></p>
                    <p><strong>Feedback:</strong> <?php echo esc_html( $t['feedback'] ); ?></p>
                    <p><strong>Score:</strong> <?php echo esc_html( $t['punteggio_totale'] ); ?></p>
                </div>
            <?php endforeach; ?>

            <hr style="border:0; border-top:1px solid #ddd; margin:20px 0;">
            <h3>Oral Task</h3>
            <?php foreach ( $results['eupassQ_audio']['qanda'] as $a ): ?>
                <div style="margin-bottom:15px;">
                    <p><strong>Question:</strong> <?php echo wp_kses_post( $a['question_text'] ); ?></p>
                    <p><strong>Feedback:</strong> <?php echo esc_html( $a['feedback'] ); ?></p>
                    <p><strong>Score:</strong> <?php echo esc_html( $a['punteggio_totale'] ?? '' ); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        $message = ob_get_clean();

        // Send the email
        $sent = wp_mail( $to, $subject, $message, $headers );

        if ( $sent ) {
            $_SESSION['eupassq_email_sent_' . $results_view_id] = true;
        }

        return $sent;

    }
}
