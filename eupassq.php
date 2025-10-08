<?php

/**
 * Plugin Name: EupassQ
 * Plugin URI:  ---
 * Description: A simple quiz plugin that lets admins create questions and serve them to users.
 * Version:     1.0.0
 * Author:      Jep
 * Author URI:  https://example.com
 * License:     GPLv2 or later
 * Text Domain: eupassq
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once(plugin_dir_path(__DIR__) . 'eupassq/vendor/autoload.php'); 
include_once(plugin_dir_path(__DIR__) . 'eupassq/php_classes/EupassqAdminInterface.php');
include_once(plugin_dir_path(__DIR__) . 'eupassq/php_classes/EupassqDatabase.php');
include_once(plugin_dir_path(__DIR__) . 'eupassq/php_classes/EupassqQuestionManager.php');
include_once(plugin_dir_path(__DIR__) . 'eupassq/php_classes/EupassQGrader.php');

use EupassQ\PhpClasses\EupassqAdminInterface;
use EupassQ\PhpClasses\EupassqDatabase;
use EupassQ\PhpClasses\EupassQGrader;
use EupassQ\PhpClasses\EupassqQuestionManager;

$dbGb = new EupassqDatabase();
$grader = new EupassQGrader();
$admin_interface = new EupassqAdminInterface($dbGb);
$question_manager = new EupassqQuestionManager($dbGb, $grader);


add_action( 'admin_enqueue_scripts', [$admin_interface, 'eupassq_admin_enqueue_scripts']); 
add_action( 'wp_enqueue_scripts', [$question_manager, 'Eupassq_EnqueueQuestionScripts']); 





