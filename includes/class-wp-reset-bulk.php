<?php
/**
 * Classe principale du plugin
 */
class WP_Reset_Bulk {

    /**
     * Instance unique de la classe
     */
    private static $instance = null;

    /**
     * Objet d'administration
     */
    public $admin;

    /**
     * Objet AJAX
     */
    public $ajax;

    /**
     * Objet Email
     */
    public $email;

    /**
     * Constructeur privé pour empêcher l'instanciation directe
     */
    private function __construct() {
        // Le constructeur est intentionnellement vide
    }

    /**
     * Récupère l'instance unique de la classe
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialise le plugin
     */
    public function run() {
        // Vérifier les dépendances
        if (!$this->check_dependencies()) {
            return;
        }
        
        // Charger les dépendances
        require_once WPRB_PLUGIN_DIR . 'includes/class-wp-reset-admin.php';
        require_once WPRB_PLUGIN_DIR . 'includes/class-wp-reset-ajax.php';
        require_once WPRB_PLUGIN_DIR . 'includes/class-wp-reset-email.php';
        
        // Initialiser les composants
        $this->admin = new WP_Reset_Admin();
        $this->ajax = new WP_Reset_Ajax();
        $this->email = new WP_Reset_Email();
        
        // Charger les traductions
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        
        // Initialiser les hooks
        $this->init_hooks();
    }
    
    /**
     * Initialise les hooks WordPress
     */
    private function init_hooks() {
        // Hooks d'initialisation
        add_action('admin_init', [$this, 'check_version']);
        
        // Actions AJAX
        add_action('wp_ajax_send_reset_emails', [$this->ajax, 'send_reset_emails']);
    }

    /**
     * Vérifie les dépendances requises
     */
    private function check_dependencies() {
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Vérifier la version de PHP
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-error">
                    <p><?php _e('WP Password Reset Bulk nécessite PHP 7.4 ou version ultérieure. Veuillez mettre à jour votre version de PHP.', 'wp-password-reset-bulk'); ?></p>
                </div>
                <?php
            });
            return false;
        }
        
        // Vérifier la version de WordPress
        global $wp_version;
        if (version_compare($wp_version, '5.6', '<')) {
            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-error">
                    <p><?php _e('WP Password Reset Bulk nécessite WordPress 5.6 ou version ultérieure. Veuillez mettre à jour WordPress.', 'wp-password-reset-bulk'); ?></p>
                </div>
                <?php
            });
            return false;
        }
        
        return true;
    }
    
    /**
     * Vérifie la version du plugin et effectue les mises à jour si nécessaire
     */
    public function check_version() {
        $saved_version = get_option('wprb_version', '1.0.0');
        
        if (version_compare($saved_version, WPRB_PLUGIN_VERSION, '<')) {
            // Mettre à jour la version enregistrée
            update_option('wprb_version', WPRB_PLUGIN_VERSION);
            
            // Effectuer les mises à jour nécessaires
            $this->upgrade($saved_version);
        }
    }
    
    /**
     * Effectue les mises à jour du plugin
     */
    private function upgrade($from_version) {
        // Mises à jour futures
    }

    /**
     * Charge les fichiers de traduction
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wp-password-reset-bulk',
            false,
            dirname(plugin_basename(__FILE__), 2) . '/languages/'
        );
    }

    /**
     * Méthode d'activation du plugin
     */
    public static function activate() {
        // Vérifier les capacités
        if (!current_user_can('activate_plugins')) {
            return;
        }
        
        // Créer une option pour suivre la version du plugin
        update_option('wprb_version', WPRB_PLUGIN_VERSION);
    }

    /**
     * Méthode de désactivation du plugin
     */
    public static function deactivate() {
        // Nettoyage si nécessaire
    }

    /**
     * Empêche le clonage de l'instance
     */
    private function __clone() {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'wp-password-reset-bulk'), '2.0.0');
    }

    /**
     * Empêche la désérialisation de l'instance
     */
    public function __wakeup() {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'wp-password-reset-bulk'), '2.0.0');
    }
}
