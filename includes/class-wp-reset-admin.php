<?php
/**
 * Gestion de l'interface d'administration du plugin
 */
class WP_Reset_Admin {
    
    /**
     * Initialise les hooks d'administration
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    /**
     * Ajoute le menu d'administration
     */
    public function add_admin_menu() {
        add_management_page(
            __('Réinitialisation des mots de passe en masse', 'wp-password-reset-bulk'),
            __('Réinitialisation MDP', 'wp-password-reset-bulk'),
            'manage_options',
            'wp-password-reset-bulk',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Charge les assets de l'administration
     */
    public function enqueue_admin_assets($hook) {
        if ('tools_page_wp-password-reset-bulk' !== $hook) {
            return;
        }
        
        // Styles
        wp_enqueue_style(
            'wprb-admin',
            WPRB_PLUGIN_URL . 'assets/css/admin.css',
            [],
            WPRB_PLUGIN_VERSION
        );
        
        // Scripts
        wp_enqueue_script(
            'wprb-admin',
            WPRB_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            WPRB_PLUGIN_VERSION,
            true
        );
        
        // Localisation des chaînes pour JavaScript
        wp_localize_script('wprb-admin', 'wprb_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wprb_nonce'),
            'site_name' => get_bloginfo('name'),
            'i18n' => [
                'confirm_send' => __('Êtes-vous sûr de vouloir envoyer les emails de réinitialisation ?', 'wp-password-reset-bulk'),
                'sending' => __('Envoi en cours...', 'wp-password-reset-bulk'),
                'complete' => __('Terminé !', 'wp-password-reset-bulk'),
                'error' => __('Une erreur est survenue', 'wp-password-reset-bulk'),
                'email_preview_title' => __('Aperçu de l\'email de réinitialisation', 'wp-password-reset-bulk'),
                'email_preview_heading' => __('Réinitialisation de votre mot de passe', 'wp-password-reset-bulk'),
                'email_preview_greeting' => __('Bonjour %s,', 'wp-password-reset-bulk'),
                'email_preview_instructions' => __('Quelqu\'un a demandé la réinitialisation du mot de passe pour votre compte. Si vous n\'êtes pas à l\'origine de cette demande, vous pouvez ignorer cet email.', 'wp-password-reset-bulk'),
                'email_preview_button' => __('Réinitialiser mon mot de passe', 'wp-password-reset-bulk'),
                'email_preview_alt_link' => __('Si le bouton ne fonctionne pas, vous pouvez copier et coller le lien suivant dans votre navigateur :', 'wp-password-reset-bulk'),
                'email_preview_expiry' => __('Ce lien expirera dans 24 heures.', 'wp-password-reset-bulk'),
                'email_preview_ignore' => __('Si vous n\'avez pas demandé de réinitialisation de mot de passe, aucune action n\'est requise.', 'wp-password-reset-bulk'),
                'email_preview_signature' => __('Cordialement', 'wp-password-reset-bulk'),
                'select_at_least_one_role' => __('Veuillez sélectionner au moins un rôle.', 'wp-password-reset-bulk'),
                'confirm_test_mode' => __('Vous êtes en mode test. Les emails seront envoyés uniquement aux adresses de test.', 'wp-password-reset-bulk')
            ]
        ]);
    }
    
    /**
     * Affiche la page d'administration
     */
    public function render_admin_page() {
        // Vérifier les capacités
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Récupérer les statistiques des utilisateurs
        $user_stats = $this->get_user_stats();
        
        // Inclure le template
        include WPRB_PLUGIN_DIR . 'templates/admin-page.php';
    }
    
    /**
     * Récupère les statistiques des utilisateurs
     */
    private function get_user_stats() {
        $stats = [
            'total' => 0,
            'by_role' => []
        ];
        
        // Compter les utilisateurs par rôle
        $user_count = count_users();
        
        foreach ($user_count['avail_roles'] as $role => $count) {
            // Exclure les administrateurs
            if ('administrator' === $role) {
                continue;
            }
            
            $stats['by_role'][$role] = $count;
            $stats['total'] += $count;
        }
        
        return $stats;
    }
}
