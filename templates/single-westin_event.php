<?php
wp_enqueue_style( 'westin-test-events' );
wp_enqueue_script( 'westin-test-events' );

get_header();

while ( have_posts() ) :
    the_post();
    $date       = get_post_meta( get_the_ID(), '_westin_event_date', true );
    $time       = get_post_meta( get_the_ID(), '_westin_event_start_time', true );
    $location   = get_post_meta( get_the_ID(), '_westin_event_location', true );
    $address    = get_post_meta( get_the_ID(), '_westin_event_address', true );
    $speaker    = get_post_meta( get_the_ID(), '_westin_event_speaker', true );
    $capacity   = absint( get_post_meta( get_the_ID(), '_westin_event_capacity', true ) );
    $rsvp_count = absint( get_post_meta( get_the_ID(), '_westin_event_rsvp_count', true ) );
    $types      = get_the_terms( get_the_ID(), \WestinTest\Events\Post_Types::TAXONOMY_TYPE );
    $audiences  = get_the_terms( get_the_ID(), \WestinTest\Events\Post_Types::TAXONOMY_AUDIENCE );
    $series     = get_the_terms( get_the_ID(), \WestinTest\Events\Post_Types::TAXONOMY_SERIES );
    $hero_style = '';

    if ( has_post_thumbnail() ) {
        $hero_style = sprintf(
            ' style="background-image:linear-gradient(135deg, rgba(3, 7, 18, 0.82), rgba(30, 41, 59, 0.54)), url(%1$s);"',
            esc_url( get_the_post_thumbnail_url( get_the_ID(), 'full' ) )
        );
    }
    ?>
    <section class="westin-test-events-wrap westin-test-events-wrap--single">
        <article <?php post_class( 'westin-test-single-event westin-test-single-event--hero-header' ); ?>>
            <header class="westin-test-single-event__masthead"<?php echo $hero_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> >
                <div class="westin-test-single-event__masthead-inner">
                    <div class="westin-test-single-event__chips">
                        <?php foreach ( array_merge( is_array( $types ) ? wp_list_pluck( $types, 'name' ) : array(), is_array( $audiences ) ? wp_list_pluck( $audiences, 'name' ) : array(), is_array( $series ) ? wp_list_pluck( $series, 'name' ) : array() ) as $label ) : ?>
                            <span class="westin-test-chip"><?php echo esc_html( $label ); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <h1><?php the_title(); ?></h1>
                    <p class="westin-test-single-event__lede"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ?: wp_trim_words( get_the_content(), 30 ) ) ); ?></p>
                </div>
            </header>

            <div class="westin-test-single-event__layout westin-test-single-event__layout--hero-header">
                <div class="westin-test-single-event__content">
                    <div class="westin-test-single-event__body"><?php the_content(); ?></div>
                </div>

                <aside class="westin-test-single-event__sidebar">
                    <section class="westin-test-single-event__overview westin-test-single-event__overview--stacked">
                        <?php if ( $date ) : ?><div><span><?php esc_html_e( 'Date', 'westin-test-events' ); ?></span><strong><?php echo esc_html( \WestinTest\Events\Frontend::format_date( $date ) ); ?></strong></div><?php endif; ?>
                        <?php if ( $time ) : ?><div><span><?php esc_html_e( 'Start Time', 'westin-test-events' ); ?></span><strong><?php echo esc_html( \WestinTest\Events\Frontend::format_time( $time ) ); ?></strong></div><?php endif; ?>
                        <?php if ( $location ) : ?><div><span><?php esc_html_e( 'Location', 'westin-test-events' ); ?></span><strong><?php echo esc_html( $location ); ?></strong></div><?php endif; ?>
                        <?php if ( $speaker ) : ?><div><span><?php esc_html_e( 'Speaker', 'westin-test-events' ); ?></span><strong><?php echo esc_html( $speaker ); ?></strong></div><?php endif; ?>
                        <div><span><?php esc_html_e( 'RSVPs', 'westin-test-events' ); ?></span><strong><?php echo esc_html( (string) $rsvp_count ); ?></strong></div>
                        <?php if ( $capacity > 0 ) : ?><div><span><?php esc_html_e( 'Capacity', 'westin-test-events' ); ?></span><strong><?php echo esc_html( (string) $capacity ); ?></strong></div><?php endif; ?>
                    </section>

                    <?php if ( $address ) : ?>
                        <section class="westin-test-panel">
                            <h2><?php esc_html_e( 'Venue Address', 'westin-test-events' ); ?></h2>
                            <p><?php echo nl2br( esc_html( $address ) ); ?></p>
                        </section>
                    <?php endif; ?>

                    <section class="westin-test-panel">
                        <h2><?php esc_html_e( 'Reserve your seat', 'westin-test-events' ); ?></h2>
                        <p><?php esc_html_e( 'Submit the RSVP form and the site will log your interest instantly.', 'westin-test-events' ); ?></p>
                        <?php echo do_shortcode( '[westin_event_rsvp]' ); ?>
                    </section>
                </aside>
            </div>
        </article>
    </section>
<?php endwhile;

get_footer();
