<?php
namespace EupassQ\PhpClasses;

class EupassQTemplate
{
    private $dbGb, $qM;

    public function __construct($dbGb, $_qM)
    {
        $this->dbGb = $dbGb;
        $this->qM = $_qM;

        add_action('init', [$this, 'EupassQ_register_rewrite_rule']);
        add_filter('query_vars', [$this, 'EupassQ_register_query_var']);
        add_action('template_redirect', [$this, 'EupassQ_load_results_template']);
        add_action('template_redirect', [$this, 'EupassQ_load_quiz_template']);
    }

    /**
     * Register custom rewrite rule
     * /results/123  →  index.php?results_id=123
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
    }


    /**
     * Make WordPress aware of our query variable
     */
    public function EupassQ_register_query_var($vars)
    {
        $vars[] = 'results_id';
        $vars[] = 'qsmres';
        $vars[] = 'uuid';

        return $vars;
    }

    /**
     * Handle loading of the results template
     */
    public function EupassQ_load_results_template()
    {
        $results_id = get_query_var('results_id');
        $qsm_id = get_query_var('qsmres');

        if (empty($results_id) || empty($qsm_id)) {
            return;
        }

        $template_path = plugin_dir_path(__FILE__) . '../assets/templates/results-template.php';

        if (!file_exists($template_path)) {
            wp_die('Template not found: ' . esc_html($template_path));
        }

        get_header();
        include $template_path;
        get_footer();

        exit;
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
}
