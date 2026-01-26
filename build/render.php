<?php
/**
 * Block renderer for the location hierarchy display block.
 *
 * **What:** This file serves as both the render callback for the Gutenberg block
 * and contains the singleton renderer class that handles all display logic.
 *
 * **Why:** Separating rendering logic into its own class (Singleton pattern) ensures:
 * - Consistent rendering behavior across multiple block instances
 * - Testable, maintainable code separate from block registration
 * - No duplicate term queries when multiple blocks render on same page
 *
 * **How:** WordPress calls this file as the render callback (defined in block.json).
 * The file defines the renderer class, instantiates it, and calls its render() method.
 * The renderer retrieves event venue information, builds hierarchical location paths,
 * and outputs formatted HTML with optional term links.
 *
 * @package GatherPressVenueHierarchy
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if( ! class_exists('GatherPress_Venue_Hierarchy_Block_Renderer')) {

	/**
	 * Block renderer class using Singleton pattern.
	 *
	 * **What:** Handles rendering of the location hierarchy display block on frontend.
	 *
	 * **Why:** Retrieves event venue location data and builds human-readable hierarchical
	 * paths (e.g., "Europe > Germany > Bavaria > Munich") for display. Singleton pattern
	 * prevents duplicate term queries and ensures consistent rendering logic.
	 *
	 * **How:** 
	 * - Validates post context (must be gatherpress_event)
	 * - Retrieves location terms from taxonomy
	 * - Optionally retrieves venue information from GatherPress
	 * - Builds hierarchical paths by traversing parent relationships
	 * - Filters paths based on startLevel/endLevel attributes
	 * - Optionally wraps terms in archive links
	 * - Outputs formatted HTML with block wrapper attributes
	 *
	 * @since 0.1.0
	 */
	class GatherPress_Venue_Hierarchy_Block_Renderer {
		
		/**
		 * Single instance.
		 *
		 * **Why:** Singleton prevents multiple instances that could cause duplicate
		 * queries and ensures consistent behavior across block instances on same page.
		 *
		 * @since 0.1.0
		 * @var GatherPress_Venue_Hierarchy_Block_Renderer|null
		 */
		private static $instance = null;
		
		/**
		 * Get singleton instance.
		 *
		 * @since 0.1.0
		 * @return GatherPress_Venue_Hierarchy_Block_Renderer The singleton instance.
		 */
		public static function get_instance(): GatherPress_Venue_Hierarchy_Block_Renderer {
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
		 * Render block content.
		 *
		 * **What:** Main rendering method called by WordPress for each block instance.
		 *
		 * **Why:** Generates the HTML output for displaying location hierarchies on the frontend.
		 * Must validate context, retrieve data, and format output according to block attributes.
		 *
		 * **How:**
		 * 1. Validates post ID and post type (must be gatherpress_event)
		 * 2. Extracts block attributes (startLevel, endLevel, enableLinks, showVenue)
		 * 3. Optionally retrieves venue information from GatherPress Event class
		 * 4. Queries location terms for the event
		 * 5. Builds hierarchical paths via build_hierarchy_paths()
		 * 6. Appends venue name if requested
		 * 7. Wraps output with block wrapper attributes (handles alignment, colors, etc.)
		 * 8. Returns formatted HTML or empty string if no data
		 *
		 * Example output:
		 * <p class="wp-block-telex-block-gatherpress-venue-hierarchy">
		 *   Europe > Germany > Bavaria > Munich
		 * </p>
		 *
		 * Example with links:
		 * <p class="wp-block-telex-block-gatherpress-venue-hierarchy">
		 *   <a href="/location/europe/">Europe</a> > 
		 *   <a href="/location/europe/germany/">Germany</a> > 
		 *   <a href="/location/europe/germany/bavaria/">Bavaria</a> > 
		 *   <a href="/location/europe/germany/bavaria/munich/">Munich</a>
		 * </p>
		 *
		 * @since 0.1.0
		 * @param array<string, mixed> $attributes Block attributes from block.json.
		 * @param string              $content    Block content (unused for dynamic blocks).
		 * @param \WP_Block            $block      Block instance with context.
		 * @return string Rendered block HTML or empty string.
		 */
		public function render( array $attributes, string $content, \WP_Block $block ): string {
			// Get post ID from context
			$post_id = $block->context['postId'] ?? 0;
			
			if ( ! $post_id ) {
				return '';
			}
			
			$post_id = absint( $post_id );
			$post = get_post( $post_id );
			
			if ( ! $post ) {
				return '';
			}
			
			// Verify this is a GatherPress event
			if ( 'gatherpress_event' !== $post->post_type ) {
				return '';
			}
			
			// Get hierarchy level attributes
			$start_level = isset( $attributes['startLevel'] ) ? absint( $attributes['startLevel'] ) : 1;
			$end_level = isset( $attributes['endLevel'] ) ? absint( $attributes['endLevel'] ) : 999;
			$enable_links = isset( $attributes['enableLinks'] ) ? (bool) $attributes['enableLinks'] : false;
			$show_venue = isset( $attributes['showVenue'] ) ? (bool) $attributes['showVenue'] : false;
			
			// Ensure start level is at least 1
			$start_level = max( 1, $start_level );
			
			// Ensure end level is at least equal to start level
			$end_level = max( $start_level, $end_level );
			
			// Get venue information if requested
			$venue_name = '';
			$venue_link = '';
			
			if ( $show_venue && class_exists( 'GatherPress\Core\Event' ) ) {
				$event = new \GatherPress\Core\Event( $post_id );
				$venue_info = $event->get_venue_information();
				
				if ( is_array( $venue_info ) ) {
					$venue_name = $venue_info['name'] ?? '';
					$venue_link = $venue_info['permalink'] ?? '';
				}
			}
			
			// Get location terms for this event
			$location_terms = wp_get_object_terms(
				$post_id,
				'gatherpress-location',
				array(
					'orderby' => 'parent',
					'order' => 'ASC',
				)
			);
			
			if ( is_wp_error( $location_terms ) ) {
				error_log( 'GatherPress Venue Hierarchy Block: Error getting terms - ' . $location_terms->get_error_message() );
				
				// If showing venue and we have venue info, show just the venue
				if ( $show_venue && $venue_name ) {
					return $this->render_output( $venue_name, $venue_link, $enable_links );
				}
				
				return '';
			}
			
			if ( empty( $location_terms ) ) {
				// If showing venue and we have venue info, show just the venue
				if ( $show_venue && $venue_name ) {
					return $this->render_output( $venue_name, $venue_link, $enable_links );
				}
				
				return '';
			}
			
			// Build hierarchy paths
			$hierarchy_paths = $this->build_hierarchy_paths( $location_terms, $start_level, $end_level, $enable_links );
			
			if ( empty( $hierarchy_paths ) ) {
				// If showing venue and we have venue info, show just the venue
				if ( $show_venue && $venue_name ) {
					return $this->render_output( $venue_name, $venue_link, $enable_links );
				}
				
				return '';
			}
			
			// Join all paths and add venue if requested
			$hierarchy_text = implode( ', ', $hierarchy_paths );
			
			if ( $show_venue && $venue_name ) {
				// Format venue name with optional link
				if ( $enable_links && $venue_link ) {
					$venue_text = sprintf(
						'<a href="%s" class="gatherpress-location-link gatherpress-venue-link">%s</a>',
						esc_url( $venue_link ),
						esc_html( $venue_name )
					);
				} else {
					$venue_text = esc_html( $venue_name );
				}
				
				$hierarchy_text .= ' > ' . $venue_text;
			}
			
			// Get block wrapper attributes
			$wrapper_attributes = get_block_wrapper_attributes();
			
			// Return formatted output
			return sprintf(
				'<p %s>%s</p>',
				$wrapper_attributes,
				$hierarchy_text
			);
		}
		
		/**
		 * Render output for venue-only display.
		 *
		 * **What:** Helper method to render just the venue when no location terms exist.
		 *
		 * **Why:** DRY principle - prevents code duplication when venue-only display is needed
		 * in multiple code paths (error cases, no terms, filtered-out terms).
		 *
		 * **How:** Formats venue name with optional link, wraps in block wrapper attributes,
		 * returns complete HTML paragraph element.
		 *
		 * @since 0.1.0
		 * @param string $venue_name  Venue name to display.
		 * @param string $venue_link  Venue permalink (optional, empty string if not available).
		 * @param bool   $enable_links Whether to link the venue.
		 * @return string Rendered block HTML.
		 */
		private function render_output( string $venue_name, string $venue_link, bool $enable_links ): string {
			$wrapper_attributes = get_block_wrapper_attributes();
			
			if ( $enable_links && $venue_link ) {
				$venue_text = sprintf(
					'<a href="%s" class="gatherpress-location-link gatherpress-venue-link">%s</a>',
					esc_url( $venue_link ),
					esc_html( $venue_name )
				);
			} else {
				$venue_text = esc_html( $venue_name );
			}
			
			return sprintf(
				'<p %s>%s</p>',
				$wrapper_attributes,
				$venue_text
			);
		}
		
		/**
		 * Build hierarchy paths from terms.
		 *
		 * **What:** Constructs complete hierarchical paths for display from taxonomy terms.
		 *
		 * **Why:** Events may have multiple location term assignments (e.g., multi-city event).
		 * Need to find the "leaf" (most specific) terms, build full paths by traversing parents,
		 * filter by level settings, and format for display with optional links.
		 *
		 * **How:**
		 * 1. Identifies leaf terms (deepest terms in each branch):
		 *    - Term is leaf if its ID doesn't appear in any other term's parent field
		 *    - Example: [Europe(0), Germany(Europe), Bavaria(Germany), Munich(Bavaria)]
		 *      Leaf = Munich (103) because 103 isn't anyone's parent
		 * 2. Builds full path for each leaf term via build_term_path()
		 * 3. Filters each path based on startLevel/endLevel:
		 *    - Converts 1-based levels to 0-based array indices
		 *    - Uses array_slice() to extract relevant portion
		 *    - Example: Full path [Europe, Germany, Bavaria, Munich], levels 2-3
		 *      Result: [Germany, Bavaria]
		 * 4. Joins filtered paths with ' > ' separator
		 * 5. Returns array of formatted path strings
		 *
		 * Example:
		 * Input terms: Europe(0), Germany(1), Bavaria(2), Munich(3)
		 * Input levels: start=1, end=3
		 * Output: ["Europe > Germany > Bavaria"]
		 *
		 * @since 0.1.0
		 * @param array<\WP_Term> $terms        Array of term objects from wp_get_object_terms().
		 * @param int            $start_level  Starting hierarchy level (1-based, 1=continent).
		 * @param int            $end_level    Ending hierarchy level (1-based, 5=street).
		 * @param bool           $enable_links Whether to wrap terms in archive links.
		 * @return array<string> Array of formatted hierarchy path strings.
		 */
		private function build_hierarchy_paths( array $terms, int $start_level, int $end_level, bool $enable_links ): array {
			if ( empty( $terms ) ) {
				return array();
			}
			
			// Find the deepest (leaf) terms - those that are not parents of other terms
			$term_ids = wp_list_pluck( $terms, 'term_id' );
			$parent_ids = wp_list_pluck( $terms, 'parent' );
			
			$leaf_terms = array();
			foreach ( $terms as $term ) {
				if ( ! $term instanceof \WP_Term ) {
					continue;
				}
				
				// A term is a leaf if its ID is not in the parent_ids array
				if ( ! in_array( $term->term_id, $parent_ids, true ) ) {
					$leaf_terms[] = $term;
				}
			}
			
			// If no leaf terms found, use all terms
			if ( empty( $leaf_terms ) ) {
				$leaf_terms = $terms;
			}
			
			// Build paths for each leaf term
			$hierarchy_paths = array();
			foreach ( $leaf_terms as $term ) {
				$full_path = $this->build_term_path( $term, $enable_links );
				
				if ( empty( $full_path ) ) {
					continue;
				}
				
				// Filter the path based on start and end levels
				$path_length = count( $full_path );
				
				// Adjust levels to array indices (0-based)
				$start_index = $start_level - 1;
				$end_index = min( $end_level, $path_length );
				
				// Skip if start level is beyond the path length
				if ( $start_index >= $path_length ) {
					continue;
				}
				
				// Extract the relevant slice of the path
				$filtered_path = array_slice( $full_path, $start_index, $end_index - $start_index );
				
				if ( ! empty( $filtered_path ) ) {
					$hierarchy_paths[] = implode( ' > ', $filtered_path );
				}
			}
			
			return $hierarchy_paths;
		}
		
		/**
		 * Build term path.
		 *
		 * **What:** Recursively builds complete hierarchical path from child term to root.
		 *
		 * **Why:** Terms store only their immediate parent, not the full ancestry. Need to
		 * traverse parent relationships recursively to build complete paths like
		 * "Europe > Germany > Bavaria > Munich". Loop detection prevents infinite recursion
		 * if term relationships are corrupted.
		 *
		 * **How:**
		 * 1. Initializes empty path array and visited tracking array
		 * 2. Loops while current_term exists and max depth not exceeded:
		 *    - Checks if term already visited (prevents infinite loops)
		 *    - Formats term name with optional archive link
		 *    - Prepends to path array (array_unshift for root-to-leaf order)
		 *    - If term has parent, loads parent term and continues
		 *    - If no parent (parent=0), breaks loop
		 * 3. Max depth of 10 prevents runaway recursion
		 * 4. Returns array of formatted term strings (plain text or HTML links)
		 *
		 * Example flow:
		 * Input: Munich term (parent=102), enable_links=true
		 * Step 1: path=["<a>Munich</a>"], current=Bavaria(102)
		 * Step 2: path=["<a>Bavaria</a>", "<a>Munich</a>"], current=Germany(101)
		 * Step 3: path=["<a>Germany</a>", "<a>Bavaria</a>", "<a>Munich</a>"], current=Europe(100)
		 * Step 4: path=["<a>Europe</a>", "<a>Germany</a>", "<a>Bavaria</a>", "<a>Munich</a>"], parent=0, break
		 * Output: ["<a>Europe</a>", "<a>Germany</a>", "<a>Bavaria</a>", "<a>Munich</a>"]
		 *
		 * @since 0.1.0
		 * @param \WP_Term $term         Starting term object (usually a leaf term).
		 * @param bool    $enable_links Whether to wrap each term in an archive link.
		 * @return array<string> Array of formatted term names/links from root to leaf.
		 */
		private function build_term_path( \WP_Term $term, bool $enable_links ): array {
			$path = array();
			$current_term = $term;
			$visited = array();
			
			// Prevent infinite loops
			$max_depth = 10;
			$depth = 0;
			
			while ( $current_term && $depth < $max_depth ) {
				// Check if we've visited this term (prevent infinite loops)
				if ( in_array( $current_term->term_id, $visited, true ) ) {
					break;
				}
				
				$visited[] = $current_term->term_id;
				
				// Format term name with optional link
				if ( $enable_links ) {
					$term_link = get_term_link( $current_term );
					if ( ! is_wp_error( $term_link ) ) {
						$term_text = sprintf(
							'<a href="%s" class="gatherpress-location-link">%s</a>',
							esc_url( $term_link ),
							esc_html( $current_term->name )
						);
					} else {
						$term_text = esc_html( $current_term->name );
					}
				} else {
					$term_text = esc_html( $current_term->name );
				}
				
				array_unshift( $path, $term_text );
				
				if ( $current_term->parent ) {
					$parent_term = get_term( $current_term->parent, 'gatherpress-location' );
					
					if ( is_wp_error( $parent_term ) || ! $parent_term ) {
						break;
					}
					
					$current_term = $parent_term;
				} else {
					break;
				}
				
				$depth++;
			}
			
			return $path;
		}
	}

}
$renderer = GatherPress_Venue_Hierarchy_Block_Renderer::get_instance();
echo $renderer->render( $attributes, $content, $block );