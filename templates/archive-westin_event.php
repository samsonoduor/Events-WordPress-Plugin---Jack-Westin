<?php
wp_enqueue_style( 'westin-test-events' );
wp_enqueue_script( 'westin-test-events' );

get_header();
$hero = \WestinTest\Events\Frontend::get_archive_hero_content();
?>
<section class="westin-test-events-wrap westin-test-events-wrap--archive">
    <header class="westin-test-events-hero">
        <div class="westin-test-events-hero__copy">
            <span class="westin-test-chip"><?php echo esc_html( $hero['eyebrow'] ); ?></span>
            <h1><?php post_type_archive_title(); ?></h1>
            <p><?php echo esc_html( $hero['intro'] ); ?></p>
            <div class="westin-test-events-description"><p><?php echo esc_html( $hero['description'] ); ?></p></div>
        </div>
        <div class="westin-test-events-hero__panel">
            <div class="westin-test-events-hero__stat"><?php echo esc_html( wp_count_posts( \WestinTest\Events\Post_Types::POST_TYPE )->publish ); ?></div>
            <div class="westin-test-events-hero__label"><?php esc_html_e( 'Published events', 'westin-test-events' ); ?></div>
        </div>
    </header>

    <form class="westin-test-filter-form" method="get">
        <div>
            <label for="westin-test-event-search"><?php esc_html_e( 'Search', 'westin-test-events' ); ?></label>
            <input type="search" id="westin-test-event-search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search events', 'westin-test-events' ); ?>">
        </div>

        <div>
            <label for="westin-test-event-type"><?php esc_html_e( 'Event Type', 'westin-test-events' ); ?></label>
            <select id="westin-test-event-type" name="event_type">
                <option value=""><?php esc_html_e( 'All event types', 'westin-test-events' ); ?></option>
                <?php foreach ( get_terms( array( 'taxonomy' => \WestinTest\Events\Post_Types::TAXONOMY_TYPE, 'hide_empty' => false ) ) as $term ) : ?>
                    <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( isset( $_GET['event_type'] ) ? sanitize_title( wp_unslash( $_GET['event_type'] ) ) : '', $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="westin-test-event-audience"><?php esc_html_e( 'Audience', 'westin-test-events' ); ?></label>
            <select id="westin-test-event-audience" name="event_audience">
                <option value=""><?php esc_html_e( 'All audiences', 'westin-test-events' ); ?></option>
                <?php foreach ( get_terms( array( 'taxonomy' => \WestinTest\Events\Post_Types::TAXONOMY_AUDIENCE, 'hide_empty' => false ) ) as $term ) : ?>
                    <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( isset( $_GET['event_audience'] ) ? sanitize_title( wp_unslash( $_GET['event_audience'] ) ) : '', $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="westin-test-event-series"><?php esc_html_e( 'Series', 'westin-test-events' ); ?></label>
            <select id="westin-test-event-series" name="event_series">
                <option value=""><?php esc_html_e( 'All series', 'westin-test-events' ); ?></option>
                <?php foreach ( get_terms( array( 'taxonomy' => \WestinTest\Events\Post_Types::TAXONOMY_SERIES, 'hide_empty' => false ) ) as $term ) : ?>
                    <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( isset( $_GET['event_series'] ) ? sanitize_title( wp_unslash( $_GET['event_series'] ) ) : '', $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="westin-test-start-date"><?php esc_html_e( 'Start Date', 'westin-test-events' ); ?></label>
            <input type="date" id="westin-test-start-date" name="start_date" value="<?php echo esc_attr( isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '' ); ?>">
        </div>

        <div>
            <label for="westin-test-end-date"><?php esc_html_e( 'End Date', 'westin-test-events' ); ?></label>
            <input type="date" id="westin-test-end-date" name="end_date" value="<?php echo esc_attr( isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '' ); ?>">
        </div>

        <div class="westin-test-filter-form__actions">
            <button type="submit"><?php esc_html_e( 'Apply Filters', 'westin-test-events' ); ?></button>
            <a class="westin-test-filter-reset" href="<?php echo esc_url( get_post_type_archive_link( \WestinTest\Events\Post_Types::POST_TYPE ) ); ?>"><?php esc_html_e( 'Reset', 'westin-test-events' ); ?></a>
        </div>
    </form>

    <div class="westin-test-events-grid westin-test-events-grid--archive">
        <?php if ( have_posts() ) : ?>
            <?php while ( have_posts() ) : the_post(); ?>
                <?php \WestinTest\Events\Frontend::render_card( get_the_ID() ); ?>
            <?php endwhile; ?>
        <?php else : ?>
            <p><?php esc_html_e( 'No events matched your filters.', 'westin-test-events' ); ?></p>
        <?php endif; ?>
    </div>

    <?php the_posts_pagination(); ?>
</section>
<?php
get_footer();
