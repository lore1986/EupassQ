<?php
// Prevent direct access
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete all plugin data (questions)
$questions = get_posts( array(
    'post_type'   => 'eupassq_question',
    'numberposts' => -1,
    'post_status' => 'any',
) );

foreach ( $questions as $q ) {
    wp_delete_post( $q->ID, true );
}
