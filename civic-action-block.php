<?php
/**
 * Plugin Name:       Civic Action Box
 * Description:       A Gutenberg block for embedding civic action content with alignment support.
 * Version:           1.5.2
 * Author:            Planet Detroit
 * License:           GPL-2.0-or-later
 * Text Domain:       civic-action-block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Auto-update from GitHub — checks Planet-Detroit/civic-action-block for new releases
require __DIR__ . '/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$civicActionUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/Planet-Detroit/civic-action-block/',
	__FILE__,
	'civic-action-block'
);
// Use GitHub releases (not branch) for version detection + release assets for download
$civicActionUpdateChecker->getVcsApi()->enableReleaseAssets();

// Keeps Newspack's in-story ads out of the paragraphs beside a floated box.
require __DIR__ . '/includes/ad-placement.php';

function civic_action_block_init() {
	$block_type = register_block_type( __DIR__ . '/build' );
	civic_action_block_expose_ad_plugin_to_editor( $block_type );
	// Ask Planet Detroit reader question form — standalone block
	$ask_pd_dir = __DIR__ . '/build/ask-planet-detroit';
	if ( file_exists( $ask_pd_dir . '/block.json' ) ) {
		register_block_type( $ask_pd_dir );
	}
}
add_action( 'init', 'civic_action_block_init' );
