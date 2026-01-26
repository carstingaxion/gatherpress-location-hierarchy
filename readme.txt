=== GatherPress Venue Hierarchy ===

Contributors:      WordPress Telex
Tags:              block, gatherpress, venue, hierarchy, geocoding, location, events
Tested up to:      6.8
Stable tag:        0.1.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html
Requires Plugins:  gatherpress

Automatically organizes GatherPress event locations into a hierarchical taxonomy system using geocoding, with a customizable display block.

== Description ==

GatherPress Venue Hierarchy is a powerful add-on for the GatherPress events plugin that automatically transforms venue addresses into structured, hierarchical geographic data. It enables you to organize and filter events by continent, country, state/region, and city, while providing flexible display options for showing location information on your site.

= What Does This Plugin Do? =

The plugin automatically:

* **Geocodes venue addresses** - When you save a GatherPress event with a venue address, the plugin queries the Nominatim (OpenStreetMap) API to extract detailed geographic information
* **Creates hierarchical location terms** - Builds a five-level taxonomy structure: Continent > Country > State/Region > City, with proper parent-child relationships
* **Associates locations with events** - Automatically tags events with all relevant location terms from the hierarchy
* **Provides a display block** - Includes a Gutenberg block for showing location hierarchies with customizable level filtering and optional links
* **Caches API responses** - Stores geocoding results for one hour to minimize API calls and improve performance

= How Does It Work? =

**Technical Overview:**

1. **Event Creation/Update** - When you save a GatherPress event, the plugin retrieves the venue information from GatherPress's core Event class
2. **Address Geocoding** - The venue's full address is sent to the Nominatim API, which returns detailed address components including coordinates and administrative regions
3. **Continent Mapping** - Since Nominatim doesn't provide continent data, the plugin uses an internal country-to-continent mapping with WordPress's core translations
4. **Hierarchy Building** - Geographic terms are created in hierarchical order (continent first, then country, state, city), with each level properly linked to its parent
5. **Term Association** - All created terms are associated with the event, allowing filtering at any geographic level
6. **Duplicate Prevention** - The plugin checks for existing terms before creating new ones and validates parent relationships to maintain data integrity

**Key Components:**

