<?php

namespace WestinTest\Events;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Meta_Boxes {
    public static function init(): void {
        add_action( 'add_meta_boxes', array( __CLASS__, 'register' ) );
        add_action( 'save_post_' . Post_Types::POST_TYPE, array( __CLASS__, 'save' ) );
    }

    public static function register(): void {
        add_meta_box(
            'westin-test-event-schedule',
            __( 'Event Schedule', 'westin-test-events' ),
            array( __CLASS__, 'render_schedule' ),
            Post_Types::POST_TYPE,
            'normal',
            'high',
            array( '__block_editor_compatible_meta_box' => true, '__back_compat_meta_box' => false )
        );

        add_meta_box(
            'westin-test-event-venue',
            __( 'Venue Details', 'westin-test-events' ),
            array( __CLASS__, 'render_venue' ),
            Post_Types::POST_TYPE,
            'normal',
            'default',
            array( '__block_editor_compatible_meta_box' => true, '__back_compat_meta_box' => false )
        );

        add_meta_box(
            'westin-test-event-speaker',
            __( 'Featured Speaker', 'westin-test-events' ),
            array( __CLASS__, 'render_speaker' ),
            Post_Types::POST_TYPE,
            'side',
            'default',
            array( '__block_editor_compatible_meta_box' => true, '__back_compat_meta_box' => false )
        );

        add_meta_box(
            'westin-test-event-capacity',
            __( 'Attendance Capacity', 'westin-test-events' ),
            array( __CLASS__, 'render_capacity' ),
            Post_Types::POST_TYPE,
            'side',
            'default',
            array( '__block_editor_compatible_meta_box' => true, '__back_compat_meta_box' => false )
        );

        add_meta_box(
            'westin-test-event-attendees',
            __( 'RSVP Attendees', 'westin-test-events' ),
            array( __CLASS__, 'render_attendees' ),
            Post_Types::POST_TYPE,
            'normal',
            'default',
            array( '__block_editor_compatible_meta_box' => true, '__back_compat_meta_box' => false )
        );
    }

    public static function render_schedule( \WP_Post $post ): void {
        wp_nonce_field( 'westin_test_save_event', 'westin_test_event_nonce' );
        $date       = get_post_meta( $post->ID, '_westin_event_date', true );
        $start_time = get_post_meta( $post->ID, '_westin_event_start_time', true );
        ?>
        <p>
            <label for="westin_event_date"><strong><?php esc_html_e( 'Event Date', 'westin-test-events' ); ?></strong></label><br>
            <input type="date" id="westin_event_date" name="westin_event_date" value="<?php echo esc_attr( $date ); ?>" class="regular-text">
        </p>
        <p>
            <label for="westin_event_start_time"><strong><?php esc_html_e( 'Start Time', 'westin-test-events' ); ?></strong></label><br>
            <input type="time" id="westin_event_start_time" name="westin_event_start_time" value="<?php echo esc_attr( $start_time ); ?>" class="regular-text">
        </p>
        <p class="description"><?php esc_html_e( 'This plugin sorts by the saved event date, so keeping the date field clean matters.', 'westin-test-events' ); ?></p>
        <?php
    }

    public static function render_venue( \WP_Post $post ): void {
        $location = get_post_meta( $post->ID, '_westin_event_location', true );
        $address  = get_post_meta( $post->ID, '_westin_event_address', true );
        ?>
        <p>
            <label for="westin_event_location"><strong><?php esc_html_e( 'Location', 'westin-test-events' ); ?></strong></label><br>
            <input type="text" id="westin_event_location" name="westin_event_location" value="<?php echo esc_attr( $location ); ?>" class="regular-text" maxlength="255" placeholder="<?php esc_attr_e( 'Example: Skyline Hall', 'westin-test-events' ); ?>">
        </p>
        <p>
            <label for="westin_event_address"><strong><?php esc_html_e( 'Address', 'westin-test-events' ); ?></strong></label><br>
            <textarea id="westin_event_address" name="westin_event_address" rows="4" class="widefat" placeholder="<?php esc_attr_e( 'Street, city, state, zip', 'westin-test-events' ); ?>"><?php echo esc_textarea( $address ); ?></textarea>
        </p>
        <?php
    }

