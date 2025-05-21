<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1><?php _e('Envoi en masse - Réinitialisation des mots de passe', 'wp-password-reset-bulk'); ?></h1>
    
    <div class="card">
        <div class="card-body">
            <h2><?php _e('Options d\'envoi', 'wp-password-reset-bulk'); ?></h2>
            
            <form id="wprb-form">
                <?php wp_nonce_field('wprb_nonce', 'wprb_nonce'); ?>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="test_mode" id="test-mode" value="1" checked>
                        <?php _e('Mode test (envoi uniquement aux adresses de test)', 'wp-password-reset-bulk'); ?>
                    </label>
                    <p class="description">
                        <?php _e('En mode test, les emails seront envoyés uniquement aux adresses de test définies dans le plugin.', 'wp-password-reset-bulk'); ?>
                    </p>
                </div>
                
                <div id="roles-selection" style="display: none;">
                    <h3><?php _e('Sélectionner les rôles', 'wp-password-reset-bulk'); ?></h3>
                    <p><?php _e('Sélectionnez les rôles des utilisateurs qui recevront l\'email de réinitialisation.', 'wp-password-reset-bulk'); ?></p>
                    
                    <div class="roles-list">
                        <?php 
                        $editable_roles = array_reverse(get_editable_roles());
                        unset($editable_roles['administrator']);
                        
                        foreach ($editable_roles as $role => $details) {
                            $count = isset($user_stats['by_role'][$role]) ? $user_stats['by_role'][$role] : 0;
                            if ($count > 0) {
                                echo sprintf(
                                    '<label style="display: block; margin: 5px 0;"><input type="checkbox" name="roles[]" value="%s" checked> %s (%d %s)</label>',
                                    esc_attr($role),
                                    translate_user_role($details['name']),
                                    $count,
                                    _n('utilisateur', 'utilisateurs', $count, 'wp-password-reset-bulk')
                                );
                            }
                        }
                        ?>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <button type="button" id="preview-email" class="button">
                        <?php _e('Aperçu de l\'email', 'wp-password-reset-bulk'); ?>
                    </button>
                    <button type="button" id="send-emails" class="button button-primary">
                        <?php _e('Envoyer les emails de réinitialisation', 'wp-password-reset-bulk'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card" id="progress-container" style="display: none;">
        <div class="card-body">
            <h2><?php _e('Progression de l\'envoi', 'wp-password-reset-bulk'); ?></h2>
            
            <div class="progress">
                <div id="progress-bar" class="progress-bar" style="width: 0%;">0%</div>
            </div>
            
            <div id="progress-stats" style="margin: 20px 0;">
                <p>
                    <?php _e('Envoi en cours...', 'wp-password-reset-bulk'); ?>
                    <span id="progress-text">0/0</span>
                </p>
                <p id="progress-details">
                    <span id="sent-count">0</span> <?php _e('envoyés', 'wp-password-reset-bulk'); ?> | 
                    <span id="error-count">0</span> <?php _e('erreurs', 'wp-password-reset-bulk'); ?>
                </p>
            </div>
            
            <div id="results-container" style="display: none;">
                <h3><?php _e('Résumé', 'wp-password-reset-bulk'); ?></h3>
                <div id="results-summary"></div>
                
                <h3><?php _e('Détails', 'wp-password-reset-bulk'); ?></h3>
                <div id="results-details">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Utilisateur', 'wp-password-reset-bulk'); ?></th>
                                <th><?php _e('Email', 'wp-password-reset-bulk'); ?></th>
                                <th><?php _e('Statut', 'wp-password-reset-bulk'); ?></th>
                                <th><?php _e('Message', 'wp-password-reset-bulk'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="results-table-body">
                            <!-- Les résultats seront ajoutés ici dynamiquement -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation -->
<div class="modal fade" id="confirmSendModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php _e('Confirmer l\'envoi', 'wp-password-reset-bulk'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fermer">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="confirm-message"></p>
                <div id="test-mode-warning" class="notice notice-warning" style="display: none; margin: 10px 0; padding: 10px;">
                    <p><?php _e('<strong>Mode test activé :</strong> Les emails seront envoyés uniquement aux adresses de test.', 'wp-password-reset-bulk'); ?></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button" data-dismiss="modal"><?php _e('Annuler', 'wp-password-reset-bulk'); ?></button>
                <button type="button" class="button button-primary" id="confirm-send"><?php _e('Confirmer l\'envoi', 'wp-password-reset-bulk'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Aperçu de l'email -->
<div class="modal fade" id="emailPreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php _e('Aperçu de l\'email', 'wp-password-reset-bulk'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fermer">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <iframe id="email-preview-frame" style="width: 100%; height: 500px; border: 1px solid #ddd;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="button" data-dismiss="modal"><?php _e('Fermer', 'wp-password-reset-bulk'); ?></button>
            </div>
        </div>
    </div>
</div>
