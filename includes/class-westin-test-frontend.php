<?php

namespace WestinTest\Events;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Frontend {
    public static function init(): void {
        add_filter( 'single_template', array( __CLASS__, 'single_template' ) );
        add_filter( 'archive_template', array( __CLASS__, 'archive_template' ) );
        add_shortcode( 'westin_events', array( __CLASS__, 'shortcode' ) );
        add_action( 'pre_get_posts', array( __CLASS__, 'apply_archive_filters' ) );
        add_action( 'init', array( __CLASS__, 'register_block' ) );
    }

    public static function single_template( string $template ): string {
        if ( is_singular( Post_Types::POST_TYPE ) ) {
            $custom = WESTIN_TEST_EVENTS_PATH . 'templates/single-westin_event.php';

            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }

        return $template;
    }

    public static function archive_template( string $template ): string {
        if ( is_post_type_archive( Post_Types::POST_TYPE ) ) {
            $custom = WESTIN_TEST_EVENTS_PATH . 'templates/archive-westin_event.php';

            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }

        return $template;
    }

    public static function register_block(): void {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        wp_register_script(
            'westin-test-events-block-editor',
            WESTIN_TEST_EVENTS_URL . 'assets/js/editor-block.js',
            array( 'wp-blocks', 'wp-components', 'wp-element', 'wp-block-editor', 'wp-server-side-render' ),
            WESTIN_TEST_EVENTS_VERSION,
            true
        );

        register_block_type(
            'westin-test/events-list',
            array(
                'api_version'     => 2,
                'editor_script'   => 'westin-test-events-block-editor',
                'style'           => 'westin-test-events',
                'render_callback' => array( __CLASS__, 'render_block' ),
                'attributes'      => array(
                    'postsPerPage' => array(
                        'type'    => 'number',
                        'default' => 6,
                    ),
                    'type' => array(
                        'type'    => 'string',
                        'default' => '',
                    ),
                    'audience' => array(
                        'type'    => 'string',
                        'default' => '',
                    ),
                    'series' => array(
                        'type'    => 'string',
                        'default' => '',
                    ),
                    'showExcerpt' => array(
                        'type'    => 'boolean',
                        'default' => true,
                    ),
                ),
            )
        );
    }

    public static function render_block( array $attributes = array() ): string {
        $atts = array(
            'posts_per_page' => isset( $attributes['postsPerPage'] ) ? absint( $attributes['postsPerPage'] ) : 6,
            'type'           => isset( $attributes['type'] ) ? sanitize_title( $attributes['type'] ) : '',
            'audience'       => isset( $attributes['audience'] ) ? sanitize_title( $attributes['audience'] ) : '',
            'series'         => isset( $attributes['series'] ) ? sanitize_title( $attributes['series'] ) : '',
            'show_excerpt'   => ! empty( $attributes['showExcerpt'] ) ? 'yes' : 'no',
        );

        return self::build_listing( $atts, 'block' );
    }

    public static function shortcode( array $atts ): string {
        $atts = shortcode_atts(
            array(
                'posts_per_page' => 6,
                'type'           => '',
                'audience'       => '',
                'series'         => '',
                'show_excerpt'   => 'yes',
            ),
            $atts,
            'westin_events'
        );

        return self::build_listing( $atts, 'shortcode' );
    }

