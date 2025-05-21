<?php
/**
 * Plugin Name: Envoi en masse - Réinitialisation des mots de passe
 * Plugin URI: 
 * Description: Permet d'envoyer des emails de réinitialisation de mot de passe en masse aux utilisateurs
 * Version: 2.0.0
 * Author: Votre Nom
 * Author URI: 
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
