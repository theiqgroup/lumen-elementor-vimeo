<?php
/**
 * Plugin Name: Elementor Vimeo Widget
 * Description: Adds a Elementor Widget that allows embedding of Vimeo Videos without any Elementor parameter filtering.
 * Version: 1.0.2
 * Author: LumenIQ
 * Author URI: https://www.lumeniq.uk
 * Text Domain: lumen-evw-embed
 * 
 * Requires Plugins: elementor
 * 
 */

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('LUMEN_EVW_PLUGIN_FILE', __FILE__);
define('LUMEN_EVW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LUMEN_EVW_PLUGIN_URL', plugin_dir_url(__FILE__));

// Extract version from plugin header
$plugin_file_content = file_get_contents(__FILE__);
if (preg_match('/Version:\s*(.+?)$/m', $plugin_file_content, $matches)) {
  define('LUMEN_EVW_PLUGIN_VERSION', trim($matches[1]));
}

// Load plugin classes
require_once LUMEN_EVW_PLUGIN_DIR . 'includes/class-main.php';

// Initialize the plugin
LumenIQ_EVW::init();

/**
 * Plugin Updater
 */
require_once __DIR__ . '/includes/GitHubUpdater.php';
new GitHubUpdater( __FILE__, 'theiqgroup/lumen-elementor-vimeo', [
    'proxy'  => 'https://lumeniq-wordpress-updates.azurewebsites.net',
    'secret' => '0035326dd479-d261a076lumeniq',
] );