    private static function build_listing( array $atts, string $context = 'shortcode' ): string {
        wp_enqueue_style( 'westin-test-events' );
        wp_enqueue_script( 'westin-test-events' );

        $normalized = array(
            'posts_per_page' => absint( $atts['posts_per_page'] ?? 6 ),
            'type'           => sanitize_title( (string) ( $atts['type'] ?? '' ) ),
            'audience'       => sanitize_title( (string) ( $atts['audience'] ?? '' ) ),
            'series'         => sanitize_title( (string) ( $atts['series'] ?? '' ) ),
            'show_excerpt'   => 'yes' === strtolower( (string) ( $atts['show_excerpt'] ?? 'yes' ) ) ? 'yes' : 'no',
            'search'         => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
            'start_date'     => isset( $_GET['start_date'] ) ? Post_Types::sanitize_date( wp_unslash( $_GET['start_date'] ) ) : '',
            'end_date'       => isset( $_GET['end_date'] ) ? Post_Types::sanitize_date( wp_unslash( $_GET['end_date'] ) ) : '',
        );

        if ( isset( $_GET['event_type'] ) ) {
            $normalized['type'] = sanitize_title( wp_unslash( $_GET['event_type'] ) );
        }

        if ( isset( $_GET['event_audience'] ) ) {
            $normalized['audience'] = sanitize_title( wp_unslash( $_GET['event_audience'] ) );
        }

        if ( isset( $_GET['event_series'] ) ) {
            $normalized['series'] = sanitize_title( wp_unslash( $_GET['event_series'] ) );
        }

        $cache_key = Cache::key( $context . ':' . wp_json_encode( $normalized ) );
        $cached    = get_transient( $cache_key );

        if ( false !== $cached ) {
            return $cached;
        }

        $args = array(
            'post_type'              => Post_Types::POST_TYPE,
            'post_status'            => 'publish',
            'posts_per_page'         => $normalized['posts_per_page'] > 0 ? $normalized['posts_per_page'] : 6,
            'orderby'                => array( 'date' => 'DESC' ),
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
        );

        if ( '' !== $normalized['search'] ) {
            $args['s'] = $normalized['search'];
        }

        $tax_query  = array();
        $meta_query = array();

        if ( '' !== $normalized['type'] ) {
            $tax_query[] = array(
                'taxonomy' => Post_Types::TAXONOMY_TYPE,
                'field'    => 'slug',
                'terms'    => $normalized['type'],
            );
        }

        if ( '' !== $normalized['audience'] ) {
            $tax_query[] = array(
                'taxonomy' => Post_Types::TAXONOMY_AUDIENCE,
                'field'    => 'slug',
                'terms'    => $normalized['audience'],
            );
        }

        if ( '' !== $normalized['series'] ) {
            $tax_query[] = array(
                'taxonomy' => Post_Types::TAXONOMY_SERIES,
                'field'    => 'slug',
                'terms'    => $normalized['series'],
            );
        }

        if ( ! empty( $tax_query ) ) {
            if ( count( $tax_query ) > 1 ) {
                $tax_query['relation'] = 'AND';
            }

            $args['tax_query'] = $tax_query;
        }

        if ( '' !== $normalized['start_date'] ) {
            $meta_query[] = array(
                'key'     => '_westin_event_date',
                'value'   => $normalized['start_date'],
                'compare' => '>=',
                'type'    => 'DATE',
            );
        }

        if ( '' !== $normalized['end_date'] ) {
            $meta_query[] = array(
                'key'     => '_westin_event_date',
                'value'   => $normalized['end_date'],
                'compare' => '<=',
                'type'    => 'DATE',
            );
        }

        if ( ! empty( $meta_query ) ) {
            $args['meta_query'] = $meta_query;
            $args['meta_key']   = '_westin_event_date';
            $args['orderby']    = 'meta_value';
            $args['order']      = 'ASC';
        }

        $events = new \WP_Query( $args );

        ob_start();

        if ( in_array( $context, array( 'shortcode', 'block' ), true ) ) {
            self::render_embedded_header( $normalized, $context );
        }

        printf(
            '<div class="westin-test-events-grid westin-test-events-grid--%s">',
            esc_attr( sanitize_html_class( $context ) )
        );

        if ( $events->have_posts() ) {
            while ( $events->have_posts() ) {
                $events->the_post();
                self::render_card( get_the_ID(), 'yes' === $normalized['show_excerpt'] );
            }

            wp_reset_postdata();
        } else {
            echo '<p>' . esc_html__( 'No events found.', 'westin-test-events' ) . '</p>';
        }

        echo '</div>';

        if ( in_array( $context, array( 'shortcode', 'block' ), true ) ) {
            echo '</section>';
        }

        $html = ob_get_clean();
        set_transient( $cache_key, $html, HOUR_IN_SECONDS );

        return $html;
    }