    public static function render_speaker( \WP_Post $post ): void {
        $speaker = get_post_meta( $post->ID, '_westin_event_speaker', true );
        ?>
        <p>
            <label for="westin_event_speaker"><strong><?php esc_html_e( 'Speaker / Host', 'westin-test-events' ); ?></strong></label><br>
            <input type="text" id="westin_event_speaker" name="westin_event_speaker" value="<?php echo esc_attr( $speaker ); ?>" class="widefat" maxlength="190">
        </p>
        <?php
    }

    public static function render_capacity( \WP_Post $post ): void {
        $capacity = absint( get_post_meta( $post->ID, '_westin_event_capacity', true ) );
        ?>
        <p>
            <label for="westin_event_capacity"><strong><?php esc_html_e( 'Capacity', 'westin-test-events' ); ?></strong></label><br>
            <input type="number" min="0" step="1" id="westin_event_capacity" name="westin_event_capacity" value="<?php echo esc_attr( (string) $capacity ); ?>" class="widefat">
        </p>
        <p class="description"><?php esc_html_e( 'Leave empty or set to 0 if the event does not have a hard cap.', 'westin-test-events' ); ?></p>
        <?php
    }


    public static function render_attendees( \WP_Post $post ): void {
        $entries = RSVP::get_attendees_for_event( $post->ID );

        if ( empty( $entries ) ) {
            echo '<p>' . esc_html__( 'No RSVPs yet for this event.', 'westin-test-events' ) . '</p>';
            return;
        }
        ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Attendee', 'westin-test-events' ); ?></th>
                    <th><?php esc_html_e( 'Email', 'westin-test-events' ); ?></th>
                    <th><?php esc_html_e( 'Submitted', 'westin-test-events' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $entries as $entry ) : ?>
                <tr>
                    <td><?php echo esc_html( $entry['name'] ?: __( 'Unnamed attendee', 'westin-test-events' ) ); ?></td>
                    <td><a href="mailto:<?php echo esc_attr( $entry['email'] ); ?>"><?php echo esc_html( $entry['email'] ); ?></a></td>
                    <td><?php echo esc_html( $entry['time'] ? mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $entry['time'] ) : '—' ); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="description"><?php esc_html_e( 'Use the Events → RSVPs screen to search attendees across all events and remove entries when needed.', 'westin-test-events' ); ?></p>
        <?php
    }

    public static function save( int $post_id ): void {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        if ( wp_is_post_autosave( $post_id ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $nonce = isset( $_POST['westin_test_event_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['westin_test_event_nonce'] ) ) : '';

        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'westin_test_save_event' ) ) {
            return;
        }

        $date       = isset( $_POST['westin_event_date'] ) ? Post_Types::sanitize_date( wp_unslash( $_POST['westin_event_date'] ) ) : '';
        $location   = isset( $_POST['westin_event_location'] ) ? sanitize_text_field( wp_unslash( $_POST['westin_event_location'] ) ) : '';
        $start_time = isset( $_POST['westin_event_start_time'] ) ? Post_Types::sanitize_time( wp_unslash( $_POST['westin_event_start_time'] ) ) : '';
        $address    = isset( $_POST['westin_event_address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['westin_event_address'] ) ) : '';
        $speaker    = isset( $_POST['westin_event_speaker'] ) ? sanitize_text_field( wp_unslash( $_POST['westin_event_speaker'] ) ) : '';
        $capacity   = isset( $_POST['westin_event_capacity'] ) ? absint( wp_unslash( $_POST['westin_event_capacity'] ) ) : 0;

        update_post_meta( $post_id, '_westin_event_date', $date );
        update_post_meta( $post_id, '_westin_event_location', $location );
        update_post_meta( $post_id, '_westin_event_start_time', $start_time );
        update_post_meta( $post_id, '_westin_event_address', $address );
        update_post_meta( $post_id, '_westin_event_speaker', $speaker );
        update_post_meta( $post_id, '_westin_event_capacity', $capacity );
        Cache::bump();
    }
}
