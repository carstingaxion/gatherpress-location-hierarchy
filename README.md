# GatherPress Venue Hierarchy

**Contributors:**      carstenbach & WordPress Telex  
**Tags:**              block, gatherpress, venue, hierarchy, geocoding, location, events  
**Tested up to:**      6.8  
**Stable tag:**        0.1.0  
**License:**           GPLv2 or later  
**License URI:**       https://www.gnu.org/licenses/gpl-2.0.html  
**Requires Plugins:**  gatherpress  

Automatically creates hierarchical location taxonomy for GatherPress events using geocoded address data.

## Description

This plugin extends GatherPress by adding a hierarchical location taxonomy. When an event is saved, the plugin geocodes the venue address and creates taxonomy terms organized by continent, country, state, city, street, and street number. A Gutenberg block displays these hierarchies with configurable level filtering and canonical URL handling.

### What This Plugin Does

* Creates a custom hierarchical taxonomy "gatherpress-location"
* Geocodes venue addresses using the Nominatim (OpenStreetMap) API
* Automatically generates taxonomy terms in 7 levels: Continent > Country > State > City > Street > Street+Number
* Establishes parent-child relationships between terms
* Associates created terms with events
* Provides a Gutenberg block for displaying location hierarchies
* Caches API responses for 1 hour using WordPress transients
* Generates canonical URLs for taxonomy archives with single child terms
* Supports configurable hierarchy level filtering via WordPress filter
* Provides extensibility for customizing term attributes before creation

### Technical Implementation

**Geocoding Process:**

