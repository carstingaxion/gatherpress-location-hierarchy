<?php
/**
 * Block renderer for the location hierarchy display block.
 *
 * This file contains the singleton renderer class and serves as the
 * render callback for the block. It retrieves event venue information
 * and builds the complete hierarchical location path for display.
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
	 * Handles rendering of the location hierarchy display block,
	 * retrieving event venue information and building the complete
	 * hierarchical location path for display.
	 *
	 * @since 0.1.0
	 */
	class GatherPress_Venue_Hierarchy_Block_Renderer {
		
		/**
		 * Single instance.
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
		 * Retrieves the event's venue location hierarchy and renders
		 * it as an inline text display with proper formatting.
		 *
		 * @since 0.1.0
		 * @param array<string, mixed> $attributes Block attributes.
		 * @param string              $content    Block content.
		 * @param \WP_Block            $block      Block instance.
		 * @return string Rendered block HTML.
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
		 * Helper method to render just the venue when no location terms exist.
		 *
		 * @since 0.1.0
		 * @param string $venue_name  Venue name to display.
		 * @param string $venue_link  Venue permalink (optional).
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
		 * Constructs complete hierarchical paths for each location term,
		 * traversing parent relationships to build full location strings.
		 * Only includes the deepest (most specific) term in each hierarchy branch.
		 * Filters paths based on start and end level settings.
		 * Optionally wraps each term in a link to its archive page.
		 *
		 * @since 0.1.0
		 * @param array<\WP_Term> $terms        Array of term objects.
		 * @param int            $start_level  Starting hierarchy level (1-based).
		 * @param int            $end_level    Ending hierarchy level (1-based).
		 * @param bool           $enable_links Whether to link terms to their archives.
		 * @return array<string> Array of formatted hierarchy paths.
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
		 * Recursively builds the complete hierarchical path for a term
		 * by traversing parent relationships from child to root.
		 * Optionally wraps each term in a link to its archive page.
		 *
		 * @since 0.1.0
		 * @param \WP_Term $term         Term object.
		 * @param bool    $enable_links Whether to link terms to their archives.
		 * @return array<string> Array of term names/links from root to leaf.
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