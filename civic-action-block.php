<?php
/**
 * Plugin Name:       Civic Action Box
 * Description:       A Gutenberg block for embedding civic action content with alignment support.
 * Version:           1.1.0
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
$civicActionUpdateChecker->setBranch( 'main' );
$civicActionUpdateChecker->getVcsApi()->enableReleaseAssets();

function civic_action_block_init() {
	register_block_type( __DIR__ . '/build' );
}
add_action( 'init', 'civic_action_block_init' );
