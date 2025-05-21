<?php
/**
 * Plugin Name: Bulk Password Reset
 * Plugin URI: https://github.com/AkremBelkahla/wp-password-reset-bulk
 * Description: Send password reset emails in bulk to WordPress users. Easily manage password resets for multiple users at once.
 * Version: 2.0.0
 * Author: Akrem Belkahla
 * Author URI: https://infinityweb.tn
 * Text Domain: wp-password-reset-bulk
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Si ce fichier est appelé directement, on sort
if (!defined('ABSPATH')) {
    exit;
}

// Définition des constantes du plugin
define('WPRB_PLUGIN_VERSION', '2.0.0');
define('WPRB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPRB_PLUGIN_URL', plugin_dir_url(__FILE__));

// Inclure les fichiers nécessaires
require_once WPRB_PLUGIN_DIR . 'includes/class-wp-reset-bulk.php';
require_once WPRB_PLUGIN_DIR . 'includes/class-wp-reset-admin.php';
require_once WPRB_PLUGIN_DIR . 'includes/class-wp-reset-ajax.php';
require_once WPRB_PLUGIN_DIR . 'includes/class-wp-reset-email.php';

// Initialisation du plugin
function wprb_init() {
    $plugin = WP_Reset_Bulk::get_instance();
    $plugin->run();
}
add_action('plugins_loaded', 'wprb_init');

// Activation du plugin
register_activation_hook(__FILE__, ['WP_Reset_Bulk', 'activate']);

// Désactivation du plugin
register_deactivation_hook(__FILE__, ['WP_Reset_Bulk', 'deactivate']);
