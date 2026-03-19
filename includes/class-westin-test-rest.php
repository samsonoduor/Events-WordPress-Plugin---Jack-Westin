<?php

namespace WestinTest\Events;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rest {
    public static function init(): void {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes(): void {
        register_rest_route(
            'westin-test/v1',
            '/rsvp',
            array(
                'methods'             => 'POST',
                'callback'            => array( __CLASS__, 'rsvp' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'event_id' => array(
                        'required'          => true,
                        'sanitize_callback' => 'absint',
                    ),
                    'name' => array(
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'email' => array(
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_email',
                    ),
                ),
            )
        );
    }

    public static function rsvp( \WP_REST_Request $request ): \WP_REST_Response {
        $result = RSVP::store_rsvp(
            absint( $request->get_param( 'event_id' ) ),
            (string) $request->get_param( 'name' ),
            (string) $request->get_param( 'email' )
        );

        if ( is_wp_error( $result ) ) {
            return new \WP_REST_Response(
                array(
                    'success' => false,
                    'message' => $result->get_error_message(),
                ),
                400
            );
        }

        return new \WP_REST_Response(
            array(
                'success' => true,
                'message' => __( 'RSVP saved.', 'westin-test-events' ),
            ),
            200
        );
    }
}
