<?php
/**
 * Plugin Name:       GatherPress Venue Hierarchy
 * Plugin URI:        https://github.com/automattic/gatherpress-venue-hierarchy
 * Description:       Adds hierarchical location taxonomy to GatherPress with automatic geocoding
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            WordPress Telex
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gatherpress-venue-hierarchy
 *
 * @package GatherPressVenueHierarchy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class using Singleton pattern.
 *
 * Handles registration of the hierarchical location taxonomy,
 * admin settings, and coordinates geocoding and hierarchy building
 * for GatherPress venues.
 *
 * @since 0.1.0
 */
class GatherPress_Venue_Hierarchy {
	
	/**
	 * Single instance of the class.
	 *
	 * @since 0.1.0
	 * @var GatherPress_Venue_Hierarchy|null
	 */
	private static $instance = null;
	
	/**
	 * Taxonomy name for hierarchical locations.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private $taxonomy = 'gatherpress-location';
	
	/**
	 * Get singleton instance.
	 *
	 * Ensures only one instance of the class exists.
	 *
	 * @since 0.1.0
	 * @return GatherPress_Venue_Hierarchy The singleton instance.
	 */
	public static function get_instance(): GatherPress_Venue_Hierarchy {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Constructor.
	 *
	 * Private constructor to enforce singleton pattern.
	 * Registers all WordPress hooks and filters.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'save_post_gatherpress_event', array( $this, 'maybe_geocode_event_venue' ), 20, 2 );
		// add_filter( 'get_terms_args', array( $this, 'order_location_terms_hierarchically' ), 10, 2 );
		// add_filter( 'terms_clauses', array( $this, 'modify_location_terms_query' ), 10, 3 );
	}
	
	/**
	 * Initialize plugin.
	 *
	 * Registers the location taxonomy and block type.
	 * Fires on the 'init' action.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function init(): void {
		$this->register_location_taxonomy();
		$this->register_block();
	}
	
	/**
	 * Register the hierarchical location taxonomy.
	 *
	 * Creates a hierarchical taxonomy for organizing venues
	 * by country, state/region, and city.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_location_taxonomy(): void {
		$labels = array(
			'name'                       => __( 'Locations', 'gatherpress-venue-hierarchy' ),
			'singular_name'              => __( 'Location', 'gatherpress-venue-hierarchy' ),
			'search_items'               => __( 'Search Locations', 'gatherpress-venue-hierarchy' ),
			'all_items'                  => __( 'All Locations', 'gatherpress-venue-hierarchy' ),
			'parent_item'                => __( 'Parent Location', 'gatherpress-venue-hierarchy' ),
			'parent_item_colon'          => __( 'Parent Location:', 'gatherpress-venue-hierarchy' ),
			'edit_item'                  => __( 'Edit Location', 'gatherpress-venue-hierarchy' ),
			'update_item'                => __( 'Update Location', 'gatherpress-venue-hierarchy' ),
			'add_new_item'               => __( 'Add New Location', 'gatherpress-venue-hierarchy' ),
			'new_item_name'              => __( 'New Location Name', 'gatherpress-venue-hierarchy' ),
			'menu_name'                  => __( 'Locations', 'gatherpress-venue-hierarchy' ),
		);
		$wp_term_query_args            = array();
		$wp_term_query_args['orderby'] = 'parent';
		$wp_term_query_args['order']   = 'ASC';

		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => true,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
			'show_in_rest'               => true,
			'rewrite'                    => array( 'slug' => 'location' ),
			'sort'                       => true,
			'args'                       => $wp_term_query_args,
		);
		
		register_taxonomy( $this->taxonomy, array( 'gatherpress_event' ), $args );
	}
	
	/**
	 * Order location terms hierarchically.
	 *
	 * Modifies term query arguments to ensure location terms
	 * are always ordered from parent to child (country > state > city).
	 *
	 * @since 0.1.0
	 * @param array<string, mixed> $args     Query arguments.
	 * @param array<string>        $taxonomies Array of taxonomy names.
	 * @return array<string, mixed> Modified query arguments.
	 */
	public function order_location_terms_hierarchically( array $args, array $taxonomies ): array {
		// Only modify queries for our location taxonomy
		if ( ! in_array( $this->taxonomy, $taxonomies, true ) ) {
			return $args;
		}
		
		// Don't override if a specific order is already set
		if ( isset( $args['orderby'] ) && 'none' !== $args['orderby'] ) {
			return $args;
		}
		
		// Set default ordering to parent hierarchy
		$args['orderby'] = 'parent';
		$args['order'] = 'ASC';
		
		return $args;
	}
	
	/**
	 * Modify location terms query to order by hierarchy depth.
	 *
	 * Modifies the SQL query to ensure terms are ordered from
	 * top-level parents down to children, maintaining the
	 * country > state > city hierarchy.
	 *
	 * @since 0.1.0
	 * @param array<string>        $clauses    Query clauses.
	 * @param array<string>        $taxonomies Array of taxonomy names.
	 * @param array<string, mixed> $args       Query arguments.
	 * @return array<string> Modified query clauses.
	 */
	public function modify_location_terms_query( array $clauses, array $taxonomies, array $args ): array {
		// Only modify queries for our location taxonomy
		if ( ! in_array( $this->taxonomy, $taxonomies, true ) ) {
			return $clauses;
		}
		
		// Only modify if ordering by parent
		if ( ! isset( $args['orderby'] ) || 'parent' !== $args['orderby'] ) {
			return $clauses;
		}
		
		global $wpdb;
		
		// Order by parent first (0 = top-level), then by term_id to maintain consistency
		$clauses['orderby'] = "ORDER BY t.parent ASC, t.term_id ASC";
		
		return $clauses;
	}
	
	/**
	 * Register the location hierarchy display block.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_block(): void {
		register_block_type( __DIR__ . '/build/' );
	}
	
	/**
	 * Add admin menu for plugin settings.
	 *
	 * Creates a submenu page under Settings for configuring
	 * default geographic locations.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function add_admin_menu(): void {
		add_options_page(
			__( 'GatherPress Location', 'gatherpress-venue-hierarchy' ),
			__( 'GatherPress Location', 'gatherpress-venue-hierarchy' ),
			'manage_options',
			'gatherpress-venue-hierarchy',
			array( $this, 'render_admin_page' )
		);
	}
	
	/**
	 * Register plugin settings.
	 *
	 * Registers settings for default country, state, and city values.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'gatherpress_venue_hierarchy',
			'gatherpress_venue_hierarchy_defaults',
			array(
				'type' => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default' => array(
					'country' => '',
					'state' => '',
					'city' => '',
				),
			)
		);
	}
	
	/**
	 * Sanitize settings input.
	 *
	 * Ensures all settings values are properly sanitized before saving.
	 *
	 * @since 0.1.0
	 * @param mixed $input Raw settings input from form submission.
	 * @return array<string, string> Sanitized settings array.
	 */
	public function sanitize_settings( $input ): array {
		if ( ! is_array( $input ) ) {
			$input = array();
		}
		
		return array(
			'country' => sanitize_text_field( $input['country'] ?? '' ),
			'state' => sanitize_text_field( $input['state'] ?? '' ),
			'city' => sanitize_text_field( $input['city'] ?? '' ),
		);
	}
	
	/**
	 * Render the admin settings page.
	 *
	 * Displays the settings form for configuring default locations.
	 * Includes fields for country, state, and city.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function render_admin_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		$defaults = get_option( 'gatherpress_venue_hierarchy_defaults', array(
			'country' => '',
			'state' => '',
			'city' => '',
		) );
		
		if ( ! is_array( $defaults ) ) {
			$defaults = array(
				'country' => '',
				'state' => '',
				'city' => '',
			);
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'gatherpress_venue_hierarchy' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="default_country"><?php esc_html_e( 'Default Country', 'gatherpress-venue-hierarchy' ); ?></label>
						</th>
						<td>
							<input type="text" id="default_country" name="gatherpress_venue_hierarchy_defaults[country]" 
								value="<?php echo esc_attr( $defaults['country'] ?? '' ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Default country for new venues', 'gatherpress-venue-hierarchy' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="default_state"><?php esc_html_e( 'Default State/Region', 'gatherpress-venue-hierarchy' ); ?></label>
						</th>
						<td>
							<input type="text" id="default_state" name="gatherpress_venue_hierarchy_defaults[state]" 
								value="<?php echo esc_attr( $defaults['state'] ?? '' ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Default state or region for new venues', 'gatherpress-venue-hierarchy' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="default_city"><?php esc_html_e( 'Default City', 'gatherpress-venue-hierarchy' ); ?></label>
						</th>
						<td>
							<input type="text" id="default_city" name="gatherpress_venue_hierarchy_defaults[city]" 
								value="<?php echo esc_attr( $defaults['city'] ?? '' ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Default city for new venues', 'gatherpress-venue-hierarchy' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Maybe geocode event venue on save.
	 *
	 * Triggers geocoding and hierarchy generation when a GatherPress
	 * event is saved. Skips if autosaving or if no venue address exists.
	 *
	 * @since 0.1.0
	 * @param int     $post_id Post ID of the event.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function maybe_geocode_event_venue( int $post_id, \WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		
		if ( 'gatherpress_event' !== $post->post_type ) {
			return;
		}
		
		if ( ! class_exists( 'GatherPress\Core\Event' ) ) {
			error_log( 'GatherPress Venue Hierarchy: GatherPress Event class not found' );
			return;
		}
		
		$event = new \GatherPress\Core\Event( $post_id );
		$venue_info = $event->get_venue_information();
		
		if ( ! is_array( $venue_info ) || empty( $venue_info['full_address'] ) ) {
			return;
		}
		
		$this->geocode_and_create_hierarchy( $post_id, $venue_info['full_address'] );
	}
	
	/**
	 * Geocode address and create location hierarchy.
	 *
	 * Coordinates the geocoding process via Nominatim API and
	 * generates the hierarchical location terms.
	 *
	 * @since 0.1.0
	 * @param int    $post_id Post ID to associate terms with.
	 * @param string $address Address to geocode.
	 * @return void
	 */
	private function geocode_and_create_hierarchy( int $post_id, string $address ): void {
		$geocoder = GatherPress_Venue_Geocoder::get_instance();
		$location = $geocoder->geocode( $address );
		
		if ( ! $location ) {
			error_log( 'GatherPress Venue Hierarchy: Failed to geocode address for event ' . $post_id );
			return;
		}
		
		$hierarchy_builder = GatherPress_Venue_Hierarchy_Builder::get_instance();
		$hierarchy_builder->create_hierarchy_terms( $post_id, $location, $this->taxonomy );
	}
}

