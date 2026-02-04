<?php
/**
 * Main plugin controller that manages the hierarchical location taxonomy system.
 *
 * @package GatherPressLocationHierarchy
 */

declare(strict_types=1);

namespace GatherPress_Location_Hierarchy;

/**
 * Main plugin class using Singleton pattern.
 *
 * Core plugin controller that manages the hierarchical location taxonomy system.
 *
 * Provides a single point of initialization and coordination for all plugin functionality,
 * ensuring only one instance exists (Singleton pattern) to prevent duplicate registrations and
 * conflicts. This is critical for WordPress hooks and taxonomy registration.
 *
 * Registers WordPress hooks on instantiation, coordinates geocoding and hierarchy building
 * through specialized singleton classes (Geocoder and Hierarchy_Builder), and manages the custom
 * taxonomy lifecycle. Uses the Singleton pattern to guarantee single instantiation via
 * get_instance() static method and private constructor.
 *
 * @since 0.1.0
 */
class Setup {
	
	/**
	 * Single instance of the class.
	 *
	 * Holds the single instance of this class.
	 *
	 * Part of the Singleton pattern implementation to ensure only one instance
	 * exists throughout the WordPress request lifecycle.
	 *
	 * @since 0.1.0
	 * @var Setup|null
	 */
	private static $instance = null;
	
