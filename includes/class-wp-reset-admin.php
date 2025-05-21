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

        // Enqueue Tailwind CSS from CDN (for demo purposes)
        // In production, consider using a build process with PostCSS
        wp_enqueue_style(
            'tailwindcss',
            'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css',
            [],
            '2.2.19'
        );

        // Enqueue Alpine.js for interactivity
        wp_enqueue_script(
            'alpinejs',
            'https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.8.2/dist/alpine.min.js',
            [],
            '2.8.2',
            true
        );

        // Custom styles that override Tailwind
        wp_enqueue_style(
            'wpbr-admin',
            WPRB_PLUGIN_URL . 'assets/css/admin.css',
            ['tailwindcss'],
            WPRB_PLUGIN_VERSION
        );
        
        // Main admin script
        wp_enqueue_script(
            'wpbr-admin',
            WPRB_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'alpinejs'],
            WPRB_PLUGIN_VERSION,
            true
        );
        
        // Localize strings for JavaScript
        wp_localize_script('wpbr-admin', 'wpbr_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'admin_url' => admin_url(),
            'site_name' => get_bloginfo('name'),
            'nonce' => wp_create_nonce('wpbr_nonce'),
            'i18n' => [
                'confirm_send' => __('Are you sure you want to send password reset emails to the selected users?', 'wp-bulk-password-reset'),
                'confirm_test_mode' => __('You are in test mode. Emails will only be sent to test email addresses.', 'wp-bulk-password-reset'),
                'select_at_least_one_role' => __('Please select at least one user role.', 'wp-bulk-password-reset'),
                'sending' => __('Sending...', 'wp-bulk-password-reset'),
                'stopping' => __('Stopping...', 'wp-bulk-password-reset'),
                'complete' => __('Complete!', 'wp-bulk-password-reset'),
                'error' => __('An error occurred', 'wp-bulk-password-reset'),
                'ajax_error' => __('AJAX Error:', 'wp-bulk-password-reset'),
                'unknown_error' => __('An unknown error occurred. Please try again.', 'wp-bulk-password-reset'),
                'send_emails' => __('Send Reset Emails', 'wp-bulk-password-reset'),
                'send_emails_again' => __('Send Emails Again', 'wp-bulk-password-reset'),
                'email_preview_title' => __('Password Reset', 'wp-bulk-password-reset'),
                'email_preview_heading' => __('Reset Your Password', 'wp-bulk-password-reset'),
                'email_preview_greeting' => __('Hello %s,', 'wp-bulk-password-reset'),
                'email_preview_instructions' => __('You have requested to reset your password. Please click the button below to set a new password:', 'wp-bulk-password-reset'),
                'email_preview_button' => __('Reset My Password', 'wp-bulk-password-reset'),
                'email_preview_alt_link' => __('Or copy and paste this link into your browser:', 'wp-bulk-password-reset'),
                'email_preview_expiry' => __('This password reset link will expire in 24 hours.', 'wp-bulk-password-reset'),
                'email_preview_ignore' => __("If you didn't request this password reset, you can safely ignore this email.", 'wp-bulk-password-reset'),
                'email_preview_signature' => __('Best regards', 'wp-bulk-password-reset'),
                'summary_title' => __('Password Reset Summary', 'wp-bulk-password-reset'),
                'summary_total' => __('Total users processed: %d', 'wp-bulk-password-reset'),
                'summary_sent' => __('Successfully sent: %d', 'wp-bulk-password-reset'),
                'summary_errors' => __('Errors: %d', 'wp-bulk-password-reset')
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