/**
 * Geocoder class using Singleton pattern.
 *
 * Handles geocoding of addresses via the Nominatim OpenStreetMap API.
 * Includes caching to minimize API calls and parsing of location data
 * with special handling for German-speaking regions.
 *
 * @since 0.1.0
 */
class GatherPress_Venue_Geocoder {
	
	/**
	 * Single instance.
	 *
	 * @since 0.1.0
	 * @var GatherPress_Venue_Geocoder|null
	 */
	private static $instance = null;
	
	/**
	 * Nominatim API endpoint.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private $api_endpoint = 'https://nominatim.openstreetmap.org/search';
	
	/**
	 * Cache duration in seconds (1 hour).
	 *
	 * @since 0.1.0
	 * @var int
	 */
	private $cache_duration = 3600;
	
	/**
	 * Get singleton instance.
	 *
	 * @since 0.1.0
	 * @return GatherPress_Venue_Geocoder The singleton instance.
	 */
	public static function get_instance(): GatherPress_Venue_Geocoder {
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
	 * Geocode an address.
	 *
	 * Queries the Nominatim API to get geographic coordinates and
	 * location details. Results are cached for one hour.
	 *
	 * @since 0.1.0
	 * @param string $address Address to geocode.
	 * @return array<string, string>|false Location data array with keys:
	 *                                      'country', 'country_code', 'state', 'city'
	 *                                      or false on failure.
	 */
	public function geocode( string $address ) {
		$address = sanitize_text_field( $address );
		$cache_key = 'gpvh_geocode_' . md5( $address );
		
		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}
		
		$response = wp_remote_get(
			add_query_arg(
				array(
					'q' => $address,
					'format' => 'json',
					'addressdetails' => '1',
					'limit' => '1',
				),
				$this->api_endpoint
			),
			array(
				'timeout' => 10,
				'headers' => array(
					'User-Agent' => 'GatherPress Venue Hierarchy WordPress Plugin',
				),
			)
		);
		
		if ( is_wp_error( $response ) ) {
			error_log( 'GatherPress Venue Hierarchy: Geocoding API error - ' . $response->get_error_message() );
			return false;
		}
		
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		
		if ( empty( $data ) || ! is_array( $data ) ) {
			error_log( 'GatherPress Venue Hierarchy: Invalid API response for address: ' . $address );
			return false;
		}
		
		$location = $this->parse_location_data( $data[0] );
		
		if ( $location ) {
			set_transient( $cache_key, $location, $this->cache_duration );
		}
		
		return $location;
	}
	