1. When a GatherPress event is saved (priority 20, after GatherPress core processes at priority 10), the plugin retrieves venue information via GatherPress\Core\Event::get_venue_information()
2. The full address is sent to Nominatim API (https://nominatim.openstreetmap.org/search) with format=json, addressdetails=1, and site language
3. API response includes address components: house_number, road/street, city/town/village, state/region/province, country, country_code
4. Continent is derived from country_code using an internal mapping with WordPress core translations
5. Results are cached as transients with key format: gpvh_geocode_{md5(address)}
6. Cache duration: 3600 seconds (1 hour)
7. Only geocodes when location terms are missing (checks wp_get_object_terms)

**Hierarchy Building:**

1. Terms are created in top-down order: Continent → Country → State → City → Street → Street+Number
2. Each term stores its parent's term_id, creating a parent-child chain
3. Street number terms combine street name and number (e.g., "Main St 123") to avoid numerous single-digit terms
4. Before creating a term, the plugin checks if it exists by slug (not name)
5. Uses sanitize_title() for proper slug generation (handles ß→ss, accents→ascii, special characters)
6. If a term exists with incorrect parent, the plugin updates the parent relationship
7. All term IDs are associated with the event using wp_set_object_terms()
8. Respects hierarchy level filter to only create terms within configured range
9. Applies 'gatherpress_venue_hierarchy_term_args' filter before term insertion
10. Country terms use country_code as slug via filter

**Special Handling:**

* German-speaking regions (DE, AT, CH, LU): Uses 'state' field directly for Bundesland/Canton
* City-states (e.g., Berlin where state field is empty):
  - Uses city name as state level
  - Uses suburb (fallback: borough) as city level
  - Creates hierarchy: Europe > Germany > Berlin > Prenzlauer Berg > Street > Number
* Other countries: Falls back through state → region → province for administrative divisions
* City extraction: city → town → village → county (urban to rural priority)
* Street extraction: road → street → pedestrian (common field name variations)
* Slug generation: Uses remove_accents() with locale parameter for consistent transliteration

**Canonical URL Handling:**

* Adds canonical link tags to taxonomy archive pages when a term has only one child
* Points to child term's archive to consolidate SEO value and prevent duplicate content
* Creates canonical chains: grandparent → parent → child (leaf)
* Runs on wp_head hook (priority 1) for early placement in <head>
* Only affects location taxonomy archives

**Hierarchy Level Filtering:**

* WordPress filter: 'gatherpress_venue_hierarchy_levels'
* Default range: [1, 7] (all levels)
* Example: [2, 4] restricts to Country, State, City only
* Affects both term creation and block display
* Level mapping:
  - 1 = Continent
  - 2 = Country
  - 3 = State
  - 4 = City
  - 5 = Street
  - 6 = Street Number
* Filter data passed to block editor via wp_localize_script()

### Display Block Features

**Hierarchy Level Control:**

* Dual-handle range control for selecting start and end levels
* 7 levels available: Continent, Country, State, City, Street, Number
* Staggered label layout (alternating top/bottom rows) for readability
* Real-time preview of selected range
* Respects configured allowed level range from filter
* Adjusts control bounds dynamically based on filter settings

**Display Options:**

* Customizable separator between terms (default: " > ")
* Preserves whitespace in separator (uses wp_kses_post, not sanitize_text_field)
* Optional term links to taxonomy archive pages
* Optional venue display at end of hierarchy
* Venue links to GatherPress venue post when enabled
* Venue respects term link setting (links if enabled, plain text if disabled)
* Full WordPress block editor support (alignment, colors, spacing, border, typography)

**Context Awareness:**

* Works inside single event posts
* Works inside query loops querying for events
* Uses useSelect hook for reactive term and venue data
* Automatically updates when event is saved
* Shows placeholder text in query loop contexts when no terms available

**Implementation Details:**

* Editor: Uses useSelect hook to query location terms and venue taxonomy
* Frontend: Singleton renderer class (GatherPress_Venue_Hierarchy_Block_Renderer)
* Term retrieval: Ordered by parent relationship to maintain hierarchy
* Path building: Identifies leaf terms (deepest in hierarchy), builds paths by traversing parents
* Level filtering: Uses array_slice on complete paths based on startLevel/endLevel
* Accounts for allowed level range offset when filtering paths

### Use Cases

**Multi-Location Event Series:**
Organizations running events in multiple cities can filter by geographic level. Events are queryable at any hierarchy level using standard WordPress taxonomy queries.

**Regional Event Management:**
Local chapters or regional groups can organize events by state or city while maintaining connection to parent geographic regions.

**International Event Coordination:**
Global organizations can display continent-level groupings while drilling down to specific cities and venues.

**Restricted Hierarchy Levels:**
Sites can configure which levels to use (e.g., country to city only) via WordPress filter, avoiding unnecessary detail or handling edge cases.

## Installation

### Requirements

* WordPress 6.0 or higher
* PHP 7.4 or higher
* GatherPress plugin installed and activated

### Installation Steps

1. Upload plugin files to `/wp-content/plugins/gatherpress-venue-hierarchy/`
2. Activate the plugin through the WordPress Plugins menu
3. Plugin registers taxonomy and block automatically on activation
4. Taxonomy is registered at priority 5 (early) to prevent rewrite rule conflicts

### Configuration

1. Navigate to Settings > GatherPress Location
2. Configure default geographic terms (optional)
3. Create or edit GatherPress events with venue addresses
4. Location terms are generated automatically on save
5. Add "Location Hierarchy Display" block to templates or posts

### Optional Filter Configuration

Restrict hierarchy levels by adding a filter to your theme's functions.php:

```php
add_filter( 'gatherpress_venue_hierarchy_levels', function() {
    return [2, 4]; // Only Country, State, City
} );
```

### Optional Term Customization

Customize term attributes before creation:

```php
add_filter( 'gatherpress_venue_hierarchy_term_args', function( $args ) {
    // Countries use country code as slug (already implemented by default)
    if ( 2 === $args['level'] && ! empty( $args['location']['country_code'] ) ) {
        $args['slug'] = $args['location']['country_code'];
    }
    return $args;
} );
```

## Frequently Asked Questions

### How does geocoding work?

The plugin sends venue addresses to Nominatim API (OpenStreetMap) including the site language for localized results. Response includes coordinates and address components. Results are cached locally in the WordPress database using transients (1-hour expiration). Cache key format: `gpvh_geocode_{md5(address)}`.

### What happens if geocoding fails?

Events without location terms can have terms added manually through WordPress admin. Geocoding will retry automatically when the event is saved again.

### Can I manually edit location terms?

Yes. Terms are standard WordPress taxonomy terms accessible through admin interface. Manual edits persist unless geocoding recreates terms (occurs when all terms are deleted and event is resaved).

### What is the API usage policy?

Nominatim is free but has usage limits. Review OpenStreetMap's Nominatim Usage Policy. For high-volume sites, consider:
* Self-hosted Nominatim instance
* Commercial geocoding service
* Extended cache duration (filter the cache_duration property)

### What regions are supported?

All regions returned by Nominatim are supported. Enhanced handling for German-speaking regions (DE, AT, CH, LU) uses specific administrative structure (Bundesländer/Cantons). City-states like Berlin receive special handling to avoid duplicate entries.

### How does this relate to GatherPress venues?

This plugin creates a separate "gatherpress-location" taxonomy. GatherPress's venue system (venue post type and `_gatherpress_venue` taxonomy) remains unchanged. The location taxonomy provides geographic organization while GatherPress manages venue details (address, phone, website).

### How do I query events by location?

Use standard WordPress taxonomy queries:

```php
$args = array(
    'post_type' => 'gatherpress_event',
    'tax_query' => array(
        array(
            'taxonomy' => 'gatherpress-location',
            'field'    => 'slug',
            'terms'    => 'bavaria',
        ),
    ),
);
$query = new WP_Query( $args );
```

### How do I display only specific hierarchy levels?

Use the block's dual-range control:
* Continent only: Levels 1-1
* Country through City: Levels 2-4
* City and Street: Levels 4-5
* Full hierarchy: Levels 1-7 (or use default 1-999)

Or configure globally via filter:

```php
add_filter( 'gatherpress_venue_hierarchy_levels', function() {
    return [1, 4]; // Continent to City only
} );
```

### Can I customize block appearance?

The block supports WordPress color controls (text, background, link), typography, spacing, and border settings. Custom CSS can target `.wp-block-telex-block-gatherpress-venue-hierarchy` class.

### Does this affect performance?

Performance considerations:
* API calls only occur when events are saved (not on page load)
* 1-hour caching reduces API requests
* Singleton pattern prevents duplicate queries
* Standard WordPress taxonomy queries (optimized)
* No frontend JavaScript required
* Canonical URL generation adds minimal overhead (single query per taxonomy archive)

### What are canonical URLs and why are they used?

Canonical URLs tell search engines which page is the "main" version when multiple URLs show identical content. When a location term has only one child, both taxonomy archives display the same events (duplicate content). The plugin adds a canonical link tag pointing to the child's archive, consolidating SEO value and preventing search engine confusion.

Example: If Europe has only Germany as child, /location/europe/ shows canonical tag pointing to /location/europe/germany/.

### How does slug generation work?

Term slugs are generated using WordPress's remove_accents() with locale parameter, then sanitize_title(). This ensures:
* German ß becomes "ss"
* French accents are removed (é→e, è→e, à→a)
* Special characters are converted to hyphens
* Unsafe characters are stripped
* Consistent transliteration across languages
* Countries use country_code as slug (via filter)

### How does the hierarchy level filter work?

The filter restricts which levels are processed:

**In PHP (term creation):**
* Checks allowed range before creating each term
* Skips levels outside range
* Tracks last valid parent for proper relationships
* Example: [2,4] creates Country→State→City, skips Continent and Street levels

**In JavaScript (block display):**
* Filter data passed via wp_localize_script()
* Dual-range control bounds adjusted to filter range
* Path filtering accounts for offset (minLevel)
* Calculates array indices from absolute levels

## Changelog

### 0.1.0
* Initial release
* Hierarchical gatherpress-location taxonomy (7 levels)
* Nominatim API integration with 1-hour caching and site language support
* Automatic term creation with parent relationships
* Country-to-continent mapping with WordPress i18n
* Gutenberg block with dual-range level control
* Customizable separator between terms with whitespace preservation
* Optional term links to archive pages
* Optional venue display with link support
* Enhanced German-speaking region support
* City-state handling (e.g., Berlin) with suburb fallback
* Street and street number handling
* Combined street+number display option
* Staggered label layout for 7-level range control
* REST API integration
* PHPStan-compatible type hints
* Comprehensive docblocks with What/Why/How explanations
* Singleton pattern implementation
* Proper slug generation with sanitize_title() and remove_accents()
* Hierarchy level filtering via WordPress filter
* Term attribute customization filter
* Country codes as term slugs
* Canonical URL handling for taxonomy archives with single child
* Context-aware block (works in query loops)
* Automatic editor updates when event saved

## Developer Documentation

### Data Structure

**Taxonomy:**
* Name: gatherpress-location
* Hierarchical: true
* Post types: gatherpress_event
* REST API: enabled
* URL structure: /location/{term}/{child-term}/
* Rewrite: hierarchical (pretty URLs)
* Admin column: visible
* Default ordering: by parent (maintains hierarchy)

**Location Data Array:**
```php
array(
    'continent'      => string, // e.g., "Europe" (translated)
    'country'        => string, // e.g., "Germany"
    'country_code'   => string, // e.g., "de" (lowercase)
    'state'          => string, // e.g., "Bavaria" (or city name for city-states)
    'city'           => string, // e.g., "Munich" (or suburb for city-states)
    'street'         => string, // e.g., "Marienplatz"
    'street_number'  => string, // e.g., "1"
)
```

### Class Architecture

**GatherPress_Venue_Hierarchy** (Singleton)
* Registers taxonomy and block
* Hooks into save_post_gatherpress_event (priority 20)
* Manages settings page
* Coordinates geocoding and hierarchy building
* Provides get_allowed_levels() method
* Localizes filter data to block editor
* Adds canonical URLs for single-child terms

**GatherPress_Venue_Geocoder** (Singleton)
* Handles Nominatim API communication
* Manages transient caching (1-hour duration)
* Parses API responses
* Maps countries to continents using WordPress i18n
* Sends site language to API for localized results
* Handles German-speaking regions specially
* Handles city-states (Berlin) with suburb fallback

**GatherPress_Venue_Hierarchy_Builder** (Singleton)
* Creates taxonomy terms
* Establishes parent-child relationships
* Updates incorrect parent assignments
* Associates terms with events
* Uses sanitize_title() for proper slug generation
* Checks allowed levels before creating terms
* Applies filter before term insertion
* Uses country codes as slugs for countries

**GatherPress_Venue_Hierarchy_Block_Renderer** (Singleton)
* Renders block on frontend
* Retrieves location terms
* Builds hierarchical paths
* Formats output with optional links
* Preserves whitespace in separator
* Accounts for allowed level range offset
* Validates post context (must be gatherpress_event)

### Code Examples

**Get all location terms for an event:**
```php
$terms = wp_get_object_terms(
    $event_id,
    'gatherpress-location',
    array( 'orderby' => 'parent', 'order' => 'ASC' )
);
```

**Query events in a specific country:**
```php
$events = new WP_Query( array(
    'post_type' => 'gatherpress_event',
    'tax_query' => array(
        array(
            'taxonomy' => 'gatherpress-location',
            'field'    => 'slug',
            'terms'    => 'de', // Country code as slug
        ),
    ),
) );
```

**Get hierarchical term path:**
```php
$term = get_term_by( 'slug', 'munich', 'gatherpress-location' );
$path = array();
$current = $term;
while ( $current ) {
    array_unshift( $path, $current->name );
    $current = $current->parent ? get_term( $current->parent ) : null;
}
// Result: ['Europe', 'Germany', 'Bavaria', 'Munich']
```

**Configure hierarchy levels:**
```php
add_filter( 'gatherpress_venue_hierarchy_levels', function() {
    return [2, 4]; // Country, State, City only
} );
```

**Customize term attributes:**
```php
add_filter( 'gatherpress_venue_hierarchy_term_args', function( $args ) {
    // Example: Add custom meta or modify name
    if ( 2 === $args['level'] ) { // Country level
        // Country already uses country_code as slug by default
        $args['slug'] = $args['location']['country_code'];
    }
    return $args;
} );
```

**Query events by multiple locations:**
```php
$events = new WP_Query( array(
    'post_type' => 'gatherpress_event',
    'tax_query' => array(
        'relation' => 'OR',
        array(
            'taxonomy' => 'gatherpress-location',
            'field'    => 'slug',
            'terms'    => 'bavaria',
        ),
        array(
            'taxonomy' => 'gatherpress-location',
            'field'    => 'slug',
            'terms'    => 'saxony',
        ),
    ),
) );
```

### Hooks and Filters

**Actions:**
* `save_post_gatherpress_event` (priority 20) - Triggers geocoding
* `enqueue_block_editor_assets` - Localizes filter data
* `wp_head` (priority 1) - Adds canonical links

**Filters:**
* `gatherpress_venue_hierarchy_levels` - Configure allowed levels
  - Default: [1, 7]
  - Return: [min_level, max_level]
  - Example: [2, 4] for Country to City
* `gatherpress_venue_hierarchy_term_args` - Customize term attributes
  - Receives: ['name', 'slug', 'parent', 'taxonomy', 'level', 'location']
  - Return: Modified args array
  - Used for country code slugs


## Privacy

This plugin sends venue addresses to Nominatim API (https://nominatim.openstreetmap.org) when events are saved. The site language is also sent for localized results. Only venue addresses and language codes are transmitted. No user data or personal information is sent.

Geocoding results are cached locally in the WordPress database using transients (1-hour expiration). No data is sent to services other than Nominatim.

Review OpenStreetMap's privacy policy at: https://wiki.osmfoundation.org/wiki/Privacy_Policy

## Credits

This plugin uses the Nominatim API provided by OpenStreetMap Foundation. Nominatim is licensed under GPL v2.