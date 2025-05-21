<?php
/**
 * Plugin Name: Bulk Password Reset for WordPress
 * Plugin URI: https://github.com/AkremBelkahla/wp-bulk-password-reset
 * Description: A simple and efficient way to send password reset emails to multiple users at once. Filter users by role and preview emails before sending.
 * Version: 2.0.0
 * Author: Akrem Belkahla
 * Author URI: https://infinityweb.tn
 * Text Domain: wp-bulk-password-reset
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: https://github.com/AkremBelkahla/wp-bulk-password-reset
 * 
 * @package WPBulkPasswordReset
 * @author Akrem Belkahla
 * @link https://infinityweb.tn
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
