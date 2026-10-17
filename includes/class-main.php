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

    // Register front-end assets (enqueued on demand by the widgets)
    add_action('elementor/frontend/after_register_scripts', [__CLASS__, 'register_scripts']);
    add_action('elementor/frontend/after_register_styles', [__CLASS__, 'register_styles']);
  }

  /**
   * Register Vimeo Video Slider scripts
   */
  public static function register_scripts() {
    wp_register_script(
      'lumen-evw-vimeo-player',
      LUMEN_EVW_PLUGIN_URL . 'assets/js/vendor/vimeo-player.min.js',
      [],
      '2.30.4',
      true
    );

    wp_register_script(
      'lumen-evw-finish-detector',
      LUMEN_EVW_PLUGIN_URL . 'assets/js/finish-detector.js',
      [],
      LUMEN_EVW_PLUGIN_VERSION,
      true
    );

    wp_register_script(
      'lumen-evw-vimeo-slider',
      LUMEN_EVW_PLUGIN_URL . 'assets/js/vimeo-slider.js',
      ['jquery', 'elementor-frontend', 'swiper', 'lumen-evw-vimeo-player', 'lumen-evw-finish-detector'],
      LUMEN_EVW_PLUGIN_VERSION,
      true
    );
  }

  /**
   * Register Vimeo Video Slider styles
   */
  public static function register_styles() {
    wp_register_style(
      'lumen-evw-vimeo-slider',
      LUMEN_EVW_PLUGIN_URL . 'assets/css/vimeo-slider.css',
      [],
      LUMEN_EVW_PLUGIN_VERSION
    );
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
    require_once LUMEN_EVW_PLUGIN_DIR . 'includes/trait-vimeo-src.php';
    require_once LUMEN_EVW_PLUGIN_DIR . 'widgets/class-vimeo-embed.php';
    require_once LUMEN_EVW_PLUGIN_DIR . 'widgets/class-vimeo-slider.php';
    $widgets_manager->register(new \LumenIQ\Elementor\Vimeo_Embed_Widget());
    $widgets_manager->register(new \LumenIQ\Elementor\Vimeo_Slider_Widget());
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
