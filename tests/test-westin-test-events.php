<?php

class Test_Westin_Test_Events extends WP_UnitTestCase {
    public function test_post_type_registered(): void {
        $this->assertTrue( post_type_exists( 'westin_event' ) );
    }

    public function test_all_taxonomies_registered(): void {
        $this->assertTrue( taxonomy_exists( 'westin_event_type' ) );
        $this->assertTrue( taxonomy_exists( 'westin_event_audience' ) );
        $this->assertTrue( taxonomy_exists( 'westin_event_series' ) );
    }

    public function test_valid_sanitize_helpers_accept_expected_values(): void {
        $this->assertSame( '2026-03-18', \WestinTest\Events\Post_Types::sanitize_date( '2026-03-18' ) );
        $this->assertSame( '18:30', \WestinTest\Events\Post_Types::sanitize_time( '18:30' ) );
    }

    public function test_sanitize_helpers_reject_bad_values(): void {
        $this->assertSame( '', \WestinTest\Events\Post_Types::sanitize_date( '03/18/2026' ) );
        $this->assertSame( '', \WestinTest\Events\Post_Types::sanitize_date( '2026-3-18' ) );
        $this->assertSame( '', \WestinTest\Events\Post_Types::sanitize_time( '6:30pm' ) );
        $this->assertSame( '', \WestinTest\Events\Post_Types::sanitize_time( '25:99' ) );
    }

    public function test_sanitize_helpers_allow_empty_values(): void {
        $this->assertSame( '', \WestinTest\Events\Post_Types::sanitize_date( '' ) );
        $this->assertSame( '', \WestinTest\Events\Post_Types::sanitize_time( '' ) );
        $this->assertSame( '', \WestinTest\Events\Post_Types::sanitize_date( null ) );
        $this->assertSame( '', \WestinTest\Events\Post_Types::sanitize_time( array( 'bad' ) ) );
    }

    public function test_event_meta_can_be_saved_and_read(): void {
        $post_id = self::factory()->post->create(
            array(
                'post_type' => 'westin_event',
            )
        );

        update_post_meta( $post_id, '_westin_event_date', '2026-03-18' );
        update_post_meta( $post_id, '_westin_event_start_time', '18:30' );
        update_post_meta( $post_id, '_westin_event_location', 'Showroom A' );

        $this->assertSame( '2026-03-18', get_post_meta( $post_id, '_westin_event_date', true ) );
        $this->assertSame( '18:30', get_post_meta( $post_id, '_westin_event_start_time', true ) );
        $this->assertSame( 'Showroom A', get_post_meta( $post_id, '_westin_event_location', true ) );
    }

    public function test_admin_columns_include_event_specific_fields(): void {
        $columns = \WestinTest\Events\Admin::columns(
            array(
                'cb'    => '<input type="checkbox" />',
                'title' => 'Title',
                'date'  => 'Date',
            )
        );

        $this->assertArrayHasKey( 'event_date', $columns );
        $this->assertArrayHasKey( 'event_location', $columns );
        $this->assertArrayHasKey( 'event_type', $columns );
        $this->assertArrayHasKey( 'event_audience', $columns );
        $this->assertArrayHasKey( 'event_series', $columns );
        $this->assertArrayHasKey( 'event_speaker', $columns );
        $this->assertArrayHasKey( 'rsvp_count', $columns );
    }

    public function test_sortable_columns_include_date_and_rsvp_count(): void {
        $sortable = \WestinTest\Events\Admin::sortable_columns( array() );

        $this->assertSame( 'event_date', $sortable['event_date'] );
        $this->assertSame( 'rsvp_count', $sortable['rsvp_count'] );
    }

    public function test_shortcode_renders_event_title(): void {
        $post_id = self::factory()->post->create(
            array(
                'post_type'   => 'westin_event',
                'post_status' => 'publish',
                'post_title'  => 'Launch Party',
            )
        );

        update_post_meta( $post_id, '_westin_event_date', '2026-03-18' );

        $output = do_shortcode( '[westin_events posts_per_page="1"]' );

        $this->assertStringContainsString( 'Launch Party', $output );
    }

