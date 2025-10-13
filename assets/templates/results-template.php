<?php 
/* Template Name: Results */

include_once(dirname(plugin_dir_path(__DIR__)) . '/php_classes/EupassQGrader.php');
use EupassQ\PhpClasses\EupassQGrader;
$grader = new EupassQGrader();

get_header(); 


?>


<h1>Hello</h1>


<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <?php
        global $wpdb;
        $result_code = get_query_var( 'results_id' );
        $eupassq_tmp_quiz = $wpdb->prefix . 'eupassq_tmp';

        $answers =  $wpdb->get_results($wpdb->prepare("SELECT * FROM $eupassq_tmp_quiz 
            WHERE euqtid = %s", $result_code), ARRAY_A);

        $test = $grader->EupassQ_Handle_Submissions($answers);
        

        echo '<pre>' . print_r($test, true) . '</pre>';

        if ( $result_code ) {
            // IMPORTANT: Always escape data from the URL before displaying it!
            echo '<h1>Displaying data for: ' . esc_html( $result_code ) . '</h1>';

            // Now you can use the $result_code variable to query your database,
            // call an API, or perform any other action.

        } else {
            // Handle the case where someone visits /results/ without a code
            echo '<h1>Please provide a result code.</h1>';
        }
        ?>

    </main></div><?php get_footer();