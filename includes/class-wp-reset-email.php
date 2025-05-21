<?php
/**
 * Gestion des emails de réinitialisation
 */
class WP_Reset_Email {
    
    /**
     * Constructeur
     */
    public function __construct() {
        // Initialisation des filtres pour personnaliser les emails
        add_filter('retrieve_password_title', [$this, 'filter_retrieve_password_title'], 10, 3);
        add_filter('retrieve_password_message', [$this, 'filter_retrieve_password_message'], 10, 4);
    }
    
    /**
     * Filtre le titre de l'email de réinitialisation
     */
    public function filter_retrieve_password_title($title, $user_login, $user_data) {
        return sprintf(__('Réinitialisation de votre mot de passe - %s', 'wp-password-reset-bulk'), get_bloginfo('name'));
    }
    
    /**
     * Filtre le contenu de l'email de réinitialisation
     */
    public function filter_retrieve_password_message($message, $key, $user_login, $user_data) {
        $reset_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login');
        
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
                <p><?php _e('Bonjour', 'wp-password-reset-bulk'); ?> <?php echo esc_html($user_data->display_name); ?>,</p>
                <p><?php _e('Vous avez demandé la réinitialisation de votre mot de passe. Veuillez cliquer sur le bouton ci-dessous pour définir un nouveau mot de passe :', 'wp-password-reset-bulk'); ?></p>
                <p style="text-align: center; margin: 30px 0;">
                    <a href="<?php echo esc_url($reset_url); ?>" style="background-color: #2271b1; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block;">
                        <?php _e('Réinitialiser mon mot de passe', 'wp-password-reset-bulk'); ?>
                    </a>
                </p>
                <p><?php _e('Ou copiez-collez ce lien dans votre navigateur :', 'wp-password-reset-bulk'); ?><br>
                <code style="word-break: break-all;"><?php echo esc_url($reset_url); ?></code></p>
                <p><?php _e('Ce lien de réinitialisation expirera dans 24 heures.', 'wp-password-reset-bulk'); ?></p>
                <p><?php _e('Si vous n\'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email. Votre mot de passe restera inchangé tant que vous n\'aurez pas accédé au lien ci-dessus et créé un nouveau mot de passe.', 'wp-password-reset-bulk'); ?></p>
                <p><?php _e('Cordialement,', 'wp-password-reset-bulk'); ?><br><?php bloginfo('name'); ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Envoie un email de réinitialisation
     */
    public function send_reset_email($user) {
        if (!($user instanceof WP_User)) {
            return new WP_Error('invalid_user', __('Utilisateur invalide', 'wp-password-reset-bulk'));
        }
        
        // Générer la clé de réinitialisation
        $key = get_password_reset_key($user);
        
        if (is_wp_error($key)) {
            return $key;
        }
        
        // Construire l'URL de réinitialisation
        $reset_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login');
        
        // Sujet de l'email
        $subject = sprintf(__('Réinitialisation de votre mot de passe - %s', 'wp-password-reset-bulk'), get_bloginfo('name'));
        
        // En-têtes
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>'
        ];
        
        // Envoyer l'email
        return wp_mail(
            $user->user_email,
            $subject,
            $this->get_email_content($user, $reset_url),
            $headers
        );
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
                <p><?php _e('Ou copiez-collez ce lien dans votre navigateur :', 'wp-password-reset-bulk'); ?><br>
                <code style="word-break: break-all;"><?php echo esc_url($reset_url); ?></code></p>
                <p><?php _e('Ce lien de réinitialisation expirera dans 24 heures.', 'wp-password-reset-bulk'); ?></p>
                <p><?php _e('Si vous n\'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email. Votre mot de passe restera inchangé tant que vous n\'aurez pas accédé au lien ci-dessus et créé un nouveau mot de passe.', 'wp-password-reset-bulk'); ?></p>
                <p><?php _e('Cordialement,', 'wp-password-reset-bulk'); ?><br><?php bloginfo('name'); ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
