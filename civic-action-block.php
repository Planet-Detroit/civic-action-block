<?php
/**
 * Plugin Name:       Civic Action Box
 * Description:       A Gutenberg block for embedding civic action content with alignment support.
 * Version:           1.0.0
 * Author:            Planet Detroit
 * License:           GPL-2.0-or-later
 * Text Domain:       civic-action-block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function civic_action_block_init() {
	register_block_type( __DIR__ . '/build' );
}
add_action( 'init', 'civic_action_block_init' );
