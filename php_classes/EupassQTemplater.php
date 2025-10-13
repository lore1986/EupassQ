<?php
namespace EupassQ\PhpClasses;

class EupassQTemplate
{
    private $dbGb;

    public function __construct($dbGb)
    {
        $this->dbGb = $dbGb;

        add_action('init', [$this, 'EupassQ_register_rewrite_rule']);
        add_filter('query_vars', [$this, 'EupassQ_register_query_var']);
        add_action('template_redirect', [$this, 'EupassQ_load_results_template']);
    }

    /**
     * Register custom rewrite rule
     * /results/123  →  index.php?results_id=123
     */
    public function EupassQ_register_rewrite_rule()
    {
        add_rewrite_rule(
            '^results/([a-z0-9]+)/?$',
            'index.php?results_id=$matches[1]',
            'top'
        );
    }


    /**
     * Make WordPress aware of our query variable
     */
    public function EupassQ_register_query_var($vars)
    {
        $vars[] = 'results_id';
        return $vars;
    }

    /**
     * Handle loading of the results template
     */
    public function EupassQ_load_results_template()
    {
        $results_id = get_query_var('results_id');

        if (empty($results_id)) {
            return;
        }

        $template_path = plugin_dir_path(__FILE__) . '../assets/templates/results-template.php';

        if (!file_exists($template_path)) {
            wp_die('Template not found: ' . esc_html($template_path));
        }


        
        // // Pass data to template (if using $GLOBALS)
        // $GLOBALS['eupassq_results'] = $data;
        // $GLOBALS['eupassq_db'] = $this->dbGb;
        // $GLOBALS['eupassq_results_id'] = $results_id;

        // Load header, template, and footer
        get_header();
        include $template_path;
        get_footer();

        // Stop further processing
        exit;
    }
}
