<?php

namespace WestinTest\Events;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Notifications {
    public static function init(): void {
        add_action( 'transition_post_status', array( __CLASS__, 'maybe_notify' ), 10, 3 );
    }

    public static function maybe_notify( $new_status, $old_status, $post ): void {
        if ( ! $post instanceof \WP_Post ) {
            return;
        }

        if ( wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
            return;
        }

        if ( Post_Types::POST_TYPE !== $post->post_type || 'publish' !== $new_status ) {
            return;
        }

        $payload = self::get_email_payload( $post, (string) $old_status );

        if ( empty( $payload['recipients'] ) ) {
            Cache::bump();
            return;
        }

        try {
            wp_mail( $payload['recipients'], $payload['subject'], $payload['message'] );
        } catch ( \Throwable $throwable ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[Westin Test Events] Notification send failed: ' . $throwable->getMessage() );
            }
        }

        Cache::bump();
    }

    public static function get_email_payload( \WP_Post $post, string $old_status ): array {
        $subject = 'publish' === $old_status
            ? sprintf( __( 'Event updated: %s', 'westin-test-events' ), $post->post_title )
            : sprintf( __( 'New event published: %s', 'westin-test-events' ), $post->post_title );

        $date      = (string) get_post_meta( $post->ID, '_westin_event_date', true );
        $time      = (string) get_post_meta( $post->ID, '_westin_event_start_time', true );
        $location  = (string) get_post_meta( $post->ID, '_westin_event_location', true );
        $permalink = get_permalink( $post );

        $message_lines   = array();
        $message_lines[] = sprintf( __( 'Event: %s', 'westin-test-events' ), $post->post_title );

        if ( '' !== $date ) {
            $message_lines[] = sprintf( __( 'Date: %s', 'westin-test-events' ), $date );
        }

        if ( '' !== $time ) {
            $message_lines[] = sprintf( __( 'Start time: %s', 'westin-test-events' ), $time );
        }

        if ( '' !== $location ) {
            $message_lines[] = sprintf( __( 'Location: %s', 'westin-test-events' ), $location );
        }

        if ( $permalink ) {
            $message_lines[] = sprintf( __( 'View event: %s', 'westin-test-events' ), $permalink );
        }

        $message_lines[] = '';
        $message_lines[] = 'publish' === $old_status
            ? __( 'This event was just updated. If you already RSVP’d, this is a quick heads-up so you can review the latest details.', 'westin-test-events' )
            : __( 'A new event is now live. You are receiving this message because you RSVP’d for event updates on this site or you are the site administrator.', 'westin-test-events' );

        return array(
            'recipients' => self::get_notification_recipients( $post->ID ),
            'subject'    => $subject,
            'message'    => implode( "\n", $message_lines ),
        );
    }

    public static function get_notification_recipients( int $post_id ): array {
        $recipients  = array();
        $admin_email = sanitize_email( (string) get_option( 'admin_email' ) );

        if ( is_email( $admin_email ) ) {
            $recipients[] = $admin_email;
        }

        $entries = get_post_meta( $post_id, '_westin_event_rsvp', false );

        foreach ( $entries as $entry ) {
            if ( ! is_array( $entry ) || empty( $entry['email'] ) ) {
                continue;
            }

            $email = sanitize_email( (string) $entry['email'] );

            if ( is_email( $email ) ) {
                $recipients[] = strtolower( $email );
            }
        }

        $recipients = array_values( array_unique( $recipients ) );

        return array_filter( $recipients, 'is_email' );
    }
}