	/**
	 * Parse location data from API response.
	 *
	 * Extracts country, state, and city information from the Nominatim
	 * API response with special handling for German-speaking regions.
	 *
	 * @since 0.1.0
	 * @param array<string, mixed> $data API response data.
	 * @return array<string, string>|false Parsed location data or false on failure.
	 */
	private function parse_location_data( array $data ) {
		if ( empty( $data['address'] ) || ! is_array( $data['address'] ) ) {
			return false;
		}
		
		$address = $data['address'];
		$country_code = strtolower( $address['country_code'] ?? '' );
		
		$location = array(
			'country' => $this->sanitize_term_name( $address['country'] ?? '' ),
			'country_code' => $country_code,
			'state' => '',
			'city' => '',
		);
		
		$german_regions = array( 'de', 'at', 'ch', 'lu' );
		$is_german_region = in_array( $country_code, $german_regions, true );
		
		if ( $is_german_region ) {
			$location['state'] = $this->sanitize_term_name( $address['state'] ?? '' );
		} else {
			$location['state'] = $this->sanitize_term_name( 
				$address['state'] ?? $address['region'] ?? $address['province'] ?? '' 
			);
		}
		
		$location['city'] = $this->sanitize_term_name(
			$address['city'] ?? $address['town'] ?? $address['village'] ?? $address['county'] ?? ''
		);
		
		return array_filter( $location );
	}
	
