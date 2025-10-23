
<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

$custom_tables = array(
    'eupassq_tmp',
    'eupass_set',
    'eupqs',
);

function eupassq_delete_plugin_data_for_blog( $blog_id, $custom_tables ) {
    global $wpdb;

    switch_to_blog( $blog_id );


    $questions = get_posts( array(
        'post_type'   => 'eupassq',
        'numberposts' => -1,
        'post_status' => 'any',
    ) );

    foreach ( $questions as $q ) {
        wp_delete_post( $q->ID, true );
    }

    foreach ( $custom_tables as $table ) {
        $table_name = $wpdb->prefix . $table;
        $wpdb->query( "DROP TABLE IF EXISTS `$table_name`" );
    }

    restore_current_blog();
}


if ( is_multisite() ) {
    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

    foreach ( $blog_ids as $blog_id ) {
        eupassq_delete_plugin_data_for_blog( $blog_id, $custom_tables );
    }
} else {
    eupassq_delete_plugin_data_for_blog( get_current_blog_id(), $custom_tables );
}
