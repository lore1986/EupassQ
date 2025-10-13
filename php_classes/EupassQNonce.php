<?php


namespace EupassQ\PhpClasses;

if ( ! defined( 'ABSPATH' ) ) exit;


class EupassQNonce {

    const QUIZ_SUBMIT = 'eupassq_quiz_submit';

    /**
     * Create a nonce for a specific action
     */
    public static function create( string $action ) {
        return wp_create_nonce( $action );
    }

    /**
     * Output a hidden nonce field in a form
     */
    public static function field( string $action, string $field_name = 'security' ) {
        printf(
            '<input type="hidden" name="%s" value="%s">',
            esc_attr( $field_name ),
            esc_attr( self::create( $action ) )
        );
    }

    /**
     * Verify a nonce from request data
     */
    public static function verify( string $action, $nonce = null, string $field_name = 'security' ) {
        if ( ! $nonce && isset( $_REQUEST[ $field_name ] ) ) {
            $nonce = $_REQUEST[ $field_name ];
        }
        return wp_verify_nonce( $nonce, $action );
    }

    /**
     * Die (or JSON error) if nonce invalid
     */
    public static function die_if_invalid( string $action, string $field_name = 'security' ) {
        if ( ! self::verify( $action, null, $field_name ) ) {
            wp_send_json_error(
                [ 'message' => __( 'Security check failed', 'eupassq' ) ],
                403
            );
        }
    }
}
