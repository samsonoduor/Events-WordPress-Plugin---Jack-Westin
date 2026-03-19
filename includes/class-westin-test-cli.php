<?php

namespace WestinTest\Events;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CLI {
    public static function init(): void {
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            \WP_CLI::add_command( 'westin-test seed-events', array( __CLASS__, 'seed' ) );
        }
    }

    /**
     * Create sample events for quick testing.
     *
     * I usually like leaving a simple seed command in custom plugins.
     * It saves a few minutes every time QA needs a clean demo set.
     *
     * ## OPTIONS
     *
     * [--count=<count>]
     * : Number of events to create. Defaults to 5.
     *
     * ## EXAMPLES
     *
     *     wp westin-test seed-events --count=5
     */
    public static function seed( array $args, array $assoc_args ): void {
        $count = isset( $assoc_args['count'] ) ? max( 1, absint( $assoc_args['count'] ) ) : 5;
        $types     = array( 'conference', 'webinar', 'community', 'training' );
        $audiences = array( 'members', 'public', 'students', 'partners' );
        $series    = array( 'spring-forum', 'summer-sessions', 'fall-labs' );

        foreach ( $types as $type ) {
            if ( ! term_exists( $type, Post_Types::TAXONOMY_TYPE ) ) {
                wp_insert_term( ucfirst( $type ), Post_Types::TAXONOMY_TYPE, array( 'slug' => $type ) );
            }
        }

        foreach ( $audiences as $audience ) {
            if ( ! term_exists( $audience, Post_Types::TAXONOMY_AUDIENCE ) ) {
                wp_insert_term( ucwords( str_replace( '-', ' ', $audience ) ), Post_Types::TAXONOMY_AUDIENCE, array( 'slug' => $audience ) );
            }
        }

        foreach ( $series as $series_item ) {
            if ( ! term_exists( $series_item, Post_Types::TAXONOMY_SERIES ) ) {
                wp_insert_term( ucwords( str_replace( '-', ' ', $series_item ) ), Post_Types::TAXONOMY_SERIES, array( 'slug' => $series_item ) );
            }
        }

        for ( $i = 1; $i <= $count; $i++ ) {
            $post_id = wp_insert_post(
                array(
                    'post_type'    => Post_Types::POST_TYPE,
                    'post_status'  => 'publish',
                    'post_title'   => sprintf( 'Sample Event %d', $i ),
                    'post_content' => 'This sample record was generated from WP-CLI so the archive, filters, and RSVP flow can be tested quickly.',
                    'post_excerpt' => 'Quick demo content for the event archive.',
                ),
                true
            );

            if ( is_wp_error( $post_id ) ) {
                \WP_CLI::warning( $post_id->get_error_message() );
                continue;
            }

            update_post_meta( $post_id, '_westin_event_date', gmdate( 'Y-m-d', strtotime( '+' . $i . ' days' ) ) );
            update_post_meta( $post_id, '_westin_event_start_time', sprintf( '%02d:00', rand( 9, 18 ) ) );
            update_post_meta( $post_id, '_westin_event_location', 'Sample Location ' . $i );
            update_post_meta( $post_id, '_westin_event_address', sprintf( '%d Sample Street\nDemo City, ST 0000%d', $i * 10, $i ) );
            update_post_meta( $post_id, '_westin_event_speaker', 'Speaker ' . $i );
            update_post_meta( $post_id, '_westin_event_capacity', rand( 25, 180 ) );
            update_post_meta( $post_id, '_westin_event_rsvp_count', 0 );
            wp_set_object_terms( $post_id, $types[ array_rand( $types ) ], Post_Types::TAXONOMY_TYPE );
            wp_set_object_terms( $post_id, $audiences[ array_rand( $audiences ) ], Post_Types::TAXONOMY_AUDIENCE );
            wp_set_object_terms( $post_id, $series[ array_rand( $series ) ], Post_Types::TAXONOMY_SERIES );
        }

        Cache::bump();
        \WP_CLI::success( sprintf( 'Created %d sample events.', $count ) );
    }
}
