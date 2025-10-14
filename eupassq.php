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
include_once(plugin_dir_path(__DIR__) . 'eupassq/php_classes/EupassQNonce.php');
include_once(plugin_dir_path(__DIR__) . 'eupassq/php_classes/EupassQTemplater.php');
include_once(plugin_dir_path(__DIR__) . 'eupassq/php_classes/EupassQBridgeQSM.php');


register_activation_hook( __FILE__, 'eupassqtemplate_clear_template_cache' );
register_deactivation_hook( __FILE__, 'eupassqtemplate_clear_template_cache');

use EupassQ\PhpClasses\EupassqAdminInterface;
use EupassQ\PhpClasses\EupassqDatabase;
use EupassQ\PhpClasses\EupassQ_Nonce;
use EupassQ\PhpClasses\EupassQNonce;
use EupassQ\PhpClasses\EupassqQuestionManager;
use EupassQ\PhpClasses\EupassQTemplate;
use EupassQ\PhpClasses\EupassQBridgeQSM;



$dbGb = new EupassqDatabase();
$nc = new EupassQNonce();
$templater = new EupassQTemplate($dbGb);
$admin_interface = new EupassqAdminInterface($dbGb, $nc);
$question_manager = new EupassqQuestionManager($dbGb, $nc);
$bridge = new EupassQBridgeQSM();



function eupassqtemplate_clear_template_cache() {
    $cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );
    wp_cache_delete( $cache_key, 'themes' );
}

add_action( 'admin_enqueue_scripts', [$admin_interface, 'eupassq_admin_enqueue_scripts']); 
add_action( 'wp_enqueue_scripts', [$question_manager, 'Eupassq_EnqueueQuestionScripts']); 
add_action( 'wp_enqueue_scripts', 'Eupassq_EnqueueSharedScripts');

function Eupassq_EnqueueSharedScripts()
{
    wp_enqueue_script('shared_script',  plugin_dir_url(__FILE__) . 'assets/js/shared.js', array('jquery'), null, false );

    wp_localize_script('shared_script', 'EupQ_Ajax_Obj', array(
        'ajaxUrl' => admin_url('admin-ajax.php')
    ));
}