* **Custom Taxonomy** - Creates "gatherpress-location" taxonomy (separate from GatherPress's venue system)
* **Geocoder Class** - Handles API communication, response parsing, and caching using WordPress transients
* **Hierarchy Builder** - Manages term creation with proper parent-child relationships and duplicate detection
* **Display Block** - Gutenberg block with dual-handle range control for selecting which hierarchy levels to show

= Who Should Use This Plugin? =

This plugin is ideal for:

**Event Organizers:**
* Running multi-city or international event series
* Need to organize events by geographic region
* Want visitors to filter events by location
* Display event locations in a consistent, structured format

**Meetup and Community Groups:**
* Coordinating events across multiple cities or regions
* Managing regional chapters or local groups
* Providing location-based event discovery

**Conference and Festival Organizers:**
* Hosting events in various cities throughout the year
* Need to showcase event distribution geographically
* Want to help attendees find events near them

**Educational Institutions:**
* Organizing workshops or lectures across campuses
* Managing events in different buildings or locations
* Coordinating regional educational programs

**WordPress Developers:**
* Building event websites with advanced location features
* Need programmatic access to hierarchical location data
* Want to create custom location-based queries

= Features =

**Automatic Geocoding:**
* Nominatim OpenStreetMap API integration
* One-hour caching to minimize API calls
* Enhanced support for German-speaking regions (DE, AT, CH, LU)
* Comprehensive global coverage

**Hierarchical Organization:**
* Five-level hierarchy: Continent > Country > State > City > (optional) Venue
* Proper parent-child term relationships
* Maintains data integrity with duplicate detection
* Updates incorrect parent relationships automatically

**Flexible Display Block:**
* Dual-handle range control for selecting hierarchy levels
* Show entire hierarchy or specific subsets (e.g., only Country through City)
* Optional clickable links to location archive pages
* Optional venue name display with link to venue post
* Full WordPress block editor integration (alignment, colors, spacing)

**WordPress Integration:**
* Native taxonomy system for compatibility with all WordPress queries
* REST API support for headless WordPress setups
* Proper internationalization with translated continent names
* Admin column showing location hierarchy for events
* Standard WordPress archive pages for each location term

**Developer-Friendly:**
* Clean, well-documented code following WordPress coding standards
* Singleton pattern implementation for performance
* Extensive PHPStan-compatible type hints and docblocks
* Filter and action hooks for customization
* All geographic data accessible via standard WordPress taxonomy functions

= Use Cases =

**Example 1: Multi-City Tech Meetup**
A technology meetup group organizes events in Munich, Berlin, and Vienna. The plugin automatically creates:
* Europe (Continent)
  * Germany > Bavaria > Munich
  * Germany > Berlin > Berlin
  * Austria > Vienna > Vienna

Visitors can filter to see all European events, all German events, or events in specific cities.

**Example 2: International Conference Series**
A conference runs events globally. The display block shows "North America > United States > California > San Francisco" for the SF event, while the Tokyo event shows "Asia > Japan > Tokyo > Tokyo". Attendees can browse by continent to find events in their region.

**Example 3: Regional Workshop Network**
An organization runs workshops across Bavaria. The block displays only "Bavaria > Munich" (filtering out Continent and Country levels) for a clean, focused location display.

== Installation ==

= Requirements =

* WordPress 6.0 or higher
* PHP 7.4 or higher
* GatherPress plugin installed and activated

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins > Add New
3. Search for "GatherPress Venue Hierarchy"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin zip file
2. Upload the contents to the `/wp-content/plugins/gatherpress-venue-hierarchy` directory
3. Activate the plugin through the 'Plugins' menu in WordPress

= After Installation =

1. Navigate to Settings > GatherPress Location to configure default geographic terms (optional)
2. Create or edit a GatherPress event with a venue address
3. Save the event - location hierarchy will be generated automatically
4. Add the "Location Hierarchy Display" block to your event template or any post/page
5. Customize the block settings to control which levels display

== Frequently Asked Questions ==

= Does this require GatherPress? =

Yes, this is an add-on specifically designed for GatherPress. It requires GatherPress to be installed and activated to function. The plugin integrates with GatherPress's venue system to extract address information.

= How does the geocoding work? =

The plugin uses the Nominatim API from OpenStreetMap, a free and open-source geocoding service. When you save an event with a venue address, the plugin sends the full address to Nominatim, which returns detailed geographic information including address components and coordinates. Results are cached as WordPress transients for one hour to minimize API calls and improve performance.

= Is the Nominatim API free? =

Yes, Nominatim is completely free and open-source. However, please review OpenStreetMap's usage policy. For high-volume sites, consider setting up your own Nominatim instance or using a commercial geocoding service via custom code.

= What happens if geocoding fails? =

If the Nominatim API is unavailable or returns no results, the event will simply not have location terms associated with it. You can manually add location terms through the WordPress admin interface, or the plugin will automatically retry geocoding the next time you save the event.

= Can I manually edit location terms? =

Yes! The location terms are standard WordPress taxonomy terms. You can add, edit, or delete them through the WordPress admin (they appear in the sidebar menu). However, if you delete all location terms from an event and then save the event again, the plugin will automatically recreate them via geocoding.

= Can I set default locations? =

Yes, navigate to Settings > GatherPress Location to configure default continent, country, state, and city terms. These defaults are available for reference but the plugin primarily relies on geocoding actual venue addresses.

= What regions are supported? =

The plugin works with any geographic location worldwide. It has enhanced support for German-speaking regions (Germany, Austria, Switzerland, Luxembourg) with specific handling for their administrative structure (Bundesländer/Cantons), but fully supports all countries and regions returned by Nominatim.

= Does this replace GatherPress's venue system? =

No, this plugin works alongside GatherPress's existing venue system. It creates a separate "gatherpress-location" taxonomy that organizes events geographically, while GatherPress continues to manage detailed venue information (address, phone, website, etc.). Think of it as adding geographic organization to your existing venue data.

= Can I display the venue name in the block? =

Yes! The block includes a "Show venue" toggle that displays the GatherPress venue name at the end of the location hierarchy (e.g., "Europe > Germany > Bavaria > Munich > Conference Center"). The venue can be displayed as plain text or as a clickable link to the venue post, depending on your "Enable term links" setting.

= How do I show only certain hierarchy levels? =

The block includes a dual-handle range control that lets you select which levels to display. For example:
* Levels 1-5: Show full hierarchy (Continent through City)
* Levels 2-3: Show only Country and State
* Levels 3-3: Show only State
* Levels 1-2: Show Continent and Country

This is useful for focusing on relevant geographic information based on your event scope.

= Can I link the location terms to archive pages? =

Yes, enable the "Enable term links" toggle in the block settings. Each location term will become a clickable link to its WordPress archive page, where visitors can see all events in that location. Standard WordPress taxonomy archive pages are automatically created for each location term.

= How do I filter events by location? =

Since location data is stored as a standard WordPress taxonomy, you can:
* Use WordPress's built-in taxonomy queries
* Filter in the admin with the location column
* Create custom queries using `WP_Query` with `tax_query`
* Use WordPress archive URLs (e.g., `/location/europe/germany/`)
* Build custom filtering interfaces with WordPress REST API

= Does this work with block themes? =

Yes, the plugin is fully compatible with block themes and the block editor. The display block follows WordPress block standards and supports all standard block features (alignment, colors, spacing, etc.).

= Is the plugin translation-ready? =

Yes, the plugin uses WordPress internationalization best practices. Continent names use WordPress core translations, and all plugin strings are translatable. The text domain is 'gatherpress-venue-hierarchy'.

= Can I customize the block styling? =

Yes, the block supports WordPress's color system (text, background, and link colors) and standard block features. You can also add custom CSS targeting the `.wp-block-telex-block-gatherpress-venue-hierarchy` class.

= Does this affect performance? =

The plugin is designed with performance in mind:
* API responses are cached for one hour
* Singleton pattern prevents duplicate queries
* Only geocodes when events are saved, not on page load
* Uses standard WordPress taxonomy queries (fast and optimized)
* No frontend JavaScript required

== Screenshots ==

1. Admin settings page for configuring default geographic terms
2. Event editor showing the Location Hierarchy Display block with level range control
3. Location Hierarchy Display block with dual-handle range slider for selecting levels
4. Frontend display of location hierarchy as inline text
5. Location taxonomy in admin sidebar menu showing hierarchical structure
6. Event list in admin with location hierarchy column
7. Block inspector controls for links and venue display options

== Changelog ==

= 0.1.0 =
* Initial release
* New hierarchical gatherpress-location taxonomy with five levels (continent through city)
* Nominatim OpenStreetMap API integration for automatic geocoding
* Geographic term hierarchy generation with proper parent-child relationships
* One-hour transient caching for API responses
* Admin settings panel for default geographic terms
* Custom Gutenberg block for displaying location hierarchies
* Dual-handle range control for selecting hierarchy levels to display
* Optional clickable links to location archive pages
* Optional venue name display with link support
* Enhanced support for German-speaking regions
* Comprehensive error handling and logging
* Full WordPress color support in block
* REST API integration
* PHPStan-compatible type hints and extensive documentation
* Singleton pattern implementation for performance
* Translation-ready with WordPress core continent translations

== Upgrade Notice ==

= 0.1.0 =
Initial release of GatherPress Venue Hierarchy. Requires GatherPress plugin to be installed and activated.

== Developer Documentation ==

= Programmatic Access =

Access location data using standard WordPress taxonomy functions:

```php
// Get all location terms for an event
$locations = wp_get_object_terms( $event_id, 'gatherpress-location' );

// Query events in a specific country
$events = new WP_Query( array(
    'post_type' => 'gatherpress_event',
    'tax_query' => array(
        array(
            'taxonomy' => 'gatherpress-location',
            'field'    => 'slug',
            'terms'    => 'germany',
        ),
    ),
) );
```

= Filters and Hooks =

The plugin uses standard WordPress hooks. Future versions may add custom filters.

= Code Architecture =

* **Main Plugin Class** - `GatherPress_Venue_Hierarchy` (Singleton) - Coordinates all functionality
* **Geocoder Class** - `GatherPress_Venue_Geocoder` (Singleton) - Handles API communication
* **Hierarchy Builder Class** - `GatherPress_Venue_Hierarchy_Builder` (Singleton) - Manages term creation
* **Block Renderer Class** - `GatherPress_Venue_Hierarchy_Block_Renderer` (Singleton) - Handles frontend display

= Contributing =

This plugin is open source and welcomes contributions. Please follow WordPress coding standards and include PHPStan-compatible type hints and comprehensive docblocks.

== Privacy ==

This plugin communicates with the Nominatim API (OpenStreetMap) to geocode venue addresses. When you save an event with a venue address, that address is sent to Nominatim's servers. Please review OpenStreetMap's privacy policy. No personal data is sent to external services - only venue addresses from your events.

Geocoding results are cached locally in your WordPress database using WordPress transients. No data is sent to any service other than Nominatim, and only when explicitly saving an event with a venue address.