<?php

namespace WestinTest\Events;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Demo_Data {
    private const NOTICE_OPTION = 'westin_test_events_show_demo_notice';

    public static function init(): void {
        if ( ! is_admin() ) {
            return;
        }

        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
        add_action( 'admin_notices', array( __CLASS__, 'maybe_render_notice' ) );
        add_action( 'admin_post_westin_test_events_install_demo_data', array( __CLASS__, 'handle_install' ) );
        add_action( 'admin_post_westin_test_events_remove_demo_data', array( __CLASS__, 'handle_remove' ) );
        add_action( 'admin_post_westin_test_events_dismiss_demo_notice', array( __CLASS__, 'handle_dismiss_notice' ) );
    }

    public static function register_menu(): void {
        add_submenu_page(
            'edit.php?post_type=' . Post_Types::POST_TYPE,
            __( 'Demo Data', 'westin-test-events' ),
            __( 'Demo Data', 'westin-test-events' ),
            'edit_posts',
            'westin-test-demo-data',
            array( __CLASS__, 'render_page' )
        );
    }

    public static function activate_notice(): void {
        update_option( self::NOTICE_OPTION, 1, false );
    }

    public static function maybe_render_notice(): void {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        if ( ! get_option( self::NOTICE_OPTION ) ) {
            return;
        }

        if ( self::is_demo_data_installed() ) {
            update_option( self::NOTICE_OPTION, 0, false );
            return;
        }

        $install_link = wp_nonce_url(
            admin_url( 'admin-post.php?action=westin_test_events_install_demo_data' ),
            'westin_test_events_install_demo_data'
        );
        $dismiss_link = wp_nonce_url(
            admin_url( 'admin-post.php?action=westin_test_events_dismiss_demo_notice' ),
            'westin_test_events_dismiss_demo_notice'
        );
        ?>
        <div class="notice notice-info is-dismissible">
            <p><strong><?php esc_html_e( 'Westin Test Events is ready to go.', 'westin-test-events' ); ?></strong></p>
            <p><?php esc_html_e( 'Would you like to install the bundled demo events, taxonomies, and featured images so you can test the archive, block, shortcode, and RSVP flow right away?', 'westin-test-events' ); ?></p>
            <p>
                <a class="button button-primary" href="<?php echo esc_url( $install_link ); ?>"><?php esc_html_e( 'Install Demo Data', 'westin-test-events' ); ?></a>
                <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . Post_Types::POST_TYPE . '&page=westin-test-demo-data' ) ); ?>"><?php esc_html_e( 'Open Demo Data Screen', 'westin-test-events' ); ?></a>
                <a class="button button-link" href="<?php echo esc_url( $dismiss_link ); ?>"><?php esc_html_e( 'Dismiss', 'westin-test-events' ); ?></a>
            </p>
        </div>
        <?php
    }

    public static function render_page(): void {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage demo data.', 'westin-test-events' ) );
        }

        $status = isset( $_GET['westin_demo_status'] ) ? sanitize_key( wp_unslash( $_GET['westin_demo_status'] ) ) : '';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Demo Data', 'westin-test-events' ); ?></h1>
            <p><?php esc_html_e( 'Use this screen to install or remove the bundled sample events. The installer will create demo posts, assign taxonomies, set event meta, and attach the packaged demo images as featured images.', 'westin-test-events' ); ?></p>

            <?php if ( 'installed' === $status ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Demo data installed successfully.', 'westin-test-events' ); ?></p></div>
            <?php elseif ( 'removed' === $status ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Demo data removed successfully.', 'westin-test-events' ); ?></p></div>
            <?php elseif ( 'noop' === $status ) : ?>
                <div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'No demo records needed to be changed.', 'westin-test-events' ); ?></p></div>
            <?php endif; ?>

            <table class="widefat striped" style="max-width: 960px; margin-top: 20px;">
                <tbody>
                    <tr>
                        <td style="width: 240px;"><strong><?php esc_html_e( 'Current status', 'westin-test-events' ); ?></strong></td>
                        <td><?php echo esc_html( self::is_demo_data_installed() ? __( 'Demo data is installed.', 'westin-test-events' ) : __( 'Demo data is not installed yet.', 'westin-test-events' ) ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Bundled images', 'westin-test-events' ); ?></strong></td>
                        <td><?php esc_html_e( 'Five packaged demo images are included and linked to the five demo events as featured images.', 'westin-test-events' ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Safe reset', 'westin-test-events' ); ?></strong></td>
                        <td><?php esc_html_e( 'Remove Demo Data only targets records marked by the plugin as demo content, so your manually created events are left alone.', 'westin-test-events' ); ?></td>
                    </tr>
                </tbody>
            </table>

            <p style="margin-top: 24px; display: flex; gap: 12px; flex-wrap: wrap;">
                <a class="button button-primary button-hero" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=westin_test_events_install_demo_data' ), 'westin_test_events_install_demo_data' ) ); ?>"><?php esc_html_e( 'Install Demo Data', 'westin-test-events' ); ?></a>
                <a class="button button-secondary button-hero" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=westin_test_events_remove_demo_data' ), 'westin_test_events_remove_demo_data' ) ); ?>"><?php esc_html_e( 'Remove Demo Data', 'westin-test-events' ); ?></a>
            </p>
        </div>
        <?php
    }

    public static function handle_install(): void {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( esc_html__( 'You do not have permission to install demo data.', 'westin-test-events' ) );
        }

        check_admin_referer( 'westin_test_events_install_demo_data' );

        self::install();
        update_option( self::NOTICE_OPTION, 0, false );

        wp_safe_redirect( admin_url( 'edit.php?post_type=' . Post_Types::POST_TYPE . '&page=westin-test-demo-data&westin_demo_status=installed' ) );
        exit;
    }

    public static function handle_remove(): void {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( esc_html__( 'You do not have permission to remove demo data.', 'westin-test-events' ) );
        }

        check_admin_referer( 'westin_test_events_remove_demo_data' );

        $removed = self::remove();
        update_option( self::NOTICE_OPTION, 0, false );

        wp_safe_redirect( admin_url( 'edit.php?post_type=' . Post_Types::POST_TYPE . '&page=westin-test-demo-data&westin_demo_status=' . ( $removed ? 'removed' : 'noop' ) ) );
        exit;
    }

    public static function handle_dismiss_notice(): void {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( esc_html__( 'You do not have permission to dismiss this notice.', 'westin-test-events' ) );
        }

        check_admin_referer( 'westin_test_events_dismiss_demo_notice' );
        update_option( self::NOTICE_OPTION, 0, false );

        wp_safe_redirect( admin_url( 'edit.php?post_type=' . Post_Types::POST_TYPE ) );
        exit;
    }

    public static function install(): void {
        self::ensure_terms();

        $items = self::get_demo_items();

        foreach ( $items as $item ) {
            $existing = get_posts(
                array(
                    'post_type'      => Post_Types::POST_TYPE,
                    'post_status'    => array( 'publish', 'draft', 'future', 'pending', 'private' ),
                    'meta_key'       => '_westin_test_demo_slug',
                    'meta_value'     => sanitize_title( $item['title'] ),
                    'posts_per_page' => 1,
                    'fields'         => 'ids',
                )
            );

            if ( ! empty( $existing ) ) {
                continue;
            }

            $post_id = wp_insert_post(
                array(
                    'post_type'    => Post_Types::POST_TYPE,
                    'post_status'  => 'publish',
                    'post_title'   => $item['title'],
                    'post_excerpt' => $item['excerpt'],
                    'post_content' => $item['content'],
                ),
                true
            );

            if ( is_wp_error( $post_id ) || ! $post_id ) {
                continue;
            }

            update_post_meta( $post_id, '_westin_test_demo_item', 1 );
            update_post_meta( $post_id, '_westin_test_demo_slug', sanitize_title( $item['title'] ) );
            update_post_meta( $post_id, '_westin_event_date', $item['date'] );
            update_post_meta( $post_id, '_westin_event_start_time', $item['start_time'] );
            update_post_meta( $post_id, '_westin_event_location', $item['location'] );
            update_post_meta( $post_id, '_westin_event_address', $item['address'] );
            update_post_meta( $post_id, '_westin_event_speaker', $item['speaker'] );
            update_post_meta( $post_id, '_westin_event_capacity', (int) $item['capacity'] );
            update_post_meta( $post_id, '_westin_event_rsvp_count', 0 );

            wp_set_object_terms( $post_id, $item['type'], Post_Types::TAXONOMY_TYPE );
            wp_set_object_terms( $post_id, $item['audience'], Post_Types::TAXONOMY_AUDIENCE );
            wp_set_object_terms( $post_id, $item['series'], Post_Types::TAXONOMY_SERIES );

            $attachment_id = self::import_demo_image( $item['featured_image_suggestion'], $post_id );
            if ( $attachment_id ) {
                set_post_thumbnail( $post_id, $attachment_id );
            }
        }

        Cache::bump();
    }

    public static function remove(): bool {
        $removed = false;

        $posts = get_posts(
            array(
                'post_type'      => Post_Types::POST_TYPE,
                'post_status'    => array( 'publish', 'draft', 'future', 'pending', 'private', 'trash' ),
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_key'       => '_westin_test_demo_item',
                'meta_value'     => 1,
            )
        );

        foreach ( $posts as $post_id ) {
            wp_delete_post( (int) $post_id, true );
            $removed = true;
        }

        $attachments = get_posts(
            array(
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_key'       => '_westin_test_demo_image',
                'meta_value'     => 1,
            )
        );

        foreach ( $attachments as $attachment_id ) {
            wp_delete_attachment( (int) $attachment_id, true );
            $removed = true;
        }

        Cache::bump();

        return $removed;
    }

    public static function is_demo_data_installed(): bool {
        $posts = get_posts(
            array(
                'post_type'      => Post_Types::POST_TYPE,
                'post_status'    => array( 'publish', 'draft', 'future', 'pending', 'private' ),
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_key'       => '_westin_test_demo_item',
                'meta_value'     => 1,
            )
        );

        return ! empty( $posts );
    }

    private static function get_demo_items(): array {
        $path = WESTIN_TEST_EVENTS_PATH . 'sample-data/westin-test-events-sample-data.json';

        if ( ! file_exists( $path ) ) {
            return array();
        }

        $contents = file_get_contents( $path );
        $items    = json_decode( (string) $contents, true );

        return is_array( $items ) ? $items : array();
    }

    private static function ensure_terms(): void {
        $items = self::get_demo_items();

        foreach ( $items as $item ) {
            if ( ! term_exists( $item['type'], Post_Types::TAXONOMY_TYPE ) ) {
                wp_insert_term( ucwords( str_replace( '-', ' ', $item['type'] ) ), Post_Types::TAXONOMY_TYPE, array( 'slug' => $item['type'] ) );
            }

            if ( ! term_exists( $item['audience'], Post_Types::TAXONOMY_AUDIENCE ) ) {
                wp_insert_term( ucwords( str_replace( '-', ' ', $item['audience'] ) ), Post_Types::TAXONOMY_AUDIENCE, array( 'slug' => $item['audience'] ) );
            }

            if ( ! term_exists( $item['series'], Post_Types::TAXONOMY_SERIES ) ) {
                wp_insert_term( ucwords( str_replace( '-', ' ', $item['series'] ) ), Post_Types::TAXONOMY_SERIES, array( 'slug' => $item['series'] ) );
            }
        }
    }

    private static function import_demo_image( string $filename, int $post_id ): int {
        $source = WESTIN_TEST_EVENTS_PATH . 'sample-data/images/' . basename( $filename );

        if ( ! file_exists( $source ) ) {
            return 0;
        }

        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $uploads = wp_upload_dir();

        if ( ! empty( $uploads['error'] ) ) {
            return 0;
        }

        $dest_name = wp_unique_filename( $uploads['path'], basename( $filename ) );
        $dest_path = trailingslashit( $uploads['path'] ) . $dest_name;

        if ( ! copy( $source, $dest_path ) ) {
            return 0;
        }

        $filetype = wp_check_filetype( $dest_name, null );
        $attachment_id = wp_insert_attachment(
            array(
                'post_mime_type' => $filetype['type'],
                'post_title'     => sanitize_file_name( pathinfo( $dest_name, PATHINFO_FILENAME ) ),
                'post_content'   => '',
                'post_status'    => 'inherit',
            ),
            $dest_path,
            $post_id
        );

        if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
            return 0;
        }

        $metadata = wp_generate_attachment_metadata( $attachment_id, $dest_path );
        wp_update_attachment_metadata( $attachment_id, $metadata );
        update_post_meta( $attachment_id, '_westin_test_demo_image', 1 );

        return (int) $attachment_id;
    }
}