    public function test_rsvp_store_rejects_invalid_event(): void {
        $result = \WestinTest\Events\RSVP::store_rsvp( 999999, 'Jane Doe', 'jane@example.com' );

        $this->assertInstanceOf( 'WP_Error', $result );
        $this->assertSame( 'invalid_event', $result->get_error_code() );
    }

    public function test_rsvp_store_rejects_invalid_email(): void {
        $post_id = self::factory()->post->create(
            array(
                'post_type'   => 'westin_event',
                'post_status' => 'publish',
                'post_title'  => 'Webinar',
            )
        );

        $result = \WestinTest\Events\RSVP::store_rsvp( $post_id, 'Jane Doe', 'not-an-email' );

        $this->assertInstanceOf( 'WP_Error', $result );
        $this->assertSame( 'invalid_rsvp', $result->get_error_code() );
    }

    public function test_rsvp_store_rejects_empty_name_even_with_valid_email(): void {
        $post_id = self::factory()->post->create(
            array(
                'post_type'   => 'westin_event',
                'post_status' => 'publish',
                'post_title'  => 'Builder Breakfast',
            )
        );

        $result = \WestinTest\Events\RSVP::store_rsvp( $post_id, '', 'jane@example.com' );

        $this->assertInstanceOf( 'WP_Error', $result );
        $this->assertSame( 'invalid_rsvp', $result->get_error_code() );
    }

    public function test_rsvp_store_rejects_duplicate_email(): void {
        $post_id = self::factory()->post->create(
            array(
                'post_type'   => 'westin_event',
                'post_status' => 'publish',
                'post_title'  => 'Webinar',
            )
        );

        $first = \WestinTest\Events\RSVP::store_rsvp( $post_id, 'Jane Doe', 'jane@example.com' );
        $this->assertIsArray( $first );
        $this->assertTrue( $first['saved'] );

        $second = \WestinTest\Events\RSVP::store_rsvp( $post_id, 'Jane Doe', 'jane@example.com' );
        $this->assertInstanceOf( 'WP_Error', $second );
        $this->assertSame( 'duplicate_rsvp', $second->get_error_code() );
    }

    public function test_rsvp_store_sanitizes_name_before_storage(): void {
        $post_id = self::factory()->post->create(
            array(
                'post_type'   => 'westin_event',
                'post_status' => 'publish',
                'post_title'  => 'Webinar',
            )
        );

        $result = \WestinTest\Events\RSVP::store_rsvp( $post_id, '<b>Jane Doe</b>', 'jane@example.com' );

        $this->assertIsArray( $result );

        $entries = get_post_meta( $post_id, '_westin_event_rsvp', false );
        $this->assertSame( 'Jane Doe', $entries[0]['name'] );
    }

    public function test_rsvp_store_still_succeeds_when_mail_fails(): void {
        $post_id = self::factory()->post->create(
            array(
                'post_type'   => 'westin_event',
                'post_status' => 'publish',
                'post_title'  => 'Builder Breakfast',
            )
        );

        add_filter(
            'pre_wp_mail',
            static function () {
                return false;
            }
        );

        $result = \WestinTest\Events\RSVP::store_rsvp( $post_id, 'Jordan West', 'jordan@example.com' );

        remove_all_filters( 'pre_wp_mail' );

        $this->assertIsArray( $result );
        $this->assertTrue( $result['saved'] );
        $this->assertFalse( $result['email_sent'] );
        $this->assertSame( 1, (int) get_post_meta( $post_id, '_westin_event_rsvp_count', true ) );
    }

    public function test_safe_send_mail_returns_false_for_empty_recipient(): void {
        $this->assertFalse( \WestinTest\Events\RSVP::safe_send_mail( '', 'Subject', 'Body' ) );
    }