    public static function render_card( int $post_id, bool $show_excerpt = true ): void {
        $date      = get_post_meta( $post_id, '_westin_event_date', true );
        $time      = get_post_meta( $post_id, '_westin_event_start_time', true );
        $location  = get_post_meta( $post_id, '_westin_event_location', true );
        $speaker   = get_post_meta( $post_id, '_westin_event_speaker', true );
        $capacity  = absint( get_post_meta( $post_id, '_westin_event_capacity', true ) );
        $rsvp      = absint( get_post_meta( $post_id, '_westin_event_rsvp_count', true ) );
        $types     = get_the_terms( $post_id, Post_Types::TAXONOMY_TYPE );
        $audiences = get_the_terms( $post_id, Post_Types::TAXONOMY_AUDIENCE );
        ?>
        <article class="westin-test-event-card">
            <div class="westin-test-event-card__glow" aria-hidden="true"></div>
            <?php if ( has_post_thumbnail( $post_id ) ) : ?>
                <a class="westin-test-event-card__media-link" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" aria-hidden="true" tabindex="-1">
                    <div class="westin-test-event-card__media"><?php echo get_the_post_thumbnail( $post_id, 'large' ); ?></div>
                </a>
            <?php endif; ?>
            <div class="westin-test-event-card__inner">
                <div class="westin-test-event-card__eyebrow">
                    <?php if ( is_array( $types ) && ! empty( $types ) ) : ?>
                        <span class="westin-test-chip"><?php echo esc_html( implode( ' · ', wp_list_pluck( $types, 'name' ) ) ); ?></span>
                    <?php endif; ?>

                    <?php if ( is_array( $audiences ) && ! empty( $audiences ) ) : ?>
                        <span class="westin-test-chip westin-test-chip--light"><?php echo esc_html( implode( ' · ', wp_list_pluck( $audiences, 'name' ) ) ); ?></span>
                    <?php endif; ?>
                </div>

                <h3 class="westin-test-event-card__title">
                    <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a>
                </h3>

                <div class="westin-test-event-card__meta-grid">
                    <?php if ( $date ) : ?>
                        <div><span><?php esc_html_e( 'Date', 'westin-test-events' ); ?></span><strong><?php echo esc_html( self::format_date( $date ) ); ?></strong></div>
                    <?php endif; ?>
                    <?php if ( $time ) : ?>
                        <div><span><?php esc_html_e( 'Time', 'westin-test-events' ); ?></span><strong><?php echo esc_html( self::format_time( $time ) ); ?></strong></div>
                    <?php endif; ?>
                    <?php if ( $location ) : ?>
                        <div><span><?php esc_html_e( 'Venue', 'westin-test-events' ); ?></span><strong><?php echo esc_html( $location ); ?></strong></div>
                    <?php endif; ?>
                    <?php if ( $speaker ) : ?>
                        <div><span><?php esc_html_e( 'Speaker', 'westin-test-events' ); ?></span><strong><?php echo esc_html( $speaker ); ?></strong></div>
                    <?php endif; ?>
                </div>

                <?php if ( $show_excerpt ) : ?>
                    <div class="westin-test-event-card__excerpt">
                        <?php echo wp_kses_post( wpautop( wp_trim_words( get_post_field( 'post_content', $post_id ), 26 ) ) ); ?>
                    </div>
                <?php endif; ?>

                <div class="westin-test-event-card__footer">
                    <a class="westin-test-event-card__cta" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php esc_html_e( 'View event', 'westin-test-events' ); ?></a>
                    <span class="westin-test-event-card__stat"><?php echo esc_html( sprintf( _n( '%1$d RSVP', '%1$d RSVPs', $rsvp, 'westin-test-events' ), $rsvp ) ); ?></span>
                    <?php if ( $capacity > 0 ) : ?>
                        <span class="westin-test-event-card__stat"><?php echo esc_html( sprintf( __( '%d seats', 'westin-test-events' ), $capacity ) ); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </article>
        <?php
    }

    public static function apply_archive_filters( \WP_Query $query ): void {
        if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( Post_Types::POST_TYPE ) ) {
            return;
        }

        $meta_query = array();
        $tax_query  = array();

