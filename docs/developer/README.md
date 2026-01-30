# Developer Documentation

## Data Structure

**Taxonomy:**
* Name: gatherpress_location
* Hierarchical: true
* Post types: gatherpress_event
* REST API: enabled
* URL structure: /events/in/{term}/{child-term}/
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

## Class Architecture

**Setup** (Singleton)
* Registers taxonomy and block
* Hooks into save_post_gatherpress_event (priority 20)
* Manages settings page
* Coordinates geocoding and hierarchy building
* Provides get_allowed_levels() method
* Localizes filter data to block editor
* Adds canonical URLs for single-child terms

**Geocoder** (Singleton)
* Handles Nominatim API communication
* Manages transient caching (1-hour duration)
* Parses API responses
* Maps countries to continents using WordPress i18n
* Sends site language to API for localized results
* Handles German-speaking regions specially
* Handles city-states (Berlin) with suburb fallback

**Builder** (Singleton)
* Creates taxonomy terms
* Establishes parent-child relationships
* Updates incorrect parent assignments
* Associates terms with events
* Uses sanitize_title() for proper slug generation
* Checks allowed levels before creating terms
* Applies filter before term insertion
* Uses country codes as slugs for countries

**Block_Renderer** (Singleton)
* Renders block on frontend
* Retrieves location terms
* Builds hierarchical paths
* Formats output with optional links
* Preserves whitespace in separator
* Accounts for allowed level range offset
* Validates post context (must be gatherpress_event)

## Code Examples

**Get all location terms for an event:**
```php
$terms = wp_get_object_terms(
    $event_id,
    'gatherpress_location',
    array( 'orderby' => 'parent', 'order' => 'ASC' )
);
```

**Query events in a specific country:**
```php
$events = new WP_Query( array(
    'post_type' => 'gatherpress_event',
    'tax_query' => array(
        array(
            'taxonomy' => 'gatherpress_location',
            'field'    => 'slug',
            'terms'    => 'de', // Country code as slug
        ),
    ),
) );
```

**Get hierarchical term path:**
```php
$term = get_term_by( 'slug', 'munich', 'gatherpress_location' );
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
add_filter( 'gatherpress_location_hierarchy_levels', function() {
    return [2, 4]; // Country, State, City only
} );
```

**Customize term attributes:**
```php
add_filter( 'gatherpress_location_hierarchy_term_args', function( $args ) {
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
            'taxonomy' => 'gatherpress_location',
            'field'    => 'slug',
            'terms'    => 'bavaria',
        ),
        array(
            'taxonomy' => 'gatherpress_location',
            'field'    => 'slug',
            'terms'    => 'saxony',
        ),
    ),
) );
```

## Hooks and Filters

**Actions:**
* `save_post_gatherpress_event` (priority 20) - Triggers geocoding
* `enqueue_block_editor_assets` - Localizes filter data
* `wp_head` (priority 1) - Adds canonical links

**Filters:**
* `gatherpress_location_hierarchy_levels` - Configure allowed levels
  - Default: [1, 6]
  - Return: [min_level, max_level]
  - Example: [2, 4] for Country to City
* `gatherpress_location_hierarchy_term_args` - Customize term attributes
  - Receives: ['name', 'slug', 'parent', 'taxonomy', 'level', 'location']
  - Return: Modified args array
  - Used for country code slugs
