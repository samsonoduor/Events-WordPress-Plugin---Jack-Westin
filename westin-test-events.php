<?php
/**
 * Plugin Name: Westin Test Events
 * Plugin URI: https://github.com
 * Description: A custom events plugin with event types, admin tools, front-end templates, filtering, RSVP handling, REST support, WP-CLI seeding, and test scaffolding.
 * Version: 1.4.3
 * Author: Samson Oduor
 * Author URI: https://samsonoduor.com/
 * Text Domain: westin-test-events
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WESTIN_TEST_EVENTS_VERSION', '1.4.3' );
define( 'WESTIN_TEST_EVENTS_FILE', __FILE__ );
define( 'WESTIN_TEST_EVENTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'WESTIN_TEST_EVENTS_URL', plugin_dir_url( __FILE__ ) );

require_once WESTIN_TEST_EVENTS_PATH . 'includes/class-westin-test-plugin.php';

function westin_test_events() {
    return \WestinTest\Events\Plugin::instance();
}

register_activation_hook( __FILE__, array( '\\WestinTest\\Events\\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\\WestinTest\\Events\\Plugin', 'deactivate' ) );

westin_test_events();
