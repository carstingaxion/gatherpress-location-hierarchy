=== GatherPress Venue Hierarchy ===

Contributors:      WordPress Telex
Tags:              block, gatherpress, venue, hierarchy, geocoding
Tested up to:      6.8
Stable tag:        0.1.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html
Adds hierarchical location taxonomy to GatherPress with automatic geocoding

== Description ==

This plugin enhances GatherPress by adding a new hierarchical "gatherpress-location" taxonomy. It automatically geocodes venue addresses using the Nominatim API and generates a structured hierarchy of geographic terms: country as top-level parent, state/region as child of country, and county/city as child of state.

Key features:

* Creates new hierarchical "gatherpress-location" taxonomy
* Works alongside existing GatherPress venue system
* Automatic geocoding via Nominatim API with one-hour caching
* Generates geographic hierarchy: Country > State > City
* Checks for existing terms before creation
* Admin settings for default geographic terms
* Support for German-speaking regions (DE, AT, CH, LU)
* Filter events by location, city, state, or country
* Custom block for displaying full location hierarchy as inline text
* Singleton pattern implementation
* Comprehensive error logging and validation

The plugin includes a custom Gutenberg block that displays the complete location hierarchy as a single inline text string, showing all parent and child location terms in one continuous paragraph line.

== Installation ==

1. Ensure GatherPress plugin is installed and activated
2. Upload the plugin files to the `/wp-content/plugins/gatherpress-venue-hierarchy` directory, or install the plugin through the WordPress plugins screen directly
3. Activate the plugin through the 'Plugins' screen in WordPress
4. Navigate to Settings > GatherPress Location to configure default geographic terms
5. Add the "Location Hierarchy Display" block to any post or page to show location information

== Frequently Asked Questions ==

= Does this require GatherPress? =

Yes, this is an add-on for GatherPress and requires the GatherPress plugin to be installed and activated.

= How does the geocoding work? =

The plugin uses the Nominatim API to geocode venue addresses. Results are cached as transients for one hour to minimize API calls.

= Can I set default locations? =

Yes, you can configure default country, state, and city terms in the plugin settings under Settings > GatherPress Location.

= What regions are supported? =

The plugin has enhanced support for German-speaking regions (Germany, Austria, Switzerland, Luxembourg) but works with any geographic location.

== Screenshots ==

1. Admin settings page for configuring default geographic terms
2. Location hierarchy block displaying inline location path
3. Event filtered by country, state, or city

== Changelog ==

= 0.1.0 =
* Initial release
* New hierarchical gatherpress-location taxonomy
* Nominatim API geocoding integration
* Geographic term hierarchy generation
* Admin settings panel
* Custom location hierarchy display block
* Caching and error handling

== Usage ==

After activation, the plugin creates a new "gatherpress-location" taxonomy. When creating or editing venues in GatherPress:

1. Enter the venue address
2. The plugin automatically geocodes and creates hierarchical location terms
3. Use the admin settings to set default geographic locations
4. Add the "Location Hierarchy Display" block to show location information
5. Filter events by country, state, or city in both editor and frontend

The location hierarchy block displays the complete path as inline text, for example: "Germany > Bavaria > Munich > Conference Center"