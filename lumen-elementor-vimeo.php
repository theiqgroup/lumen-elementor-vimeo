<?php
/**
 * Plugin Name: Vimeo Widget for Elementor
 * Description: Adds a custom Elementor widget that outputs a Vimeo iframe without Elementor rewriting parameters.
 * Version: 1.0.0
 * Author: LumenIQ
 * Author URI: https://www.lumeniq.uk
 * Text Domain: lumen-elementor-vimeo
 * 
 * Requires: Elementor
 * 
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'plugins_loaded', function () {

	// Elementor must be loaded
	if ( ! did_action( 'elementor/loaded' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-warning"><p><strong>Raw Vimeo Widget:</strong> Elementor is not active.</p></div>';
		});
		return;
	}

	// Register widget
	add_action( 'elementor/widgets/register', function( $widgets_manager ) {

		require_once __DIR__ . '/widgets/class-raw-vimeo-widget.php';

		$widgets_manager->register( new \LumenIQ\Elementor\Raw_Vimeo_Widget() );

	} );

} );