	/**
	 * Taxonomy name for hierarchical locations.
	 *
	 * The slug identifier for the custom location taxonomy.
	 *
	 * Centralized constant prevents typos and makes refactoring easier.
	 * This taxonomy stores geographical hierarchy (continent > country > state > city > street > street-number).
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private $taxonomy = 'gatherpress_location';
	
	/**
	 * Get singleton instance.
	 *
	 * Returns the single instance of the class, creating it if necessary.
	 *
	 * @since 0.1.0
	 * @return Setup The singleton instance.
	 */
	public static function get_instance(): Setup {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Constructor.
	 *
	 * Initializes the plugin by registering all WordPress hooks.
	 *
	 * Private to enforce Singleton pattern - prevents external instantiation.
	 * All WordPress integration points must be registered during construction to ensure
	 * they're active when WordPress processes its action/filter queues.
	 *
	 * Adds action/filter callbacks for:
	 * - init (priority 5): Register taxonomy EARLY to prevent rewrite rule conflicts
	 * - init (priority 10): Register block at default priority
	 * - admin_menu: Add settings page
	 * - admin_init: Register settings
	 * - save_post_gatherpress_event (priority 20): Trigger geocoding after event save
	 * - enqueue_block_editor_assets: Localize script with filter data
	 * - wp_head (priority 1): Add canonical tags for taxonomy archives
	 *
	 * Priority 5 for taxonomy registration ensures it runs before WordPress's default
	 * rewrite rules (priority 10), preventing attachment rule conflicts.
	 * Priority 20 ensures GatherPress core has saved venue data first (default priority 10).
	 * Priority 1 for wp_head ensures canonical tags appear early in <head>.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {
		// Register taxonomy early (priority 5) to prevent rewrite rule conflicts.
		add_action( 'init', array( $this, 'register_location_taxonomy' ), 5 );
		// Register block at default priority.
		add_action( 'init', array( $this, 'register_block' ) );
		// add_action( 'init', array( $this, 'register_block_templates' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'save_post_gatherpress_event', array( $this, 'maybe_geocode_event_venue' ), 20, 2 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'localize_block_editor_script' ) );
		// Add canonical URL handling for taxonomy archives.
		add_action( 'wp_head', array( $this, 'add_canonical_for_single_child_terms' ), 1 );
	}
	
	/**
	 * Add canonical URL for terms with only one child.
	 *
	 * Adds canonical link tag to taxonomy archive pages when a term has only one child,
	 * pointing to the child term's archive to consolidate SEO value.
	 *
	 * When a parent term has only one child, both archives show identical events,
	 * creating duplicate content issues. Canonical tags tell search engines which URL is
	 * the preferred version, consolidating ranking signals and preventing dilution.
	 *
	 * 1. Checks if we're on a location taxonomy archive page
	 * 2. Gets the current queried term
	 * 3. Queries for direct children of this term
	 * 4. If exactly one child exists:
	 *    - Gets the child term's archive URL
	 *    - Outputs canonical link tag pointing to child
	 * 5. This creates a chain: grandparent → parent → child (leaf)
	 *    Each level canonicals to its single child until reaching the leaf
	 *
	 * Example scenario:
	 * - Europe has only Germany as child
	 * - Germany has only Berlin as child
	 * - Berlin has 5 events
	 * Result:
	 * - /events/in/europe/ shows: <link rel="canonical" href="/events/in/europe/germany/" />
	 * - /events/in/europe/germany/ shows: <link rel="canonical" href="/events/in/europe/germany/berlin/" />
	 * - /events/in/europe/germany/berlin/ is canonical (no tag needed)
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function add_canonical_for_single_child_terms(): void {
		// Only run on location taxonomy archives.
		if ( ! is_tax( $this->taxonomy ) ) {
			return;
		}
		
		$current_term = get_queried_object();
		
		if ( ! $current_term instanceof \WP_Term ) {
			return;
		}
		
		// Get direct children of this term.
		$child_terms = get_terms(
			array(
				'taxonomy'   => $this->taxonomy,
				'parent'     => $current_term->term_id,
				'hide_empty' => false,
				'number'     => 2, // Only need to know if there's 1 or more.
			)
		);
		
		if ( is_wp_error( $child_terms ) ) {
			return;
		}
		
		// If exactly one child exists, add canonical to that child.
		if ( count( $child_terms ) === 1 ) {
			$child_term = $child_terms[0];
			$child_url  = get_term_link( $child_term );
			
			if ( ! is_wp_error( $child_url ) ) {
				printf(
					'<link rel="canonical" href="%s" />' . "\n",
					esc_url( $child_url )
				);
			}
		}
	}
	
	/**
	 * Get allowed hierarchy levels.
	 *
	 * Returns the configured range of hierarchy levels to use.
	 *
	 * @since 0.1.0
	 * @return array{0: int, 1: int} Array with [min_level, max_level].
	 */
	public function get_allowed_levels(): array {
		/**
		 * Filter the allowed hierarchy levels.
		 *
		 * Allows filtering which levels are saved and displayed, providing flexibility
		 * for different use cases (e.g., only continent to city, or only city to street number).
		 *
		 * Level mapping:
		 * - 1 = Continent
		 * - 2 = Country  
		 * - 3 = State
		 * - 4 = City
		 * - 5 = Street
		 * - 6 = Street Number
		 * 
		 * Common configurations:
		 * - Continent only: Levels 1-1
		 * - Country through City: Levels 2-4
		 * - City and Street: Levels 4-5
		 * - Full hierarchy: Levels 1-6 (plugin default)
		 * 
		 * @example
		 * ```php
		 * add_filter( 'gatherpress_location_hierarchy_levels', function() {
		 *     return [2, 4]; // Only Country, State, City
		 * } );
		 * ```
		 *
		 * @since 0.1.0
		 *
		 * @param array $levels Array with [min_level, max_level] integers.
		 */
		$levels = apply_filters( 'gatherpress_location_hierarchy_levels', array( 1, 6 ) );

		// Defaults
		$min = 1;
		$max = 6;

		// @phpstan-ignore-next-line
		if ( is_array( $levels ) ) {
			if ( isset( $levels[0] ) && is_numeric( $levels[0] ) ) {
				$min = (int) $levels[0];
			}

			if ( isset( $levels[1] ) && is_numeric( $levels[1] ) ) {
				$max = (int) $levels[1];
			}
		}

		// Clamp to allowed bounds (1–6)
		$min = max( 1, min( 6, $min ) );
		$max = max( 1, min( 6, $max ) );

		// Ensure logical order
		if ( $min > $max ) {
			[ $min, $max ] = [ $max, $min ];
		}

		return array( $min, $max );
	}
	
	/**
	 * Localize block editor script with filter data.
	 *
	 * Makes PHP filter values available to JavaScript in the block editor.
	 *
	 * The editor needs to know the allowed hierarchy levels to configure the
	 * dual-range control properly. Using wp_localize_script() is the standard WordPress
	 * way to pass PHP data to JavaScript without creating custom REST endpoints.
	 *
	 * Uses wp_localize_script() to attach data to the block's editor script.
	 * The data becomes available in JavaScript as window.gatherPressLocationHierarchy.
	 * Called on enqueue_block_editor_assets hook to ensure it runs after script registration.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function localize_block_editor_script(): void {
		// Get the allowed levels from the filter.
		[ $min_level, $max_level ] = $this->get_allowed_levels();
		
		// Localize the script with the filter data.
		wp_localize_script(
			'gatherpress-location-hierarchy-editor-script',
			'gatherPressLocationHierarchy',
			array(
				'allowedLevels' => array(
					'min' => $min_level,
					'max' => $max_level,
				),
			)
		);
	}
	
	/**
	 * Register the hierarchical location taxonomy.
	 *
	 * Creates a hierarchical taxonomy for organizing venues by geographical location.
	 *
	 * WordPress's default taxonomy system requires explicit registration before use.
	 * Hierarchical structure allows parent-child relationships (Europe > Germany > Bavaria > Munich),
	 * enabling filtering at any level and proper breadcrumb-style display.
	 *
	 * Uses register_taxonomy() with carefully configured args:
	 * - hierarchical: true - Enables parent-child relationships like categories
	 * - show_in_rest: true - Exposes to Gutenberg block editor and REST API
	 * - orderby: parent, order: ASC - Ensures terms display in geographical hierarchy order
	 * - rewrite: hierarchical - Creates pretty URLs like /events/in/europe/germany/
	 *
	 * The 'args' parameter contains WP_Term_Query args that control how terms are retrieved
	 * globally, ensuring consistent hierarchical ordering throughout WordPress.
	 *
	 * Registered at priority 5 (early) to ensure rewrite rules are processed before WordPress's
	 * default attachment rules, preventing URL conflicts.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_location_taxonomy(): void {
		$visibility = (
			( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) ||
			in_array( wp_get_environment_type(), array( 'local', 'development' ), true )
		) ? true : false;

		$labels                        = array(
			'name'              => __( 'Locations', 'gatherpress-location-hierarchy' ),
			'singular_name'     => __( 'Location', 'gatherpress-location-hierarchy' ),
			'search_items'      => __( 'Search Locations', 'gatherpress-location-hierarchy' ),
			'all_items'         => __( 'All Locations', 'gatherpress-location-hierarchy' ),
			'parent_item'       => __( 'Parent Location', 'gatherpress-location-hierarchy' ),
			'parent_item_colon' => __( 'Parent Location:', 'gatherpress-location-hierarchy' ),
			'edit_item'         => __( 'Edit Location', 'gatherpress-location-hierarchy' ),
			'update_item'       => __( 'Update Location', 'gatherpress-location-hierarchy' ),
			'add_new_item'      => __( 'Add New Location', 'gatherpress-location-hierarchy' ),
			'new_item_name'     => __( 'New Location Name', 'gatherpress-location-hierarchy' ),
			'menu_name'         => __( 'Location Hierarchy', 'gatherpress-location-hierarchy' ),
		);
		$wp_term_query_args            = array();
		$wp_term_query_args['orderby'] = 'parent';
		$wp_term_query_args['order']   = 'ASC';
		
		if ( class_exists( 'GatherPress\Core\Settings' ) ) {
			$settings    = \GatherPress\Core\Settings::get_instance();
			$events_slug = $settings->get_value( 'general', 'urls', 'events' );
		}
		$events_slug   = ! empty( $events_slug ) && is_string( $events_slug ) ? $events_slug : '';
		$location_slug = $events_slug . '/in';

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => $visibility,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'         => $location_slug,
				'hierarchical' => true,
			),
			'sort'              => true,
			'args'              => $wp_term_query_args,
		);
		
		register_taxonomy( $this->taxonomy, array( 'gatherpress_event' ), $args );
	}
	
	
	/**
	 * Register the location hierarchy block.
	 *
	 * Registers the Gutenberg block for displaying location hierarchies.
	 *
	 * Uses register_block_type() with build directory path. WordPress automatically
	 * loads block.json from that directory, which defines the block's metadata, scripts,
	 * and render callback (render.php).
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_block(): void {
		register_block_type( GATHERPRESS_LOCATION_HIERARCHY_CORE_PATH . '/build/' );
	}
	
	/**
	 * Register a block template for the taxonomy.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_block_templates(): void {
		register_block_template(
			'gatherpress-locations-templates//taxonomy-gatherpress_location',
			[
				'title'       => __( 'Location Archive', 'gatherpress-location-hierarchy' ),
				'description' => __( 'Displays an archive of events with location-terms.', 'gatherpress-location-hierarchy' ),
				'content'     => $this->get_template_content( 'taxonomy-gatherpress_location.php' ),
			] 
		);
	}

	/**
	 * Get template content.
	 *
	 * Loads a template file and returns its content as a string.
	 *
	 * @since 0.1.0
	 * @param string $template Template filename.
	 * @return string Template content.
	 */
	public function get_template_content( $template ): string {
		ob_start();
		include GATHERPRESS_LOCATION_HIERARCHY_CORE_PATH . "/templates/{$template}";
		return (string) ob_get_clean();
	}

	/**
	 * Add admin menu for plugin settings.
	 *
	 * Creates a settings page under WordPress Settings menu.
	 *
	 * Provides UI for configuring default geographic locations that can be used
	 * when venue addresses don't contain complete information or as fallbacks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function add_admin_menu(): void {
		add_options_page(
			__( 'GatherPress Location', 'gatherpress-location-hierarchy' ),
			__( 'GatherPress Location', 'gatherpress-location-hierarchy' ),
			'manage_options',
			'gatherpress-location-hierarchy',
			array( $this, 'render_admin_page' )
		);
	}
	
	/**
	 * Register plugin settings.
	 *
	 * Registers settings with WordPress Settings API for storing default location values.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'gatherpress_location_hierarchy',
			'gatherpress_location_hierarchy_defaults',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(
					'continent'     => '',
					'country'       => '',
					'state'         => '',
					'city'          => '',
					'street'        => '',
					'street_number' => '',
				),
			)
		);
	}
	
	/**
	 * Sanitize settings input.
	 *
	 * Validates and sanitizes user input from settings form before saving.
	 *
	 * @since 0.1.0
	 * @param mixed $input Raw settings input from form submission.
	 * @return array<string, string> Sanitized settings array with keys: continent, country, state, city, street, street_number.
	 */
	public function sanitize_settings( $input ): array {
		if ( ! is_array( $input ) ) {
			$input = array();
		}
		
		return array(
			'continent'     => sanitize_text_field( $input['continent'] ?? '' ),
			'country'       => sanitize_text_field( $input['country'] ?? '' ),
			'state'         => sanitize_text_field( $input['state'] ?? '' ),
			'city'          => sanitize_text_field( $input['city'] ?? '' ),
			'street'        => sanitize_text_field( $input['street'] ?? '' ),
			'street_number' => sanitize_text_field( $input['street_number'] ?? '' ),
		);
	}
	
	/**
	 * Render the admin settings page.
	 *
	 * Outputs the HTML form for configuring default location settings.
	 *
	 * Provides user interface for administrators to set default geographical terms
	 * used as fallbacks during geocoding or when venue addresses are incomplete.
	 *
	 * Form submits to options.php which handles validation and saving via Settings API.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function render_admin_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		$defaults = get_option(
			'gatherpress_location_hierarchy_defaults',
			array(
				'continent'     => '',
				'country'       => '',
				'state'         => '',
				'city'          => '',
				'street'        => '',
				'street_number' => '',
			) 
		);
		
		if ( ! is_array( $defaults ) ) {
			$defaults = array(
				'continent'     => '',
				'country'       => '',
				'state'         => '',
				'city'          => '',
				'street'        => '',
				'street_number' => '',
			);
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'gatherpress_location_hierarchy' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="default_continent"><?php esc_html_e( 'Default Continent', 'gatherpress-location-hierarchy' ); ?></label>
						</th>
						<td>
							<input type="text" id="default_continent" name="gatherpress_location_hierarchy_defaults[continent]" 
								value="<?php echo esc_attr( $defaults['continent'] ?? '' ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Default continent for new venues', 'gatherpress-location-hierarchy' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="default_country"><?php esc_html_e( 'Default Country', 'gatherpress-location-hierarchy' ); ?></label>
						</th>
						<td>
							<input type="text" id="default_country" name="gatherpress_location_hierarchy_defaults[country]" 
								value="<?php echo esc_attr( $defaults['country'] ?? '' ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Default country for new venues', 'gatherpress-location-hierarchy' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="default_state"><?php esc_html_e( 'Default State/Region', 'gatherpress-location-hierarchy' ); ?></label>
						</th>
						<td>
							<input type="text" id="default_state" name="gatherpress_location_hierarchy_defaults[state]" 
								value="<?php echo esc_attr( $defaults['state'] ?? '' ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Default state or region for new venues', 'gatherpress-location-hierarchy' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="default_city"><?php esc_html_e( 'Default City', 'gatherpress-location-hierarchy' ); ?></label>
						</th>
						<td>
							<input type="text" id="default_city" name="gatherpress_location_hierarchy_defaults[city]" 
								value="<?php echo esc_attr( $defaults['city'] ?? '' ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Default city for new venues', 'gatherpress-location-hierarchy' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="default_street"><?php esc_html_e( 'Default Street', 'gatherpress-location-hierarchy' ); ?></label>
						</th>
						<td>
							<input type="text" id="default_street" name="gatherpress_location_hierarchy_defaults[street]" 
								value="<?php echo esc_attr( $defaults['street'] ?? '' ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Default street for new venues', 'gatherpress-location-hierarchy' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="default_street_number"><?php esc_html_e( 'Default Street Number', 'gatherpress-location-hierarchy' ); ?></label>
						</th>
						<td>
							<input type="text" id="default_street_number" name="gatherpress_location_hierarchy_defaults[street_number]" 
								value="<?php echo esc_attr( $defaults['street_number'] ?? '' ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Default street number for new venues', 'gatherpress-location-hierarchy' ); ?></p>
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
	 * Triggers geocoding and location term creation when a GatherPress event is saved.
	 *
	 * Automates the location hierarchy creation process so users don't have to manually
	 * create taxonomy terms. Checks for existing terms to avoid re-geocoding on every save,
	 * but will recreate terms if they were manually deleted.
	 *
	 * 1. Early returns for autosave, non-event posts, etc.
	 * 2. Gets venue information via GatherPress
	 * 3. Checks if location terms already exist using wp_get_object_terms()
	 * 4. Only geocodes if terms are missing/deleted, preventing unnecessary API calls
	 * 5. Priority 20 ensures this runs AFTER GatherPress saves venue data (priority 10)
	 *
	 * Flow example:
	 * - User saves event with venue "123 Main St, Munich, Germany"
	 * - GatherPress saves venue data (priority 10)
	 * - This function runs (priority 20)
	 * - Checks for existing terms (none found)
	 * - Calls geocode_and_create_hierarchy()
	 * - Nominatim API returns coordinates + address components
	 * - Creates terms: Europe > Germany > Bavaria > Munich > Main St > 123
	 * - Associates all terms with the event
	 *
	 * @since 0.1.0
	 * @param int      $post_id Post ID of the event.
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
			error_log( 'GatherPress Location Hierarchy: GatherPress Event class not found' );
			return;
		}
		
		$event      = new \GatherPress\Core\Event( $post_id );
		$venue_info = $event->get_venue_information();
		
		if ( empty( $venue_info['full_address'] ) || ! is_string( $venue_info['full_address'] ) ) {
			return;
		}
		
		// Check if location terms already exist for this event.
		$existing_terms = wp_get_object_terms(
			$post_id,
			$this->taxonomy,
			array( 'fields' => 'ids' )
		);
		
		// If terms already exist and are valid, skip geocoding.
		if ( ! is_wp_error( $existing_terms ) && ! empty( $existing_terms ) ) {
			return;
		}
		// Terms don't exist or were deleted - geocode and create them.
		$this->geocode_and_create_hierarchy( $post_id, $venue_info['full_address'] );
	}
	
	/**
	 * Geocode address and create location hierarchy.
	 *
	 * Coordinates the geocoding process and subsequent hierarchy term creation.
	 *
	 * Separates concerns by delegating to specialized singleton classes:
	 * Geocoder handles API communication, Hierarchy_Builder handles term creation.
	 * This keeps the main class focused on coordination rather than implementation details.
	 *
	 * 1. Gets Geocoder singleton instance
	 * 2. Geocodes address via Nominatim API (or retrieves from cache)
	 * 3. If geocoding succeeds, gets Hierarchy_Builder singleton
	 * 4. Passes location data to builder for term creation
	 * 5. Logs error if geocoding fails
	 *
	 * Example flow:
	 * Input: $post_id=123, $address="Marienplatz 1, Munich, Germany"
	 * Geocoder returns: ['continent'=>'Europe', 'country'=>'Germany', 'state'=>'Bavaria', 'city'=>'Munich', 'street'=>'Marienplatz', 'street_number'=>'1']
	 * Hierarchy_Builder creates: Europe (parent:0) > Germany (parent:Europe_ID) > Bavaria (parent:Germany_ID) > Munich (parent:Bavaria_ID) > Marienplatz (parent:Munich_ID) > 1 (parent:Marienplatz_ID)
	 * All term IDs associated with post 123
	 *
	 * @since 0.1.0
	 * @param int    $post_id Post ID to associate terms with.
	 * @param string $address Address to geocode (e.g., "123 Main St, City, Country").
	 * @return void
	 */
	private function geocode_and_create_hierarchy( int $post_id, string $address ): void {
		$geocoder = Geocoder::get_instance();
		$location = $geocoder->geocode( $address );
		
		if ( ! $location ) {
			error_log( 'GatherPress Location Hierarchy: Failed to geocode address for event ' . $post_id );
			return;
		}
		
		$hierarchy_builder = Builder::get_instance();
		$hierarchy_builder->create_hierarchy_terms( $post_id, $location, $this->taxonomy );
	}
}