	/**
	 * Sanitize term name.
	 *
	 * Removes unwanted characters and trims whitespace.
	 *
	 * @since 0.1.0
	 * @param string $name Term name to sanitize.
	 * @return string Sanitized term name.
	 */
	private function sanitize_term_name( string $name ): string {
		return trim( sanitize_text_field( $name ) );
	}
}

/**
 * Hierarchy builder class using Singleton pattern.
 *
 * Creates and manages hierarchical taxonomy terms for locations,
 * establishing parent-child relationships between country, state, and city.
 *
 * @since 0.1.0
 */
class GatherPress_Venue_Hierarchy_Builder {
	
	/**
	 * Single instance.
	 *
	 * @since 0.1.0
	 * @var GatherPress_Venue_Hierarchy_Builder|null
	 */
	private static $instance = null;
	
	/**
	 * Get singleton instance.
	 *
	 * @since 0.1.0
	 * @return GatherPress_Venue_Hierarchy_Builder The singleton instance.
	 */
	public static function get_instance(): GatherPress_Venue_Hierarchy_Builder {
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
	 * Create hierarchy terms.
	 *
	 * Generates hierarchical taxonomy terms for country, state, and city,
	 * establishing proper parent-child relationships and associating them
	 * with the specified post.
	 *
	 * @since 0.1.0
	 * @param int                  $post_id  Post ID to associate terms with.
	 * @param array<string, string> $location Location data array with keys:
	 *                                       'country', 'state', 'city'.
	 * @param string               $taxonomy Taxonomy name.
	 * @return void
	 */
	public function create_hierarchy_terms( int $post_id, array $location, string $taxonomy ): void {
		$country_term_id = 0;
		$state_term_id = 0;
		$city_term_id = 0;
		
		if ( ! empty( $location['country'] ) ) {
			$country_term_id = $this->get_or_create_term( $location['country'], 0, $taxonomy );
		}
		
		if ( ! empty( $location['state'] ) && $country_term_id ) {
			$state_term_id = $this->get_or_create_term( $location['state'], $country_term_id, $taxonomy );
		}
		
		if ( ! empty( $location['city'] ) && $state_term_id ) {
			$city_term_id = $this->get_or_create_term( $location['city'], $state_term_id, $taxonomy );
		}
		
		$term_ids = array_filter( array( $country_term_id, $state_term_id, $city_term_id ) );
		
		if ( ! empty( $term_ids ) ) {
			wp_set_object_terms( $post_id, $term_ids, $taxonomy, false );
		}
	}
	
	/**
	 * Get or create term.
	 *
	 * Retrieves an existing term by name or creates it if it doesn't exist.
	 * Updates parent relationship if the term exists but has wrong parent.
	 *
	 * @since 0.1.0
	 * @param string $name      Term name.
	 * @param int    $parent_id Parent term ID (0 for top-level).
	 * @param string $taxonomy  Taxonomy name.
	 * @return int Term ID, or 0 on failure.
	 */
	private function get_or_create_term( string $name, int $parent_id, string $taxonomy ): int {
		$name = sanitize_text_field( $name );
		
		$existing_term = get_term_by( 'name', $name, $taxonomy );
		
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
		
		$term = wp_insert_term(
			$name,
			$taxonomy,
			array( 'parent' => $parent_id )
		);
		
		if ( is_wp_error( $term ) ) {
			error_log( 'GatherPress Venue Hierarchy: Failed to create term - ' . $term->get_error_message() );
			return 0;
		}
		
		if ( ! is_array( $term ) || ! isset( $term['term_id'] ) ) {
			return 0;
		}
		
		return $term['term_id'];
	}
}

if ( ! function_exists( 'gatherpress_venue_hierarchy_init' ) ) {
	/**
	 * Initialize the plugin.
	 *
	 * Bootstrap function that initializes the main plugin class.
	 * Hooked to 'plugins_loaded' to ensure WordPress core is fully loaded.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	function gatherpress_venue_hierarchy_init(): void {
		GatherPress_Venue_Hierarchy::get_instance();
	}
	add_action( 'plugins_loaded', 'gatherpress_venue_hierarchy_init' );
}