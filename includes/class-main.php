<?php
/**
 * Main Plugin Class - Orchestrates all plugin functionality
 */

if (!defined('ABSPATH')) exit;

class LumenIQ_EVW {

  /**
   * Initialize the plugin
   */
  public static function init() {
    add_action('plugins_loaded', [__CLASS__, 'on_plugins_loaded']);
    register_activation_hook(LUMEN_EVW_PLUGIN_FILE, [__CLASS__, 'activate']);
    register_deactivation_hook(LUMEN_EVW_PLUGIN_FILE, [__CLASS__, 'deactivate']);
  }

  /**
   * Runs on plugins_loaded hook
   */
  public static function on_plugins_loaded() {
    // Elementor must be loaded
    if (!did_action('elementor/loaded')) {
      add_action('admin_notices', [__CLASS__, 'elementor_not_active_notice']);
      return;
    }

    // Register widgets
    add_action('elementor/widgets/register', [__CLASS__, 'register_widgets']);
  }

  /**
   * Display notice if Elementor is not active
   */
  public static function elementor_not_active_notice() {
    echo '<div class="notice notice-warning"><p><strong>Elementor Vimeo Widget:</strong> Elementor is not active.</p></div>';
  }

  /**
   * Register Elementor widgets
   */
  public static function register_widgets($widgets_manager) {
    require_once LUMEN_EVW_PLUGIN_DIR . 'widgets/class-vimeo-embed.php';
    $widgets_manager->register(new \LumenIQ\Elementor\Vimeo_Embed_Widget());
  }

  /**
   * Plugin activation
   */
  public static function activate() {
    // Placeholder for any activation routines
  }

  /**
   * Plugin deactivation
   */
  public static function deactivate() {
    // Placeholder for any deactivation routines
  }
}