        $event_type = isset( $_GET['event_type'] ) ? sanitize_title( wp_unslash( $_GET['event_type'] ) ) : '';
        $audience   = isset( $_GET['event_audience'] ) ? sanitize_title( wp_unslash( $_GET['event_audience'] ) ) : '';
        $series     = isset( $_GET['event_series'] ) ? sanitize_title( wp_unslash( $_GET['event_series'] ) ) : '';
        $start_date = isset( $_GET['start_date'] ) ? Post_Types::sanitize_date( wp_unslash( $_GET['start_date'] ) ) : '';
        $end_date   = isset( $_GET['end_date'] ) ? Post_Types::sanitize_date( wp_unslash( $_GET['end_date'] ) ) : '';
        $search     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

        if ( $event_type ) {
            $tax_query[] = array(
                'taxonomy' => Post_Types::TAXONOMY_TYPE,
                'field'    => 'slug',
                'terms'    => $event_type,
            );
        }

        if ( $audience ) {
            $tax_query[] = array(
                'taxonomy' => Post_Types::TAXONOMY_AUDIENCE,
                'field'    => 'slug',
                'terms'    => $audience,
            );
        }

        if ( $series ) {
            $tax_query[] = array(
                'taxonomy' => Post_Types::TAXONOMY_SERIES,
                'field'    => 'slug',
                'terms'    => $series,
            );
        }

        if ( count( $tax_query ) > 1 ) {
            $tax_query['relation'] = 'AND';
        }

        if ( $start_date ) {
            $meta_query[] = array(
                'key'     => '_westin_event_date',
                'value'   => $start_date,
                'compare' => '>=',
                'type'    => 'DATE',
            );
        }

        if ( $end_date ) {
            $meta_query[] = array(
                'key'     => '_westin_event_date',
                'value'   => $end_date,
                'compare' => '<=',
                'type'    => 'DATE',
            );
        }

        if ( $search ) {
            $query->set( 's', $search );
        }

        if ( ! empty( $tax_query ) ) {
            $query->set( 'tax_query', $tax_query );
        }

        if ( ! empty( $meta_query ) ) {
            $query->set( 'meta_query', $meta_query );
            $query->set( 'meta_key', '_westin_event_date' );
            $query->set( 'orderby', 'meta_value' );
            $query->set( 'order', 'ASC' );
            return;
        }

