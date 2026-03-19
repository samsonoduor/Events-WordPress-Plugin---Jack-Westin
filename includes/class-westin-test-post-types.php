<?php

namespace WestinTest\Events;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Post_Types {
    public const POST_TYPE          = 'westin_event';
    public const TAXONOMY_TYPE      = 'westin_event_type';
    public const TAXONOMY_AUDIENCE  = 'westin_event_audience';
    public const TAXONOMY_SERIES    = 'westin_event_series';

    public static function init(): void {
        add_action( 'init', array( __CLASS__, 'register' ) );
    }

    public static function register(): void {
        register_post_type(
            self::POST_TYPE,
            array(
                'labels' => array(
                    'name'                  => __( 'Events', 'westin-test-events' ),
                    'singular_name'         => __( 'Event', 'westin-test-events' ),
                    'menu_name'             => __( 'Events', 'westin-test-events' ),
                    'name_admin_bar'        => __( 'Event', 'westin-test-events' ),
                    'add_new'               => __( 'Add New', 'westin-test-events' ),
                    'add_new_item'          => __( 'Add New Event', 'westin-test-events' ),
                    'edit_item'             => __( 'Edit Event', 'westin-test-events' ),
                    'new_item'              => __( 'New Event', 'westin-test-events' ),
                    'view_item'             => __( 'View Event', 'westin-test-events' ),
                    'view_items'            => __( 'View Events', 'westin-test-events' ),
                    'search_items'          => __( 'Search Events', 'westin-test-events' ),
                    'not_found'             => __( 'No events found.', 'westin-test-events' ),
                    'not_found_in_trash'    => __( 'No events found in Trash.', 'westin-test-events' ),
                    'all_items'             => __( 'All Events', 'westin-test-events' ),
                    'archives'              => __( 'Event Archives', 'westin-test-events' ),
                    'attributes'            => __( 'Event Attributes', 'westin-test-events' ),
                    'insert_into_item'      => __( 'Insert into event', 'westin-test-events' ),
                    'uploaded_to_this_item' => __( 'Uploaded to this event', 'westin-test-events' ),
                ),
                'description'        => __( 'A dedicated post type for live, virtual, and evergreen event content.', 'westin-test-events' ),
                'public'             => true,
                'has_archive'        => true,
                'show_in_rest'       => true,
                'rest_base'          => 'events',
                'menu_icon'          => 'dashicons-calendar-alt',
                'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
                'rewrite'            => array( 'slug' => 'events', 'with_front' => false ),
                'publicly_queryable' => true,
                'show_in_nav_menus'  => true,
                'capability_type'    => 'post',
                'map_meta_cap'       => true,
            )
        );

        self::register_taxonomies();
    }

    private static function register_taxonomies(): void {
        register_taxonomy(
            self::TAXONOMY_TYPE,
            array( self::POST_TYPE ),
            array(
                'labels' => array(
                    'name'              => __( 'Event Types', 'westin-test-events' ),
                    'singular_name'     => __( 'Event Type', 'westin-test-events' ),
                    'search_items'      => __( 'Search Event Types', 'westin-test-events' ),
                    'all_items'         => __( 'All Event Types', 'westin-test-events' ),
                    'parent_item'       => __( 'Parent Event Type', 'westin-test-events' ),
                    'parent_item_colon' => __( 'Parent Event Type:', 'westin-test-events' ),
                    'edit_item'         => __( 'Edit Event Type', 'westin-test-events' ),
                    'update_item'       => __( 'Update Event Type', 'westin-test-events' ),
                    'add_new_item'      => __( 'Add New Event Type', 'westin-test-events' ),
                    'new_item_name'     => __( 'New Event Type Name', 'westin-test-events' ),
                    'menu_name'         => __( 'Event Types', 'westin-test-events' ),
                ),
                'public'            => true,
                'hierarchical'      => true,
                'show_admin_column' => true,
                'show_in_rest'      => true,
                'rewrite'           => array( 'slug' => 'event-type', 'with_front' => false ),
            )
        );

        register_taxonomy(
            self::TAXONOMY_AUDIENCE,
            array( self::POST_TYPE ),
            array(
                'labels' => array(
                    'name'                       => __( 'Audiences', 'westin-test-events' ),
                    'singular_name'              => __( 'Audience', 'westin-test-events' ),
                    'search_items'               => __( 'Search Audiences', 'westin-test-events' ),
                    'all_items'                  => __( 'All Audiences', 'westin-test-events' ),
                    'edit_item'                  => __( 'Edit Audience', 'westin-test-events' ),
                    'update_item'                => __( 'Update Audience', 'westin-test-events' ),
                    'add_new_item'               => __( 'Add New Audience', 'westin-test-events' ),
                    'new_item_name'              => __( 'New Audience Name', 'westin-test-events' ),
                    'menu_name'                  => __( 'Audiences', 'westin-test-events' ),
                ),
                'public'            => true,
                'hierarchical'      => false,
                'show_admin_column' => true,
                'show_in_rest'      => true,
                'rewrite'           => array( 'slug' => 'event-audience', 'with_front' => false ),
            )
        );

        register_taxonomy(
            self::TAXONOMY_SERIES,
            array( self::POST_TYPE ),
            array(
                'labels' => array(
                    'name'              => __( 'Event Series', 'westin-test-events' ),
                    'singular_name'     => __( 'Event Series', 'westin-test-events' ),
                    'search_items'      => __( 'Search Event Series', 'westin-test-events' ),
                    'all_items'         => __( 'All Event Series', 'westin-test-events' ),
                    'edit_item'         => __( 'Edit Event Series', 'westin-test-events' ),
                    'update_item'       => __( 'Update Event Series', 'westin-test-events' ),
                    'add_new_item'      => __( 'Add New Event Series', 'westin-test-events' ),
                    'new_item_name'     => __( 'New Event Series Name', 'westin-test-events' ),
                    'menu_name'         => __( 'Event Series', 'westin-test-events' ),
                ),
                'public'            => true,
                'hierarchical'      => true,
                'show_admin_column' => true,
                'show_in_rest'      => true,
                'rewrite'           => array( 'slug' => 'event-series', 'with_front' => false ),
            )
        );
    }

    public static function sanitize_date( $value ): string {
        $value = is_string( $value ) ? trim( $value ) : '';

        if ( '' === $value ) {
            return '';
        }

        return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
    }

    public static function sanitize_time( $value ): string {
        $value = is_string( $value ) ? trim( $value ) : '';

        if ( '' === $value ) {
            return '';
        }

        return preg_match( '/^\d{2}:\d{2}$/', $value ) ? $value : '';
    }

}
