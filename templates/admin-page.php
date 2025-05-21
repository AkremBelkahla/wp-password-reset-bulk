<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <div class="config-section">
        <div class="config-header">
            <h2 class="config-title"><?php _e('Configuration de l\'envoi', 'wp-password-reset-bulk'); ?></h2>
            <span class="mode-test-badge"><?php _e('MODE TEST', 'wp-password-reset-bulk'); ?></span>
        </div>
        
        <div class="stats-section">
            <div class="stats-header mb-2">
                <strong><?php _e('Statistiques des utilisateurs :', 'wp-password-reset-bulk'); ?></strong>
            </div>
            <div class="stats-content">
                <?php 
                $total_users = 0;
                $editable_roles = array_reverse(get_editable_roles());
                unset($editable_roles['administrator']);
                
                foreach ($editable_roles as $role => $details) {
                    $count = isset($user_stats['by_role'][$role]) ? $user_stats['by_role'][$role] : 0;
                    $total_users += $count;
                }
                ?>
                <p class="text-muted"><?php printf(__('Total des utilisateurs (hors administrateurs) : %d', 'wp-password-reset-bulk'), $total_users); ?></p>
            </div>
        </div>

        <p class="mb-2">
            <?php _e('Ce script va envoyer un email de bienvenue et de réinitialisation de mot de passe à tous les utilisateurs (sauf les administrateurs).', 'wp-password-reset-bulk'); ?>
            <span class="warning-text"><?php _e('Attention', 'wp-password-reset-bulk'); ?></span>: 
            <?php _e('Cette action est irréversible. Vérifiez bien le contenu de l\'email avant de continuer.', 'wp-password-reset-bulk'); ?>
        </p>

        <div class="email-section">
            <div class="mb-2">
                <strong><?php _e('Contenu de l\'email :', 'wp-password-reset-bulk'); ?></strong>
            </div>
            <div class="email-content">
                <p class="mb-2">
                    <strong><?php _e('Objet :', 'wp-password-reset-bulk'); ?></strong> 
                    <?php _e('Bienvenue sur le nouveau site Occasion Photo !', 'wp-password-reset-bulk'); ?>
                </p>
                <button type="button" class="email-preview-btn">
                    <?php _e('Aperçu', 'wp-password-reset-bulk'); ?>
                </button>
            </div>
        </div>

        <div class="progress-stats">
            <div class="stat-box">
                <div class="stat-label"><?php _e('Total', 'wp-password-reset-bulk'); ?></div>
                <div class="stat-number total-stat">0</div>
            </div>
            <div class="stat-box">
                <div class="stat-label"><?php _e('Envoyés', 'wp-password-reset-bulk'); ?></div>
                <div class="stat-number sent-stat">0</div>
            </div>
            <div class="stat-box">
                <div class="stat-label"><?php _e('Erreurs', 'wp-password-reset-bulk'); ?></div>
                <div class="stat-number error-stat">0</div>
            </div>
        </div>

        <div class="action-buttons">
            <button type="button" class="normal-mode-btn"><?php _e('Retour au mode normal', 'wp-password-reset-bulk'); ?></button>
            <button type="button" class="send-test-btn"><?php _e('Tester l\'envoi', 'wp-password-reset-bulk'); ?></button>
        </div>
    </div>

    <div class="log-section">
        <h3 class="log-title"><?php _e('Journal d\'envoi', 'wp-password-reset-bulk'); ?></h3>
        <div class="log-empty">
            <?php _e('Aucun envoi pour le moment', 'wp-password-reset-bulk'); ?>
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