    public function test_get_attendees_for_event_returns_latest_entries_first(): void {
        $post_id = self::factory()->post->create(
            array(
                'post_type'   => 'westin_event',
                'post_status' => 'publish',
                'post_title'  => 'Builder Summit',
            )
        );

        add_post_meta( $post_id, '_westin_event_rsvp', array( 'name' => 'Early One', 'email' => 'early@example.com', 'time' => '2026-01-01 08:00:00' ) );
        add_post_meta( $post_id, '_westin_event_rsvp', array( 'name' => 'Late One', 'email' => 'late@example.com', 'time' => '2026-01-02 08:00:00' ) );

        $entries = \WestinTest\Events\RSVP::get_attendees_for_event( $post_id );

        $this->assertCount( 2, $entries );
        $this->assertSame( 'Late One', $entries[0]['name'] );
        $this->assertSame( 'Early One', $entries[1]['name'] );
    }

    public function test_get_attendees_for_event_filters_by_search_term(): void {
        $post_id = self::factory()->post->create(
            array(
                'post_type'   => 'westin_event',
                'post_status' => 'publish',
                'post_title'  => 'Builder Summit',
            )
        );

        add_post_meta( $post_id, '_westin_event_rsvp', array( 'name' => 'Jordan West', 'email' => 'jordan@example.com', 'time' => '2026-01-03 08:00:00' ) );
        add_post_meta( $post_id, '_westin_event_rsvp', array( 'name' => 'Alex Stone', 'email' => 'alex@example.com', 'time' => '2026-01-02 08:00:00' ) );
        add_post_meta( $post_id, '_westin_event_rsvp', 'bad-row' );

        $entries = \WestinTest\Events\RSVP::get_attendees_for_event( $post_id, 'alex' );

        $this->assertCount( 1, $entries );
        $this->assertSame( 'alex@example.com', $entries[0]['email'] );
    }

    public function test_admin_rsvp_rows_can_filter_by_search_term(): void {
        $post_id = self::factory()->post->create(
            array(
                'post_type'   => 'westin_event',
                'post_status' => 'publish',
                'post_title'  => 'Builder Summit',
            )
        );

        add_post_meta( $post_id, '_westin_event_rsvp', array( 'name' => 'Jordan West', 'email' => 'jordan@example.com', 'time' => '2026-01-03 08:00:00' ) );
        add_post_meta( $post_id, '_westin_event_rsvp', array( 'name' => 'Alex Stone', 'email' => 'alex@example.com', 'time' => '2026-01-02 08:00:00' ) );

        $rows = \WestinTest\Events\Admin::get_rsvp_rows( $post_id, 'jordan' );

        $this->assertCount( 1, $rows );
        $this->assertSame( 'jordan@example.com', $rows[0]['email'] );
    }

    public function test_admin_rsvp_rows_return_latest_rows_across_multiple_events(): void {
        $older_event = self::factory()->post->create(
            array(
                'post_type'   => 'westin_event',
                'post_status' => 'publish',
                'post_title'  => 'Older Event',
            )
        );
        $newer_event = self::factory()->post->create(
            array(
                'post_type'   => 'westin_event',
                'post_status' => 'publish',
                'post_title'  => 'Newer Event',
            )
        );

        add_post_meta( $older_event, '_westin_event_rsvp', array( 'name' => 'Older Person', 'email' => 'older@example.com', 'time' => '2026-01-01 08:00:00' ) );
        add_post_meta( $newer_event, '_westin_event_rsvp', array( 'name' => 'Newer Person', 'email' => 'newer@example.com', 'time' => '2026-01-03 08:00:00' ) );

        $rows = \WestinTest\Events\Admin::get_rsvp_rows();

        $this->assertNotEmpty( $rows );
        $this->assertSame( 'newer@example.com', $rows[0]['email'] );
    }

    public function test_get_delete_link_contains_expected_action_arguments(): void {
        $link = \WestinTest\Events\Admin::get_delete_link( 22, 33 );

        $this->assertStringContainsString( 'westin_action=delete_rsvp', $link );
        $this->assertStringContainsString( 'event_id=22', $link );
        $this->assertStringContainsString( 'rsvp_meta_id=33', $link );
        $this->assertStringContainsString( '_wpnonce=', $link );
    }

