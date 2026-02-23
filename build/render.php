<?php
/**
 * Block renderer for the location hierarchy display block.
 *
 * This file serves as both the render callback for the Gutenberg block
 * and contains the singleton renderer class that handles all display logic.
 *
 * Separating rendering logic into its own class (Singleton pattern) ensures:
 * - Consistent rendering behavior across multiple block instances
 * - Testable, maintainable code separate from block registration
 * - No duplicate term queries when multiple blocks render on same page
 *
 * WordPress calls this file as the render callback (defined in block.json).
 * The file defines the renderer class, instantiates it, and calls its render() method.
 * The renderer retrieves event venue information, builds hierarchical location paths,
 * and outputs formatted HTML with optional term links.
 *
 * @package GatherPressLocationHierarchy
 * @since 0.1.0
 */

declare(strict_types=1);

namespace GatherPress_Location_Hierarchy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Extract and sanitize block attributes.
 *
 * @var array{
 *   startLevel: int,
 *   endLevel: int,
 *   enableLinks: bool,
 *   showVenue: bool,
 *   separator: string,
 * } $attributes
 * @var string $content
 * @var \WP_Block $block
 */
$gatherpress_location_hierarchy_block = Block_Renderer::get_instance();
echo $gatherpress_location_hierarchy_block->render( $attributes, $content, $block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