        $query->set( 'orderby', 'date' );
        $query->set( 'order', 'DESC' );
    }

    public static function get_archive_hero_content(): array {
        $archive_description = is_post_type_archive( Post_Types::POST_TYPE ) ? get_the_archive_description() : '';

        $headline = __( 'Plan your next builder event with confidence.', 'westin-test-events' );
        $intro    = __( 'Explore builder summits, design workshops, networking nights, and client-facing events built for custom home teams, remodeling pros, and homeowners planning what comes next.', 'westin-test-events' );
        $support  = __( 'Use the filters to narrow by event type, audience, series, or date range so visitors can quickly find the sessions that match their role, timeline, and interests.', 'westin-test-events' );

        if ( ! empty( $archive_description ) ) {
            $support = wp_strip_all_tags( $archive_description );
        }

        return array(
            'eyebrow'      => __( 'Curated calendar', 'westin-test-events' ),
            'title'        => post_type_archive_title( '', false ) ?: __( 'Events', 'westin-test-events' ),
            'intro'        => $intro,
            'description'  => $support,
            'headline'     => $headline,
        );
    }

    private static function render_embedded_header( array $normalized, string $context ): void {
        $hero         = self::get_archive_hero_content();
        $total_events = absint( wp_count_posts( Post_Types::POST_TYPE )->publish ?? 0 );
        $action_url   = self::get_embedded_action_url();
        ?>
        <section class="westin-test-events-wrap westin-test-events-wrap--archive westin-test-events-wrap--embedded westin-test-events-wrap--<?php echo esc_attr( sanitize_html_class( $context ) ); ?>">
            <header class="westin-test-events-hero">
                <div class="westin-test-events-hero__copy">
                    <span class="westin-test-chip"><?php echo esc_html( $hero['eyebrow'] ); ?></span>
                    <h1><?php echo esc_html( $hero['title'] ); ?></h1>
                    <p><?php echo esc_html( $hero['intro'] ); ?></p>
                    <div class="westin-test-events-description"><p><?php echo esc_html( $hero['description'] ); ?></p></div>
                </div>
                <div class="westin-test-events-hero__panel">
                    <div class="westin-test-events-hero__stat"><?php echo esc_html( (string) $total_events ); ?></div>
                    <div class="westin-test-events-hero__label"><?php esc_html_e( 'Published events', 'westin-test-events' ); ?></div>
                </div>
            </header>

            <form class="westin-test-filter-form" method="get" action="<?php echo esc_url( $action_url ); ?>">
                <div>
                    <label for="westin-test-shortcode-search"><?php esc_html_e( 'Search', 'westin-test-events' ); ?></label>
                    <input type="search" id="westin-test-shortcode-search" name="s" value="<?php echo esc_attr( $normalized['search'] ); ?>" placeholder="<?php esc_attr_e( 'Search events', 'westin-test-events' ); ?>">
                </div>

                <div>
                    <label for="westin-test-shortcode-type"><?php esc_html_e( 'Event Type', 'westin-test-events' ); ?></label>
                    <select id="westin-test-shortcode-type" name="event_type">
                        <option value=""><?php esc_html_e( 'All event types', 'westin-test-events' ); ?></option>
                        <?php foreach ( get_terms( array( 'taxonomy' => Post_Types::TAXONOMY_TYPE, 'hide_empty' => false ) ) as $term ) : ?>
                            <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $normalized['type'], $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="westin-test-shortcode-audience"><?php esc_html_e( 'Audience', 'westin-test-events' ); ?></label>
                    <select id="westin-test-shortcode-audience" name="event_audience">
                        <option value=""><?php esc_html_e( 'All audiences', 'westin-test-events' ); ?></option>
                        <?php foreach ( get_terms( array( 'taxonomy' => Post_Types::TAXONOMY_AUDIENCE, 'hide_empty' => false ) ) as $term ) : ?>
                            <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $normalized['audience'], $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="westin-test-shortcode-series"><?php esc_html_e( 'Series', 'westin-test-events' ); ?></label>
                    <select id="westin-test-shortcode-series" name="event_series">
                        <option value=""><?php esc_html_e( 'All series', 'westin-test-events' ); ?></option>
                        <?php foreach ( get_terms( array( 'taxonomy' => Post_Types::TAXONOMY_SERIES, 'hide_empty' => false ) ) as $term ) : ?>
                            <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $normalized['series'], $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="westin-test-shortcode-start"><?php esc_html_e( 'Start Date', 'westin-test-events' ); ?></label>
                    <input type="date" id="westin-test-shortcode-start" name="start_date" value="<?php echo esc_attr( $normalized['start_date'] ); ?>">
                </div>

                <div>
                    <label for="westin-test-shortcode-end"><?php esc_html_e( 'End Date', 'westin-test-events' ); ?></label>
                    <input type="date" id="westin-test-shortcode-end" name="end_date" value="<?php echo esc_attr( $normalized['end_date'] ); ?>">
                </div>

                <div class="westin-test-filter-form__actions">
                    <button type="submit"><?php esc_html_e( 'Apply Filters', 'westin-test-events' ); ?></button>
                    <a class="westin-test-filter-reset" href="<?php echo esc_url( $action_url ); ?>"><?php esc_html_e( 'Reset', 'westin-test-events' ); ?></a>
                </div>
            </form>
        <?php
    }

    private static function get_embedded_action_url(): string {
        if ( is_singular() ) {
            return (string) get_permalink();
        }

        return (string) home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) );
    }

    public static function format_date( string $date ): string {
        $timestamp = strtotime( $date );

        if ( ! $timestamp ) {
            return $date;
        }

        return wp_date( get_option( 'date_format' ), $timestamp );
    }

    public static function format_time( string $time ): string {
        $timestamp = strtotime( $time );

        if ( ! $timestamp ) {
            return $time;
        }

        return wp_date( get_option( 'time_format' ), $timestamp );
    }
}
