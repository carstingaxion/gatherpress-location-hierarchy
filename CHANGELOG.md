# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased](https://github.com/carstingaxion/gatherpress-location-hierarchy/compare/0.1.2...HEAD)

## [0.1.2](https://github.com/carstingaxion/gatherpress-location-hierarchy/compare/0.1.1...0.1.2) - 2026-01-30

## [0.1.1](https://github.com/carstingaxion/gatherpress-location-hierarchy/compare/0.1.0...0.1.1) - 2026-01-30

## 0.1.0

* Initial release
* Hierarchical gatherpress_location taxonomy (6 levels)
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
* Proper slug generation with sanitize_title() and remove_accents()
* Hierarchy level filtering via WordPress filter
* Term attribute customization filter
* Country codes as term slugs
* Canonical URL handling for taxonomy archives with single child
* Context-aware block (works in query loops)
* Automatic editor updates when event saved
