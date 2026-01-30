<?php
/**
 * Plugin Name:       GatherPress Location Hierarchy
 * Plugin URI:        https://github.com/carstingaxion/gatherpress-location-hierarchy
 * Description:       Adds hierarchical location taxonomy to GatherPress with automatic geocoding
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires plugins:  gatherpress
 * Author:            carstenbach
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gatherpress-location-hierarchy
 *
 * @package GatherPressLocationHierarchy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

// Constants.
define( 'GATHERPRESS_LOCATION_HIERARCHY_VERSION', current( get_file_data( __FILE__, array( 'Version' ), 'plugin' ) ) );
define( 'GATHERPRESS_LOCATION_HIERARCHY_CORE_PATH', __DIR__ );


/**
 * Adds the GatherPress_Location_Hierarchy namespace to the autoloader.
 *
 * This function hooks into the 'gatherpress_autoloader' filter and adds the
 * GatherPress_Location_Hierarchy namespace to the list of namespaces with its core path.
 *
 * @param array<string, string> $namespaces An associative array of namespaces and their paths.
 * @return array<string, string> Modified array of namespaces and their paths.
 */
function gatherpress_location_hierarchy_autoloader( array $namespaces ): array {
	$namespaces['GatherPress_Location_Hierarchy'] = GATHERPRESS_LOCATION_HIERARCHY_CORE_PATH;

	return $namespaces;
}
add_filter( 'gatherpress_autoloader', 'gatherpress_location_hierarchy_autoloader' );

/**
 * Initialize the plugin.
 *
 * Bootstrap function that starts the plugin by initializing the main class.
 *
 * This function hooks into the 'plugins_loaded' action to ensure that
 * the instances are created once all plugins are loaded,
 * only if the GatherPress plugin is active.
 *
 * @since 0.1.0
 * @return void
 */
function gatherpress_location_hierarchy_setup(): void {
	if ( defined( 'GATHERPRESS_VERSION' ) ) {
		\GatherPress_Location_Hierarchy\Setup::get_instance();
	}
}
add_action( 'plugins_loaded', 'gatherpress_location_hierarchy_setup' );



/**
 * Plugin activation hook.
 *
 * Runs when the plugin is activated, triggering geocoding for all existing events.
 *
 * When plugin is first installed, existing events won't have location terms.
 * This method ensures all events get their hierarchies created automatically upon activation.
 *
 * @since 0.1.0
 * @return void
 */
function gatherpress_location_hierarchy_activate(): void {
	if ( ! class_exists( '\GatherPress_Location_Hierarchy\Setup' ) ) {
		require_once GATHERPRESS_LOCATION_HIERARCHY_CORE_PATH . '/includes/classes/class-setup.php';
		require_once GATHERPRESS_LOCATION_HIERARCHY_CORE_PATH . '/includes/classes/class-builder.php';
		require_once GATHERPRESS_LOCATION_HIERARCHY_CORE_PATH . '/includes/classes/class-geocoder.php';
	}
	$plugin = \Setup::get_instance();
	$plugin->register_location_taxonomy(); // Ensure taxonomy is registered before processing events

	// Clear the permalinks to add our post type's rules to the database.
	flush_rewrite_rules();

	// Query all GatherPress events
	$events = get_posts(
		array(
			'post_type'      => 'gatherpress_event',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		)
	);

	// Trigger save action for each event to geocode and create terms
	foreach ( $events as $event ) {
		// do_action( 'save_post_gatherpress_event', $event->ID, $event, false );
		$plugin->maybe_geocode_event_venue( $event->ID, $event );

		//
		sleep(1); // Be polite to the geocoding API
	}
}

/**
 * Plugin deactivation hook.
 *
 * Runs when the plugin is deactivated, cleaning up all cached geocoding data.
 *
 * Transients consume database space. When plugin is deactivated, the cached
 * geocoding data is no longer needed and should be removed to free resources.
 *
 * @since 0.1.0
 * @return void
 */
function gatherpress_location_hierarchy_deactivate(): void {
	global $wpdb;
	
	// Delete all geocoding transients
	$transients = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( '_transient_gpvh_geocode_' ) . '%'
		)
	);
	
	foreach ( $transients as $transient ) {
		$key = str_replace( '_transient_', '', $transient->option_name );
		delete_transient( $key );
	}

	// Clear the permalinks to remove our post type's rules from the database.
	flush_rewrite_rules();
}

/**
 * Plugin uninstall handler.
 *
 * Removes all plugin data when plugin is deleted via WordPress admin.
 *
 * Complete cleanup when user uninstalls the plugin. Removes taxonomy terms,
 * settings, and any remaining transients to leave no trace in the database.
 *
 * 1. Deletes all location taxonomy terms
 * 2. Deletes plugin settings option
 * 3. Calls deactivate() to clean up transients
 *
 * @since 0.1.0
 * @return void
 */
function gatherpress_location_hierarchy_uninstall(): void {
	// Get all terms in the location taxonomy
	$terms = get_terms(
		array(
			'taxonomy'   => 'gatherpress_location',
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);
	
	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		foreach ( $terms as $term_id ) {
			wp_delete_term( $term_id, 'gatherpress_location' );
		}
	}
	
	// Delete plugin settings
	delete_option( 'gatherpress_location_hierarchy_defaults' );
	
	// Clean up transients
	gatherpress_location_hierarchy_deactivate();
}
// Register activation hook
register_activation_hook( __FILE__, 'gatherpress_location_hierarchy_activate' );

// Register deactivation hook
register_deactivation_hook( __FILE__, 'gatherpress_location_hierarchy_deactivate' );

// Register uninstall hook
register_uninstall_hook( __FILE__, 'gatherpress_location_hierarchy_uninstall' );