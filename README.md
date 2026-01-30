# GatherPress Location Hierarchy

**Contributors:**      carstenbach & WordPress Telex  
**Tags:**              block, gatherpress, venue, hierarchy, geocoding, location, events  
**Tested up to:**      6.8  
**Stable tag:**        0.1.1  
**License:**           GPLv2 or later  
**License URI:**       https://www.gnu.org/licenses/gpl-2.0.html  
**Requires Plugins:**  gatherpress  

[![Playground Demo Link](https://img.shields.io/badge/WordPress_Playground-blue?logo=wordpress&logoColor=%23fff&labelColor=%233858e9&color=%233858e9)](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/carstingaxion/gatherpress-location-hierarchy/main/.wordpress-org/blueprints/blueprint.json)

Automatically creates hierarchical location taxonomy for GatherPress events using geocoded address data.

## Description

This plugin extends GatherPress by adding a hierarchical location taxonomy. When an event is saved, the plugin geocodes the venue address and creates taxonomy terms organized by continent, country, state, city, street, and street number. A Gutenberg block displays these hierarchies with configurable level filtering.

### What This Plugin Does

* Creates a custom hierarchical taxonomy `gatherpress_location`
* Geocodes venue addresses using the Nominatim (OpenStreetMap) API
* Automatically generates taxonomy terms in 6 levels: Continent > Country > State > City > Street > Street+Number
* Establishes parent-child relationships between terms
* Associates created terms with events
* Provides a Gutenberg block for displaying location hierarchies
* Generates canonical URLs for taxonomy archives with single child terms


### Display Block Features

**Hierarchy Level Control:**

* Dual-handle range control for selecting start and end levels
* 6 levels available: Continent, Country, State, City, Street, Number

**Display Options:**

* Customizable separator between terms (default: " > ")
* Optional term links to taxonomy archive pages
* Optional venue display at end of hierarchy
* Venue links to GatherPress venue post when enabled
* Venue respects term link setting (links if enabled, plain text if disabled)
* Full WordPress block editor support (alignment, colors, spacing, border, typography)

**Context Awareness:**

* Works inside single event posts
* Works inside query loops querying for events



## Installation

### Requirements

* WordPress 6.0 or higher
* PHP 7.4 or higher
* GatherPress plugin installed and activated

### Installation Steps

1. Upload plugin files to `/wp-content/plugins/gatherpress-location-hierarchy/`
2. Activate the plugin through the WordPress Plugins menu
3. Plugin registers taxonomy and block automatically on activation

### Configuration

1. (Optional) Navigate to Settings > GatherPress Location and set default geographic terms 
2. Create or edit GatherPress events with venue addresses
3. Location terms are generated automatically on save
4. Add "Location Hierarchy" block to templates or posts



## Frequently Asked Questions

### How does geocoding work?

The plugin sends venue addresses to Nominatim API (OpenStreetMap) including the site language for localized results. Response includes coordinates and address components. Results are cached locally in the WordPress database using transients (1-hour expiration). Cache key format: `gpvh_geocode_{md5(address)}`.

### What happens if geocoding fails?

Events without location terms can have terms added manually through WordPress admin. Geocoding will retry automatically when the event is saved again.

### Can I manually edit location terms?

No. Even terms are standard WordPress taxonomy terms accessible through the admin interface, exactly this admin interface is not available *normally*. You can enable the common admin UI for the taxonomy by enabling `WP_DEBUG` or running the plugin on a `local` or `development` environment type. Manual edits persist unless all terms are deleted and event is resaved, which will trigger geocoding and recreates terms.

### What is the API usage policy?

Nominatim is free but has usage limits. Review OpenStreetMap's Nominatim Usage Policy. For high-volume sites, consider:
* Self-hosted Nominatim instance
* Commercial geocoding service
* Extended cache duration (filter the cache_duration property)

### What regions are supported?

All regions returned by Nominatim are supported. Enhanced handling for German-speaking regions (DE, AT, CH, LU) uses specific administrative structure (Bundesländer/Cantons). City-states like Berlin receive special handling to avoid duplicate entries.

### How does this relate to GatherPress venues?

This plugin creates a separate `gatherpress_location` taxonomy. GatherPress's venue system (venue post type and `_gatherpress_venue` taxonomy) remains unchanged. The location taxonomy provides geographic organization while GatherPress manages venue details (address, phone, website).

### How do I query events by location?

Use standard WordPress taxonomy queries:

```php
$args = array(
    'post_type' => 'gatherpress_event',
    'tax_query' => array(
        array(
            'taxonomy' => 'gatherpress_location',
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
* Full hierarchy: Levels 1-6 (plugin default)



### Can I customize block appearance?

The block supports WordPress color controls (text, background, link), typography, spacing, and border settings. Custom CSS can target `.wp-block-gatherpress-location-hierarchy` class.

### Does this affect performance?

Performance considerations:
* API calls only occur when events are saved (not on page load)
* 1-hour caching reduces API requests
* Standard WordPress taxonomy queries
* No frontend JavaScript required
* Canonical URL generation adds minimal overhead (single query per taxonomy archive)

### What are canonical URLs and why are they used?

Canonical URLs tell search engines which page is the "main" version when multiple URLs show identical content. When a location term has only one child, both taxonomy archives display the same events (duplicate content). The plugin adds a canonical link tag pointing to the child's archive, consolidating SEO value and preventing search engine confusion.

Example: If Europe has only Germany as child, `/events/in/europe/` shows canonical tag pointing to `/events/in/europe/germany/`.

### How does slug generation work?

Term slugs are generated using WordPress's remove_accents() with locale parameter, then sanitize_title(). This ensures:
* French accents are removed (é→e, è→e, à→a)
* German ß becomes "ss"
* Special characters are converted to hyphens
* Unsafe characters are stripped
* Consistent transliteration across languages
* Countries use country_code as slug (via filter)


## Documentation

* [Developer Documentation](./docs/developer/README.md)
    * [Actions and Filters](./docs/developer/hooks/Hooks.md)

## Changelog

All notable changes to this project will be documented in the [CHANGELOG.md](CHANGELOG.md).


## Privacy

This plugin sends venue addresses to Nominatim API (https://nominatim.openstreetmap.org) when events are saved.

The site language is also sent for localized results. Only venue addresses and language codes and the admin-email option are transmitted. No user data or personal information is sent.

Geocoding results are cached locally in the WordPress database using transients (1-hour expiration). No data is sent to services other than Nominatim.

Review OpenStreetMap's privacy policy at: https://wiki.osmfoundation.org/wiki/Privacy_Policy

## Credits

This plugin uses the Nominatim API provided by OpenStreetMap Foundation. Nominatim is licensed under GPL v2.