<?php

namespace WestinTest\Events;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RSVP {
    public static function init(): void {
        add_action( 'wp_ajax_westin_test_event_rsvp', array( __CLASS__, 'handle' ) );
        add_action( 'wp_ajax_nopriv_westin_test_event_rsvp', array( __CLASS__, 'handle' ) );
        add_shortcode( 'westin_event_rsvp', array( __CLASS__, 'render_form' ) );
        add_action( 'wp_mail_failed', array( __CLASS__, 'log_mail_failure' ) );
    }

    public static function render_form(): string {
        wp_enqueue_style( 'westin-test-events' );
        wp_enqueue_script( 'westin-test-events' );

        if ( ! is_singular( Post_Types::POST_TYPE ) ) {
            return '';
        }

        ob_start();
        ?>
        <form class="westin-test-rsvp-form" method="post">
            <input type="hidden" name="action" value="westin_test_event_rsvp">
            <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'westin_test_event_rsvp' ) ); ?>">
            <input type="hidden" name="event_id" value="<?php echo esc_attr( get_the_ID() ); ?>">

            <p>
                <label for="westin-test-rsvp-name"><?php esc_html_e( 'Your Name', 'westin-test-events' ); ?></label>
                <input type="text" id="westin-test-rsvp-name" name="name" required maxlength="120">
            </p>

            <p>
                <label for="westin-test-rsvp-email"><?php esc_html_e( 'Your Email', 'westin-test-events' ); ?></label>
                <input type="email" id="westin-test-rsvp-email" name="email" required maxlength="190">
            </p>

            <p>
                <button type="submit"><?php esc_html_e( 'RSVP', 'westin-test-events' ); ?></button>
            </p>

            <div class="westin-test-rsvp-response" aria-live="polite"></div>
        </form>
        <?php

        return ob_get_clean();
    }

    public static function handle(): void {
        check_ajax_referer( 'westin_test_event_rsvp', 'nonce' );

        $event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
        $name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

        $response = self::store_rsvp( $event_id, $name, $email );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error(
                array(
                    'message' => $response->get_error_message(),
                ),
                400
            );
        }

        $message = __( 'RSVP received. See you there.', 'westin-test-events' );

        if ( isset( $response['email_sent'] ) && ! $response['email_sent'] ) {
            $message = __( 'Your RSVP was received. We could not send the confirmation email right now, but your spot was still saved.', 'westin-test-events' );
        }

        wp_send_json_success(
            array(
                'message'       => $message,
                'email_sent'    => ! empty( $response['email_sent'] ),
                'admin_sent'    => ! empty( $response['admin_sent'] ),
                'attendee_sent' => ! empty( $response['attendee_sent'] ),
            )
        );
    }

    public static function store_rsvp( int $event_id, string $name, string $email ) {
        $name  = sanitize_text_field( $name );
        $email = sanitize_email( $email );

        if ( ! $event_id || Post_Types::POST_TYPE !== get_post_type( $event_id ) ) {
            return new \WP_Error( 'invalid_event', __( 'That event could not be found.', 'westin-test-events' ) );
        }

        if ( '' === $name || ! is_email( $email ) ) {
            return new \WP_Error( 'invalid_rsvp', __( 'Please provide a valid name and email address.', 'westin-test-events' ) );
        }

        $existing = get_post_meta( $event_id, '_westin_event_rsvp', false );

        foreach ( $existing as $entry ) {
            if ( is_array( $entry ) && isset( $entry['email'] ) && strtolower( (string) $entry['email'] ) === strtolower( $email ) ) {
                return new \WP_Error( 'duplicate_rsvp', __( 'This email has already RSVP’d for the event.', 'westin-test-events' ) );
            }
        }

        add_post_meta(
            $event_id,
            '_westin_event_rsvp',
            array(
                'name'  => $name,
                'email' => $email,
                'time'  => current_time( 'mysql' ),
            )
        );

        $count = absint( get_post_meta( $event_id, '_westin_event_rsvp_count', true ) );
        update_post_meta( $event_id, '_westin_event_rsvp_count', $count + 1 );

        $email_results = self::send_rsvp_notifications( $event_id, $name, $email );
        $email_sent    = ! empty( $email_results['admin_sent'] ) || ! empty( $email_results['attendee_sent'] );

        Cache::bump();

        return array(
            'saved'         => true,
            'email_sent'    => $email_sent,
            'admin_sent'    => ! empty( $email_results['admin_sent'] ),
            'attendee_sent' => ! empty( $email_results['attendee_sent'] ),
        );
    }

    public static function get_attendees_for_event( int $event_id, string $search = '' ): array {
        $entries   = get_post_meta( $event_id, '_westin_event_rsvp', false );
        $search    = strtolower( trim( $search ) );
        $attendees = array();

        foreach ( $entries as $meta_id => $entry ) {
            if ( ! is_array( $entry ) ) {
                continue;
            }

            $name  = isset( $entry['name'] ) ? sanitize_text_field( (string) $entry['name'] ) : '';
            $email = isset( $entry['email'] ) ? sanitize_email( (string) $entry['email'] ) : '';
            $time  = isset( $entry['time'] ) ? sanitize_text_field( (string) $entry['time'] ) : '';

            if ( '' !== $search ) {
                $haystack = strtolower( $name . ' ' . $email );
                if ( false === strpos( $haystack, $search ) ) {
                    continue;
                }
            }

            $attendees[] = array(
                'meta_id' => is_numeric( $meta_id ) ? (int) $meta_id : 0,
                'name'    => $name,
                'email'   => $email,
                'time'    => $time,
            );
        }

        usort(
            $attendees,
            static function ( array $left, array $right ): int {
                return strcmp( (string) $right['time'], (string) $left['time'] );
            }
        );

        return $attendees;
    }

    public static function send_rsvp_notifications( int $event_id, string $name, string $email ): array {
        $event_title = get_the_title( $event_id );
        $event_url   = get_permalink( $event_id );
        $event_date  = (string) get_post_meta( $event_id, '_westin_event_date', true );
        $event_time  = (string) get_post_meta( $event_id, '_westin_event_start_time', true );
        $location    = (string) get_post_meta( $event_id, '_westin_event_location', true );
        $admin_email = sanitize_email( (string) get_option( 'admin_email' ) );
        $site_name   = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
        $headers     = self::get_mail_headers( $site_name, $admin_email );
        $snapshot    = array_filter(
            array(
                $event_date ? sprintf( __( 'Date: %s', 'westin-test-events' ), $event_date ) : '',
                $event_time ? sprintf( __( 'Start time: %s', 'westin-test-events' ), $event_time ) : '',
                $location ? sprintf( __( 'Location: %s', 'westin-test-events' ), $location ) : '',
                $event_url ? sprintf( __( 'View event: %s', 'westin-test-events' ), $event_url ) : '',
            )
        );

        $admin_sent = false;
        if ( is_email( $admin_email ) ) {
            $admin_lines = array_merge(
                array(
                    sprintf( __( '%1$s (%2$s) just RSVP’d for %3$s.', 'westin-test-events' ), $name, $email, $event_title ),
                    '',
                ),
                $snapshot
            );
            $admin_sent = self::safe_send_mail(
                $admin_email,
                sprintf( __( 'New RSVP for %s', 'westin-test-events' ), $event_title ),
                implode( "
", $admin_lines ),
                $headers
            );
        }

        $attendee_sent = false;
        if ( is_email( $email ) ) {
            $attendee_lines = array(
                sprintf( __( 'Hi %s,', 'westin-test-events' ), $name ),
                '',
                sprintf( __( 'Thanks for reserving your spot for %s.', 'westin-test-events' ), $event_title ),
            );
            $attendee_lines = array_merge( $attendee_lines, array( '' ), $snapshot, array( '', __( 'We look forward to seeing you there.', 'westin-test-events' ) ) );
            $attendee_sent  = self::safe_send_mail(
                $email,
                sprintf( __( 'Your RSVP is confirmed for %s', 'westin-test-events' ), $event_title ),
                implode( "
", $attendee_lines ),
                $headers
            );
        }

        return array(
            'admin_sent'    => $admin_sent,
            'attendee_sent' => $attendee_sent,
        );
    }

    public static function get_mail_headers( string $site_name, string $reply_to = '' ): array {
        $site_domain = wp_parse_url( home_url(), PHP_URL_HOST );
        $from_domain = $site_domain ? preg_replace( '/^www\./', '', (string) $site_domain ) : 'localhost';
        $from_email  = 'no-reply@' . $from_domain;
        $headers     = array(
            'Content-Type: text/plain; charset=UTF-8',
            sprintf( 'From: %1$s <%2$s>', $site_name, $from_email ),
        );

        if ( is_email( $reply_to ) ) {
            $headers[] = sprintf( 'Reply-To: %s', $reply_to );
        }

        return $headers;
    }

    public static function log_mail_failure( $error ): void {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG || ! $error instanceof \WP_Error ) {
            return;
        }

        error_log( '[Westin Test Events] wp_mail failed: ' . $error->get_error_message() );
    }

    public static function safe_send_mail( $to, string $subject, string $message, array $headers = array() ): bool {
        if ( empty( $to ) ) {
            return false;
        }

        try {
            return (bool) wp_mail( $to, $subject, $message, $headers );
        } catch ( \Throwable $throwable ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[Westin Test Events] RSVP mail failed: ' . $throwable->getMessage() );
            }

            return false;
        }
    }
}
