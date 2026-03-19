# Westin Test Events

Westin Test Events is a custom WordPress plugin built for the audition brief. It adds a dedicated Events content type, three related taxonomies, richer event metadata, modern front-end templates, filtering, RSVP handling, REST support, WP-CLI seed data, translation files, and expanded unit test coverage.

## Included features

- Custom post type: `westin_event`
- Custom taxonomies:
  - `westin_event_type`
  - `westin_event_audience`
  - `westin_event_series`
- Four admin meta boxes with six event fields:
  - event date
  - start time
  - location
  - venue address
  - speaker / host
  - capacity
- Admin columns for date, location, taxonomies, speaker, and RSVP count
- RSVP admin tools, including a dedicated management screen and an attendee meta box on each event
- Creative single and archive templates
- Shared archive-style hero/filter layout for:
  - the native Events archive
  - `[westin_events]`
  - the dynamic Gutenberg listing block
- Single event hero header layout using the featured image as the page banner
- Front-end filtering by keyword, type, audience, series, and date range
- Shortcodes:
  - `[westin_events]`
  - `[westin_event_rsvp]`
- Email notifications for:
  - site administrators when events are published, updated, or receive a new RSVP
  - RSVP attendees when events they registered for are updated
- REST support for the event post type and RSVP submissions
- Translation-ready strings plus sample `.pot` and `.po` files
- WP-CLI sample data command
- PHPUnit test scaffolding with broader core coverage and edge-case coverage examples
- Bundled sample data file for manual testing

## Installation

1. Copy the `westin-test-events` folder into `wp-content/plugins/`.
2. Activate **Westin Test Events** from the WordPress admin.
3. Visit **Settings > Permalinks** and save once if rewrite rules need a refresh.
4. Optional: create test content with the WP-CLI seeder or use the bundled sample data file under `/sample-data/`.

## Usage

### Add and manage events
- Go to **Events > Add New**
- Fill in the title, main content, excerpt, and featured image
- Use the schedule, venue, speaker, and capacity meta boxes
- Assign one or more Event Types, Audiences, and Event Series terms
- Publish the event to make it appear in the archive, shortcode listing, and Gutenberg block output

### Show event listings inside a page
Use:

```text
[westin_events]
```

Optional shortcode attributes:

```text
[westin_events posts_per_page="6" type="conference" audience="builders" series="spring-series" show_excerpt="yes"]
```

### Gutenberg block
The plugin includes a dynamic block named **Westin Events Listing**.

- Add it from the block inserter
- Adjust posts per page, taxonomy filters, and excerpt visibility in the sidebar
- The block uses the same hero/filter/list layout as the shortcode and archive

### RSVP form
The single event template already includes the RSVP form. You can also place it manually on an event page with:

```text
[westin_event_rsvp]
```


### Manage RSVP attendees in admin
The plugin adds **Events → RSVPs** so editors can:
- review RSVP submissions across all events
- filter by event
- search by attendee name or email
- remove RSVP entries that were submitted by mistake

Each event edit screen also includes an **RSVP Attendees** meta box with the latest submissions for that event.

### Front-end archive filtering
The archive, shortcode output, and Gutenberg block support:
- keyword search
- event type filter
- audience filter
- series filter
- start date
- end date

Archive URL example:

```text
/events/?event_type=conference&event_audience=builders&event_series=spring-series&start_date=2026-03-01&end_date=2026-03-31&s=summit
```

## Sample data for testing

### Option 1: WP-CLI seeder
Create demo content with:

```bash
wp westin-test seed-events --count=5
```

### Option 2: Bundled sample data file
A manual sample dataset is included here:

```text
/sample-data/westin-test-events-sample-data.json
```

That file includes five realistic sample events with titles, excerpts, content, taxonomies, and event meta you can use for QA or manual entry.

## REST API

### Events CPT
The custom post type is exposed through the WordPress REST API because `show_in_rest` is enabled on `westin_event`.

Typical endpoint:

```text
GET /wp-json/wp/v2/events
```

### Custom RSVP endpoint

```text
POST /wp-json/westin-test/v1/rsvp
```

Payload example:

```json
{
  "event_id": 123,
  "name": "Jane Doe",
  "email": "jane@example.com"
}
```

## Notifications

When an event is published or updated, the plugin notifies the site administrator. If the event already has RSVP attendees stored, those attendees are also included in the update notification recipient list.

When a new RSVP is submitted, the site administrator receives a notification email with the attendee details. If mail delivery fails, the RSVP is still stored and counted so the visitor does not lose their place.

## Testing

This repo includes PHPUnit scaffolding under `/tests`.

The current test suite covers core plugin behavior such as:
- post type and taxonomy registration
- meta sanitization helpers
- shortcode rendering
- RSVP storage success and duplicate protection
- RSVP validation failures
- notification recipient list building
- notification payload generation
- RSVP attendee lookup and admin row building
- admin column and action link helpers
- cache version changes and cache key stability

Typical flow:

```bash
composer install
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
vendor/bin/phpunit
```

The exact bootstrap still depends on the local WordPress test environment.

## Notes on performance and security

- Event listing output is cached with a versioned transient strategy.
- Cache versioning is bumped whenever events or RSVP counts change.
- Event queries stay focused on the custom post type and relevant filters.
- Meta box saves and RSVP submissions use nonce verification.
- Public inputs are sanitized and validated before storage.
- The plugin keeps the event post type available in REST for external integrations.


## REST API Examples

Get all events:
GET /wp-json/wp/v2/events

Submit RSVP:
POST /wp-json/westin-test/v1/rsvp

Example payload:
{
  "event_id": 123,
  "name": "John Doe",
  "email": "john@example.com"
}


## Architecture Notes
- Modular class-based structure
- Separation of concerns (CPT, Meta, RSVP, Admin, Frontend)
- Transient caching with versioning
- REST-first design for extensibility
