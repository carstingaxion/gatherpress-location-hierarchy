<?php
/**
 * 
 */

declare(strict_types=1);

namespace GatherPress_Location_Hierarchy;

/**
 * Hierarchy builder class using Singleton pattern.
 *
 * Creates and manages hierarchical taxonomy terms for geographical locations.
 *
 * Separates term creation logic from geocoding logic (Single Responsibility Principle).
 * Handles the complex task of establishing parent-child relationships between terms
 * and ensuring terms exist before referencing them as parents. This prevents orphaned
 * terms and maintains data integrity across 6 levels of hierarchy.
 *
 * - Creates terms in top-down order (continent > country > state > city > street > street-number)
 * - Each level uses parent's term_id as parent parameter
 * - Checks for existing terms before creating (prevents duplicates)
 * - Updates parent relationships if term exists with wrong parent
 * - Associates all created terms with the event post
 * - Uses sanitize_title() for proper slug generation (handles ß, accents, etc.)
 * - Respects allowed level range via filter
 * - Applies filter before term insertion to allow attribute customization
 *
 * @since 0.1.0
 */
class Builder {
	
	/**
	 * Single instance.
	 *
	 * Singleton ensures consistent term creation behavior and prevents
	 * potential race conditions from multiple instances creating duplicate terms.
	 *
	 * @since 0.1.0
	 * @var Builder|null
	 */
	private static $instance = null;
	
