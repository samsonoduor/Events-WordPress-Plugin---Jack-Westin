<?php

namespace WestinTest\Events;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin {
    public static function init(): void {
        add_filter( 'manage_' . Post_Types::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
        add_action( 'manage_' . Post_Types::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
        add_filter( 'manage_edit-' . Post_Types::POST_TYPE . '_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
        add_action( 'pre_get_posts', array( __CLASS__, 'sort_query' ) );
        add_action( 'admin_menu', array( __CLASS__, 'register_rsvp_page' ) );
        add_action( 'admin_init', array( __CLASS__, 'handle_rsvp_delete' ) );
    }

    public static function columns( array $columns ): array {
        $rebuilt = array();

        foreach ( $columns as $key => $label ) {
            $rebuilt[ $key ] = $label;

            if ( 'title' === $key ) {
                $rebuilt['event_date']     = __( 'Date', 'westin-test-events' );
                $rebuilt['event_location'] = __( 'Location', 'westin-test-events' );
                $rebuilt['event_type']     = __( 'Type', 'westin-test-events' );
                $rebuilt['event_audience'] = __( 'Audience', 'westin-test-events' );
                $rebuilt['event_series']   = __( 'Series', 'westin-test-events' );
                $rebuilt['event_speaker']  = __( 'Speaker', 'westin-test-events' );
                $rebuilt['rsvp_count']     = __( 'RSVPs', 'westin-test-events' );
            }
        }

        return $rebuilt;
    }

    public static function column_content( string $column, int $post_id ): void {
        switch ( $column ) {
            case 'event_date':
                $date = get_post_meta( $post_id, '_westin_event_date', true );
                echo esc_html( $date ? Frontend::format_date( $date ) : '—' );
                break;

            case 'event_location':
                echo esc_html( get_post_meta( $post_id, '_westin_event_location', true ) ?: '—' );
                break;

            case 'event_type':
                self::render_term_column( $post_id, Post_Types::TAXONOMY_TYPE );
                break;

            case 'event_audience':
                self::render_term_column( $post_id, Post_Types::TAXONOMY_AUDIENCE );
                break;

            case 'event_series':
                self::render_term_column( $post_id, Post_Types::TAXONOMY_SERIES );
                break;

            case 'event_speaker':
                echo esc_html( get_post_meta( $post_id, '_westin_event_speaker', true ) ?: '—' );
                break;

            case 'rsvp_count':
                echo esc_html( (string) absint( get_post_meta( $post_id, '_westin_event_rsvp_count', true ) ) );
                break;
        }
    }

    private static function render_term_column( int $post_id, string $taxonomy ): void {
        $terms = get_the_terms( $post_id, $taxonomy );

        if ( is_array( $terms ) && ! empty( $terms ) ) {
            echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
            return;
        }

        echo '—';
    }

    public static function sortable_columns( array $columns ): array {
        $columns['event_date'] = 'event_date';
        $columns['rsvp_count'] = 'rsvp_count';

        return $columns;
    }

    public static function sort_query( \WP_Query $query ): void {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }

        if ( Post_Types::POST_TYPE !== $query->get( 'post_type' ) ) {
            return;
        }

        if ( 'event_date' === $query->get( 'orderby' ) ) {
            $query->set( 'meta_key', '_westin_event_date' );
            $query->set( 'orderby', 'meta_value' );
        }

        if ( 'rsvp_count' === $query->get( 'orderby' ) ) {
            $query->set( 'meta_key', '_westin_event_rsvp_count' );
            $query->set( 'orderby', 'meta_value_num' );
        }
    }

    public static function register_rsvp_page(): void {
        add_submenu_page(
            'edit.php?post_type=' . Post_Types::POST_TYPE,
            __( 'RSVP Management', 'westin-test-events' ),
            __( 'RSVPs', 'westin-test-events' ),
            'edit_posts',
            'westin-test-event-rsvps',
            array( __CLASS__, 'render_rsvp_page' )
        );
    }

    public static function render_rsvp_page(): void {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( esc_html__( 'You do not have permission to view RSVP entries.', 'westin-test-events' ) );
        }

        $selected_event = isset( $_GET['event_id'] ) ? absint( $_GET['event_id'] ) : 0;
        $search         = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
        $attendees      = self::get_rsvp_rows( $selected_event, $search );
        $events         = get_posts(
            array(
                'post_type'      => Post_Types::POST_TYPE,
                'post_status'    => array( 'publish', 'draft', 'future', 'pending', 'private' ),
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            )
        );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'RSVP Management', 'westin-test-events' ); ?></h1>
            <p><?php esc_html_e( 'Review attendees, search RSVP records, and remove entries that were submitted by mistake.', 'westin-test-events' ); ?></p>

            <?php if ( isset( $_GET['rsvp_deleted'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'RSVP entry removed.', 'westin-test-events' ); ?></p></div>
            <?php endif; ?>

            <form method="get" style="margin: 16px 0 20px; display: flex; gap: 12px; align-items: end; flex-wrap: wrap;">
                <input type="hidden" name="post_type" value="<?php echo esc_attr( Post_Types::POST_TYPE ); ?>">
                <input type="hidden" name="page" value="westin-test-event-rsvps">
                <p>
                    <label for="westin-test-rsvp-event-filter"><strong><?php esc_html_e( 'Event', 'westin-test-events' ); ?></strong></label><br>
                    <select name="event_id" id="westin-test-rsvp-event-filter">
                        <option value="0"><?php esc_html_e( 'All events', 'westin-test-events' ); ?></option>
                        <?php foreach ( $events as $event ) : ?>
                            <option value="<?php echo esc_attr( (string) $event->ID ); ?>" <?php selected( $selected_event, $event->ID ); ?>>
                                <?php echo esc_html( get_the_title( $event ) ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p>
                    <label for="westin-test-rsvp-search"><strong><?php esc_html_e( 'Search attendee', 'westin-test-events' ); ?></strong></label><br>
                    <input type="search" name="s" id="westin-test-rsvp-search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Name or email', 'westin-test-events' ); ?>">
                </p>
                <p>
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Filter RSVPs', 'westin-test-events' ); ?></button>
                </p>
            </form>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Attendee', 'westin-test-events' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'westin-test-events' ); ?></th>
                        <th><?php esc_html_e( 'Event', 'westin-test-events' ); ?></th>
                        <th><?php esc_html_e( 'Submitted', 'westin-test-events' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'westin-test-events' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $attendees ) ) : ?>
                    <tr>
                        <td colspan="5"><?php esc_html_e( 'No RSVPs found for the current filters.', 'westin-test-events' ); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $attendees as $row ) : ?>
                        <tr>
                            <td><?php echo esc_html( $row['name'] ?: __( 'Unnamed attendee', 'westin-test-events' ) ); ?></td>
                            <td><a href="mailto:<?php echo esc_attr( $row['email'] ); ?>"><?php echo esc_html( $row['email'] ); ?></a></td>
                            <td>
                                <a href="<?php echo esc_url( get_edit_post_link( $row['event_id'] ) ?: '#' ); ?>"><?php echo esc_html( get_the_title( $row['event_id'] ) ); ?></a>
                            </td>
                            <td><?php echo esc_html( $row['time'] ? mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $row['time'] ) : '—' ); ?></td>
                            <td>
                                <a class="button button-small" href="<?php echo esc_url( get_permalink( $row['event_id'] ) ?: '#' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View event', 'westin-test-events' ); ?></a>
                                <a class="button button-small" href="<?php echo esc_url( self::get_delete_link( $row['event_id'], $row['meta_id'] ) ); ?>"><?php esc_html_e( 'Delete RSVP', 'westin-test-events' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function get_rsvp_rows( int $selected_event = 0, string $search = '' ): array {
        $event_ids = array();

        if ( $selected_event > 0 ) {
            $event_ids[] = $selected_event;
        } else {
            $event_ids = get_posts(
                array(
                    'post_type'      => Post_Types::POST_TYPE,
                    'post_status'    => array( 'publish', 'draft', 'future', 'pending', 'private' ),
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                )
            );
        }

        $rows = array();

        foreach ( $event_ids as $event_id ) {
            foreach ( RSVP::get_attendees_for_event( (int) $event_id, $search ) as $entry ) {
                $rows[] = array(
                    'meta_id'  => (int) $entry['meta_id'],
                    'name'     => $entry['name'],
                    'email'    => $entry['email'],
                    'time'     => $entry['time'],
                    'event_id' => (int) $event_id,
                );
            }
        }

        usort(
            $rows,
            static function ( array $left, array $right ): int {
                return strcmp( (string) $right['time'], (string) $left['time'] );
            }
        );

        return $rows;
    }

    public static function get_delete_link( int $event_id, int $meta_id ): string {
        return wp_nonce_url(
            add_query_arg(
                array(
                    'post_type'       => Post_Types::POST_TYPE,
                    'page'            => 'westin-test-event-rsvps',
                    'westin_action'   => 'delete_rsvp',
                    'event_id'        => $event_id,
                    'rsvp_meta_id'    => $meta_id,
                ),
                admin_url( 'edit.php' )
            ),
            'westin_delete_rsvp_' . $meta_id
        );
    }

    public static function handle_rsvp_delete(): void {
        if ( ! is_admin() || ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        $action = isset( $_GET['westin_action'] ) ? sanitize_key( wp_unslash( $_GET['westin_action'] ) ) : '';

        if ( 'delete_rsvp' !== $action ) {
            return;
        }

        $event_id = isset( $_GET['event_id'] ) ? absint( $_GET['event_id'] ) : 0;
        $meta_id  = isset( $_GET['rsvp_meta_id'] ) ? absint( $_GET['rsvp_meta_id'] ) : 0;
        $nonce    = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( ! $event_id || ! $meta_id || ! wp_verify_nonce( $nonce, 'westin_delete_rsvp_' . $meta_id ) ) {
            return;
        }

        delete_metadata_by_mid( 'post', $meta_id );

        $remaining = count( get_post_meta( $event_id, '_westin_event_rsvp', false ) );
        update_post_meta( $event_id, '_westin_event_rsvp_count', $remaining );
        Cache::bump();

        wp_safe_redirect(
            add_query_arg(
                array(
                    'post_type'     => Post_Types::POST_TYPE,
                    'page'          => 'westin-test-event-rsvps',
                    'event_id'      => $event_id,
                    'rsvp_deleted'  => 1,
                ),
                admin_url( 'edit.php' )
            )
        );
        exit;
    }
}
