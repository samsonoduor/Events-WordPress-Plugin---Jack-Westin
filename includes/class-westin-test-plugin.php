<?php

namespace WestinTest\Events;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once WESTIN_TEST_EVENTS_PATH . 'includes/class-westin-test-cache.php';
require_once WESTIN_TEST_EVENTS_PATH . 'includes/class-westin-test-post-types.php';
require_once WESTIN_TEST_EVENTS_PATH . 'includes/class-westin-test-meta-boxes.php';
require_once WESTIN_TEST_EVENTS_PATH . 'includes/class-westin-test-admin.php';
require_once WESTIN_TEST_EVENTS_PATH . 'includes/class-westin-test-frontend.php';
require_once WESTIN_TEST_EVENTS_PATH . 'includes/class-westin-test-rsvp.php';
require_once WESTIN_TEST_EVENTS_PATH . 'includes/class-westin-test-notifications.php';
require_once WESTIN_TEST_EVENTS_PATH . 'includes/class-westin-test-rest.php';
require_once WESTIN_TEST_EVENTS_PATH . 'includes/class-westin-test-cli.php';
require_once WESTIN_TEST_EVENTS_PATH . 'includes/class-westin-test-demo-data.php';

class Plugin {
    private static ?Plugin $instance = null;

    public static function instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        // Boot the plugin on plugins_loaded so post types are registered before the admin menu is built.
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_action( 'plugins_loaded', array( $this, 'bootstrap' ), 5 );
        add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
        add_action( 'save_post_' . Post_Types::POST_TYPE, array( __CLASS__, 'bust_cache' ), 20 );
        add_action( 'deleted_post', array( __CLASS__, 'maybe_bust_cache_on_delete' ) );
    }

    public function load_textdomain(): void {
        load_plugin_textdomain(
            'westin-test-events',
            false,
            dirname( plugin_basename( WESTIN_TEST_EVENTS_FILE ) ) . '/languages'
        );
    }

    public function bootstrap(): void {
        Post_Types::init();
        Meta_Boxes::init();
        Admin::init();
        Frontend::init();
        RSVP::init();
        Notifications::init();
        Rest::init();
        CLI::init();
        Demo_Data::init();
    }

    public function register_assets(): void {
        wp_register_style(
            'westin-test-events',
            WESTIN_TEST_EVENTS_URL . 'assets/css/events.css',
            array(),
            WESTIN_TEST_EVENTS_VERSION
        );

        wp_register_script(
            'westin-test-events',
            WESTIN_TEST_EVENTS_URL . 'assets/js/events.js',
            array( 'jquery' ),
            WESTIN_TEST_EVENTS_VERSION,
            true
        );

        wp_localize_script(
            'westin-test-events',
            'westinTestEvents',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'westin_test_event_rsvp' ),
                'i18n'    => array(
                    'success' => __( 'Thanks, your RSVP is on the list.', 'westin-test-events' ),
                    'error'   => __( 'We could not process the RSVP request right now.', 'westin-test-events' ),
                ),
            )
        );
    }

    public static function activate(): void {
        Post_Types::register();
        Cache::set_version( time() );
        Demo_Data::activate_notice();
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    public static function bust_cache(): void {
        Cache::bump();
    }

    public static function maybe_bust_cache_on_delete( int $post_id ): void {
        if ( Post_Types::POST_TYPE === get_post_type( $post_id ) ) {
            Cache::bump();
        }
    }
}