	/**
	 * Get singleton instance.
	 *
	 * @since 0.1.0
	 * @return Builder The singleton instance.
	 */
	public static function get_instance(): Builder {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Constructor.
	 *
	 * Private constructor to enforce singleton pattern.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {}
	
	/**
	 * Check if a specific hierarchy level is allowed.
	 *
	 * Determines if a given level number should be processed based on configuration.
	 *
	 * Allows sites to restrict which hierarchy levels are created, providing flexibility
	 * for different use cases without code changes.
	 *
	 * Gets the allowed range via filter and checks if the level falls within bounds.
	 *
	 * Level numbers:
	 * 1 = Continent
	 * 2 = Country  
	 * 3 = State
	 * 4 = City
	 * 5 = Street
	 * 6 = Street Number
	 *
	 * @since 0.1.0
	 * @param int $level Level number to check (1-6).
	 * @return bool True if level is allowed, false otherwise.
	 */
	private function is_level_allowed( int $level ): bool {
		$hierarchy                 = Setup::get_instance();
		[ $min_level, $max_level ] = $hierarchy->get_allowed_levels();
		
		return $level >= $min_level && $level <= $max_level;
	}
	
	/**
	 * Create hierarchy terms.
	 *
	 * Generates complete hierarchical taxonomy term structure from location data.
	 *
	 * Automates the creation of properly nested geographic terms
	 * (continent > country > state > city > street > street-number) with correct parent-child relationships.
	 * This enables filtering events at any geographic level and provides structured data for the display block.
	 *
	 * **How:**
	 * 1. Checks allowed level range via filter
	 * 2. Creates terms in hierarchical order using cascading term IDs:
	 *    - Continent (level 1, parent: 0 = root level)
	 *    - Country (level 2, parent: continent_term_id)
	 *    - State (level 3, parent: country_term_id) - may equal city for city-states
	 *    - City (level 4, parent: state_term_id) - may be suburb for city-states
	 *    - Street (level 5, parent: city_term_id)
	 *    - Street Number (level 6, parent: street_term_id)
	 * 3. Each step uses get_or_create_term() which:
	 *    - Checks if term exists by name
	 *    - Creates if missing with proper slug generation
	 *    - Updates parent if wrong
	 *    - Returns term_id for next level
	 * 4. Skips levels outside allowed range
	 * 5. Tracks last valid parent for proper relationships
	 * 6. Collects all valid term IDs (filters out 0s from failures)
	 * 7. Associates all terms with the event via wp_set_object_terms()
	 *    - false parameter: Replace existing terms (not append)
	 *
	 * Example flow for "81-84 Greifswalder Straße, Prenzlauer Berg, Berlin, Germany" (city-state):
	 * With default levels [1, 6]:
	 * - Create/get "Europe" term (ID: 100, parent: 0)
	 * - Create/get "Germany" term (ID: 101, parent: 100)
	 * - Create/get "Berlin" term as state (ID: 102, parent: 101)
	 * - Create/get "Prenzlauer Berg" term as city (ID: 103, parent: 102)
	 * - Create/get "Greifswalder Straße" term (ID: 104, parent: 103)
	 * - Create/get "Greifswalder Straße 81-84" term (ID: 105, parent: 104)
	 * - Associate post with terms [100, 101, 102, 103, 104, 105]
	 *
	 * With restricted levels [2, 4] (Country to City only):
	 * - Skip continent (level 1 not allowed)
	 * - Create/get "Germany" term (ID: 101, parent: 0) - root because continent skipped
	 * - Create/get "Berlin" term as state (ID: 102, parent: 101)
	 * - Create/get "Prenzlauer Berg" term as city (ID: 103, parent: 102)
	 * - Skip street levels (5-6 not allowed)
	 * - Associate post with terms [101, 102, 103]
	 *
	 * **Result:** Event is tagged with complete hierarchy, browseable at any level.
	 *
	 * @since 0.1.0
	 * @param int                   $post_id  Post ID to associate terms with.
	 * @param array<string, string> $location Location data array with keys:
	 *                                       'continent', 'country', 'country_code', 'state', 'city', 'street', 'street_number'.
	 * @param string                $taxonomy Taxonomy name (e.g., 'gatherpress_location').
	 * @return void
	 */
	public function create_hierarchy_terms( int $post_id, array $location, string $taxonomy ): void {
		$continent_term_id     = 0;
		$country_term_id       = 0;
		$state_term_id         = 0;
		$city_term_id          = 0;
		$street_term_id        = 0;
		$street_number_term_id = 0;

		$locale = ! empty( $location['country_code'] ) ? $location['country_code'] : '';
		
		// Track the last valid parent ID for proper hierarchy
		$last_parent_id = 0;
		
		// Level 1: Continent
		if ( ! empty( $location['continent'] ) && $this->is_level_allowed( 1 ) ) {
			$continent_term_id = $this->get_or_create_term( $location['continent'], $last_parent_id, $taxonomy, $locale, 1, $location );
			if ( $continent_term_id ) {
				$last_parent_id = $continent_term_id;
			}
		}
		
		// Level 2: Country
		if ( ! empty( $location['country'] ) && $this->is_level_allowed( 2 ) ) {
			$country_term_id = $this->get_or_create_term( $location['country'], $last_parent_id, $taxonomy, $locale, 2, $location );
			if ( $country_term_id ) {
				$last_parent_id = $country_term_id;
			}
		}
		
		// Level 3: State
		if ( ! empty( $location['state'] ) && $this->is_level_allowed( 3 ) ) {
			$state_term_id = $this->get_or_create_term( $location['state'], $last_parent_id, $taxonomy, $locale, 3, $location );
			if ( $state_term_id ) {
				$last_parent_id = $state_term_id;
			}
		}
		
		// Level 4: City
		if ( ! empty( $location['city'] ) && $this->is_level_allowed( 4 ) ) {
			$city_term_id = $this->get_or_create_term( $location['city'], $last_parent_id, $taxonomy, $locale, 4, $location );
			if ( $city_term_id ) {
				$last_parent_id = $city_term_id;
			}
		}
		
		// Level 5: Street
		if ( ! empty( $location['street'] ) && $this->is_level_allowed( 5 ) ) {
			$street_term_id = $this->get_or_create_term( $location['street'], $last_parent_id, $taxonomy, $locale, 5, $location );
			if ( $street_term_id ) {
				$last_parent_id = $street_term_id;
			}
		}
		
		// Level 6: Street Number
		if ( ! empty( $location['street_number'] ) && $this->is_level_allowed( 6 ) ) {
			$street_number_term_id = $this->get_or_create_term( $location['street_number'], $last_parent_id, $taxonomy, $locale, 6, $location );
		}
		
		$term_ids = array_filter( array( $continent_term_id, $country_term_id, $state_term_id, $city_term_id, $street_term_id, $street_number_term_id ) );
		
		if ( ! empty( $term_ids ) ) {
			wp_set_object_terms( $post_id, $term_ids, $taxonomy, false );
		}
	}
	
	/**
	 * Get or create term.
	 *
	 * Retrieves existing term or creates new one with proper parent relationship and slug.
	 *
	 * Prevents duplicate terms and ensures consistent hierarchy. If a term exists
	 * but has the wrong parent (e.g., from manual creation), it fixes the relationship.
	 * Uses sanitize_title() for proper slug generation, ensuring special characters like
	 * German ß become "ss" and French accents are properly converted (e.g., é → e).
	 * Applies a filter before creating the term to allow customization of term attributes,
	 * such as using country codes as slugs for countries.
	 *
	 * **How:**
	 * 1. Sanitizes term name for display (security + consistency)
	 * 2. Generates proper slug using sanitize_title() (handles ß → ss, accents, etc.)
	 * 3. For country level (level 2), uses country_code as slug if available
	 * 4. Applies 'gatherpress_location_hierarchy_term_args' filter to allow customization
	 * 5. Checks if term exists using get_term_by('slug', ...)
	 *    - Looks up by slug (not name) to handle transliteration consistently
	 * 6. If term exists:
	 *    - Validates it's a WP_Term object
	 *    - Checks if parent matches expected parent_id
	 *    - Updates parent via wp_update_term() if mismatch
	 *    - Returns existing term_id
	 * 7. If term doesn't exist:
	 *    - Creates via wp_insert_term() with parent and explicit slug
	 *    - Handles errors (logs to error_log)
	 *    - Returns new term_id or 0 on failure
	 *
	 * Example scenarios:
	 * Scenario 1 - Create new with special chars:
	 *   Input: name="Große Straße", parent_id=101, taxonomy="gatherpress-location"
	 *   Slug: "grosse-strasse" (ß → ss)
	 *   Result: Creates term, returns ID 102
	 *
	 * Scenario 2 - Create with French accents:
	 *   Input: name="Café René", parent_id=101
	 *   Slug: "cafe-rene" (é → e)
	 *   Result: Creates term, returns ID 103
	 *
	 * Scenario 3 - Use existing:
	 *   Input: name="Bavaria", parent_id=101, term already exists with correct parent
	 *   Result: Returns existing ID 102
	 *
	 * Scenario 4 - Fix parent:
	 *   Input: name="Bavaria", parent_id=101, term exists with parent_id=0
	 *   Result: Updates parent to 101, returns ID 102
	 *
	 * Scenario 5 - Country with code:
	 *   Input: name="Germany", level=2, location['country_code']='de'
	 *   Slug: "de" (uses country code)
	 *   Result: Creates term with slug "de", returns ID
	 *
	 * @since 0.1.0
	 * @param string                $name      Term name to find or create.
	 * @param int                   $parent_id Parent term ID (0 for root level).
	 * @param string                $taxonomy  Taxonomy name.
	 * @param string                $locale    Country code of the retrieved address.
	 * @param int                   $level     Hierarchy level (1-6: continent, country, state, city, street, number).
	 * @param array<string, string> $location Full location data array for context.
	 * @return int Term ID on success, 0 on failure.
	 */
	private function get_or_create_term( string $name, int $parent_id, string $taxonomy, string $locale = '', int $level = 0, array $location = array() ): int {
		$name = sanitize_text_field( $name );
		// Generate proper slug using sanitize_title() which handles:
		// - German characters: ß → ss, ä → a, ö → o, ü → u
		// - French accents: é → e, è → e, ê → e, à → a, etc.
		// - Special characters: spaces → hyphens, removes unsafe chars
		
		// sanitize_title uses the sites locale to decide HOW to remove accents,
		// to stay consistent across different languages we use remove_accents directly.
		$slug = remove_accents( $name, $locale );
		$slug = sanitize_title( $slug );
		
		// For country level, use country_code as slug if available
		if ( 2 === $level && ! empty( $location['country_code'] ) ) {
			$slug = $location['country_code'];
		}
		
		/**
		 * Filter term arguments before creating the term.
		 *
		 * Allows modification of term attributes before insertion.
		 *
		 * Provides extensibility point for customizing term creation.
		 * For example, this can be used to ensure country terms use country codes
		 * as slugs instead of transliterated country names.
		 *
		 * Passes array of term data including name, slug, parent, level,
		 * and full location context. Filters can modify any attribute except taxonomy.
		 *
		 * @example
		 * ```php
		 * add_filter( 'gatherpress_location_hierarchy_term_args', function( $args ) {
		 *     // Countries use country code as slug
		 *     if ( 2 === $args['level'] && ! empty( $args['location']['country_code'] ) ) {
		 *         $args['slug'] = $args['location']['country_code'];
		 *     }
		 *     return $args;
		 * } );
		 * ```
		 * 
		 * The Term arguments, that are not well-parseable by extract-wp-hooks. (https://github.com/akirk/extract-wp-hooks/issues/23)
		 *
		 * @since 0.1.0
		 *
		 * @param array $args {
		 *     Term arguments array.
		 * 
		 *     @type string                $name      Term name.
		 *     @type string                $slug      Term slug.
		 *     @type int                   $parent    Parent term ID.
		 *     @type string                $taxonomy  Taxonomy name.
		 *     @type int                   $level     Hierarchy level (1-6).
		 *     @type array<string, string> $location  Full location data array.
		 * }
		 */
		$term_args = apply_filters(
			'gatherpress_location_hierarchy_term_args',
			array(
				'name'     => $name,
				'slug'     => $slug,
				'parent'   => $parent_id,
				'taxonomy' => $taxonomy,
				'level'    => $level,
				'location' => $location,
			)
		);
		
		// Extract potentially modified values
		$name      = $term_args['name'];
		$slug      = $term_args['slug'];
		$parent_id = $term_args['parent'];
		
		// Check by slug (not name) to handle transliteration consistently
		$existing_term = get_term_by( 'slug', $slug, $taxonomy );
		
		if ( $existing_term instanceof \WP_Term ) {
			if ( $existing_term->parent !== $parent_id ) {
				wp_update_term(
					$existing_term->term_id,
					$taxonomy,
					array( 'parent' => $parent_id )
				);
			}
			return $existing_term->term_id;
		}
		
		// Create term with explicit slug to ensure proper transliteration
		$term = wp_insert_term(
			$name,
			$taxonomy,
			array(
				'parent' => $parent_id,
				'slug'   => $slug,
			)
		);
		
		if ( is_wp_error( $term ) ) {
			error_log( 'GatherPress Location Hierarchy: Failed to create term - ' . $term->get_error_message() );
			return 0;
		}
		
		if ( ! is_array( $term ) || ! isset( $term['term_id'] ) ) {
			return 0;
		}
		
		return $term['term_id'];
	}
}