    public function test_notification_recipients_include_admin_and_unique_attendees(): void {
        update_option( 'admin_email', 'admin@example.com' );

        $post_id = self::factory()->post->create(
            array(
                'post_type'   => 'westin_event',
                'post_status' => 'publish',
                'post_title'  => 'Builder Summit',
            )
        );

        add_post_meta( $post_id, '_westin_event_rsvp', array( 'name' => 'Jane', 'email' => 'Jane@example.com' ) );
        add_post_meta( $post_id, '_westin_event_rsvp', array( 'name' => 'John', 'email' => 'john@example.com' ) );
        add_post_meta( $post_id, '_westin_event_rsvp', array( 'name' => 'Jane 2', 'email' => 'jane@example.com' ) );

        $recipients = \WestinTest\Events\Notifications::get_notification_recipients( $post_id );

        $this->assertSame(
            array( 'admin@example.com', 'jane@example.com', 'john@example.com' ),
            $recipients
        );
    }

    public function test_notification_recipients_ignore_invalid_emails(): void {
        update_option( 'admin_email', 'not-an-email' );

        $post_id = self::factory()->post->create(
            array(
                'post_type'   => 'westin_event',
                'post_status' => 'publish',
                'post_title'  => 'Builder Summit',
            )
        );

        add_post_meta( $post_id, '_westin_event_rsvp', array( 'name' => 'Bad', 'email' => 'not-valid' ) );
        add_post_meta( $post_id, '_westin_event_rsvp', array( 'name' => 'Good', 'email' => 'good@example.com' ) );

        $recipients = \WestinTest\Events\Notifications::get_notification_recipients( $post_id );

        $this->assertSame( array( 'good@example.com' ), $recipients );
    }

    public function test_notification_payload_changes_based_on_old_status(): void {
        update_option( 'admin_email', 'admin@example.com' );

        $post_id = self::factory()->post->create(
            array(
                'post_type'   => 'westin_event',
                'post_status' => 'publish',
                'post_title'  => 'Builder Summit',
            )
        );

        update_post_meta( $post_id, '_westin_event_date', '2026-04-15' );
        update_post_meta( $post_id, '_westin_event_start_time', '09:00' );
        update_post_meta( $post_id, '_westin_event_location', 'Boise Convention Center' );

        $post = get_post( $post_id );

        $new_payload    = \WestinTest\Events\Notifications::get_email_payload( $post, 'draft' );
        $update_payload = \WestinTest\Events\Notifications::get_email_payload( $post, 'publish' );

        $this->assertStringContainsString( 'New event published', $new_payload['subject'] );
        $this->assertStringContainsString( 'Event updated', $update_payload['subject'] );
        $this->assertStringContainsString( 'Boise Convention Center', $update_payload['message'] );
    }

    public function test_notification_payload_still_builds_when_optional_meta_is_missing(): void {
        update_option( 'admin_email', 'admin@example.com' );

        $post_id = self::factory()->post->create(
            array(
                'post_type'   => 'westin_event',
                'post_status' => 'publish',
                'post_title'  => 'No Meta Event',
            )
        );

        $post = get_post( $post_id );
        $payload = \WestinTest\Events\Notifications::get_email_payload( $post, 'draft' );

        $this->assertArrayHasKey( 'subject', $payload );
        $this->assertArrayHasKey( 'message', $payload );
        $this->assertStringContainsString( 'No Meta Event', $payload['message'] );
    }

    public function test_cache_version_bumps(): void {
        \WestinTest\Events\Cache::set_version( 100 );
        $before = \WestinTest\Events\Cache::get_version();
        \WestinTest\Events\Cache::bump();
        $after = \WestinTest\Events\Cache::get_version();

        $this->assertNotSame( $before, $after );
        $this->assertGreaterThanOrEqual( $before, $after );
    }

    public function test_cache_key_is_stable_for_same_version_and_changes_after_bump(): void {
        \WestinTest\Events\Cache::set_version( 12345 );
        $first  = \WestinTest\Events\Cache::key( 'listing:test' );
        $second = \WestinTest\Events\Cache::key( 'listing:test' );

        $this->assertSame( $first, $second );

        \WestinTest\Events\Cache::set_version( 67890 );
        $third = \WestinTest\Events\Cache::key( 'listing:test' );

        $this->assertNotSame( $first, $third );
    }
}
