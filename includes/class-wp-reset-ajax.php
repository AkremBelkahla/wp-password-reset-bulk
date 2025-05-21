<?php
/**
 * Gestion des appels AJAX
 */
class WP_Reset_Ajax {
    
    /**
     * Constructeur
     */
    public function __construct() {
        // Les actions AJAX sont enregistrées dans la classe principale
    }
    
    /**
     * Envoie les emails de réinitialisation
     */
    public function send_reset_emails() {
        // Vérifier le nonce
        check_ajax_referer('wprb_nonce', 'security');
        
        // Vérifier les capacités
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Accès non autorisé', 'wp-password-reset-bulk')], 403);
        }
        
        // Récupérer les paramètres
        $test_mode = isset($_POST['test_mode']) && '1' === $_POST['test_mode'];
        $roles = isset($_POST['roles']) ? (array) $_POST['roles'] : [];
        
        try {
            // Récupérer les utilisateurs cibles
            $users = $this->get_target_users($test_mode, $roles);
            
            if (empty($users)) {
                throw new Exception(__('Aucun utilisateur trouvé avec les critères sélectionnés', 'wp-password-reset-bulk'));
            }
            
            // Initialiser les résultats
            $results = [
                'total' => count($users),
                'sent' => 0,
                'errors' => 0,
                'details' => []
            ];
            
            // Envoyer les emails
            foreach ($users as $user) {
                $result = [
                    'user_id' => $user->ID,
                    'email' => $user->user_email,
                    'status' => 'pending',
                    'message' => ''
                ];
                
                try {
                    // Générer la clé de réinitialisation
                    $reset_key = get_password_reset_key($user);
                    
                    if (is_wp_error($reset_key)) {
                        throw new Exception(__('Erreur lors de la génération de la clé de réinitialisation', 'wp-password-reset-bulk'));
                    }
                    
                    // Construire l'URL de réinitialisation
                    $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');
                    
                    // Envoyer l'email
                    $email_sent = wp_mail(
                        $user->user_email,
                        __('Réinitialisation de votre mot de passe', 'wp-password-reset-bulk'),
                        $this->get_email_content($user, $reset_url),
                        ['Content-Type: text/html; charset=UTF-8']
                    );
                    
                    if ($email_sent) {
                        $result['status'] = 'success';
                        $result['message'] = __('Email envoyé avec succès', 'wp-password-reset-bulk');
                        $results['sent']++;
                    } else {
                        throw new Exception(__('Échec de l\'envoi de l\'email', 'wp-password-reset-bulk'));
                    }
                    
                } catch (Exception $e) {
                    $result['status'] = 'error';
                    $result['message'] = $e->getMessage();
                    $results['errors']++;
                }
                
                $results['details'][] = $result;
                
                // Petite pause pour éviter de surcharger le serveur
                usleep(100000); // 100ms
            }
            
            wp_send_json_success($results);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Récupère les utilisateurs cibles
     */
    private function get_target_users($test_mode = false, $roles = []) {
        $args = [
            'role__not_in' => ['administrator'],
            'fields' => 'all_with_meta',
            'number' => -1
        ];
        
        // Filtrer par rôles si spécifiés
        if (!empty($roles)) {
            $args['role__in'] = $roles;
        }
        
        // Mode test : utiliser des adresses email de test
        if ($test_mode) {
            $test_emails = [
                'infinitywebtn@gmail.com',
                'akrem@email.com',
                'webrocket@mail.com'
            ];
            
            $users = [];
            foreach ($test_emails as $email) {
                $user = get_user_by('email', $email);
                if (!$user) {
                    $user_id = wp_insert_user([
                        'user_login' => $email,
                        'user_email' => $email,
                        'display_name' => 'Utilisateur de test',
                        'user_pass' => wp_generate_password(),
                        'role' => 'customer'
                    ]);
                    
                    if (!is_wp_error($user_id)) {
                        $user = get_user_by('id', $user_id);
                    }
                }
                
                if ($user) {
                    $users[] = $user;
                }
            }
            
            return $users;
        }
        
        // Mode normal : récupérer les utilisateurs selon les critères
        return get_users($args);
    }
    
    /**
     * Génère le contenu de l'email
     */
    private function get_email_content($user, $reset_url) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <title><?php _e('Réinitialisation de votre mot de passe', 'wp-password-reset-bulk'); ?></title>
        </head>
        <body>
            <div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">
                <h2><?php _e('Réinitialisation de votre mot de passe', 'wp-password-reset-bulk'); ?></h2>
                <p><?php _e('Bonjour', 'wp-password-reset-bulk'); ?> <?php echo esc_html($user->display_name); ?>,</p>
                <p><?php _e('Vous avez demandé la réinitialisation de votre mot de passe. Veuillez cliquer sur le bouton ci-dessous pour définir un nouveau mot de passe :', 'wp-password-reset-bulk'); ?></p>
                <p style="text-align: center; margin: 30px 0;">
                    <a href="<?php echo esc_url($reset_url); ?>" style="background-color: #2271b1; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block;">
                        <?php _e('Réinitialiser mon mot de passe', 'wp-password-reset-bulk'); ?>
                    </a>
                </p>
                <p><?php _e('Si vous n\'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email.', 'wp-password-reset-bulk'); ?></p>
                <p><?php _e('Cordialement,', 'wp-password-reset-bulk'); ?><br><?php bloginfo('name'); ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
