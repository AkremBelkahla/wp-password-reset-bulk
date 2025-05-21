<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap" id="wprb-admin">
    <h1 class="wprb-text-2xl wprb-font-bold wprb-mb-6"><?php _e('Bulk Password Reset', 'wp-password-reset-bulk'); ?></h1>
    
    <div class="wprb-card">
        <div class="wprb-form-group">
            <h2 class="wprb-text-xl wprb-font-semibold wprb-mb-4"><?php _e('Send Options', 'wp-password-reset-bulk'); ?></h2>
            
            <form id="wprb-form">
                <?php wp_nonce_field('wprb_nonce', 'wprb_nonce'); ?>
                
                <div class="wprb-form-group">
                    <label class="wprb-flex wprb-items-center wprb-space-x-2">
                        <input type="checkbox" name="test_mode" id="test-mode" value="1" checked class="wprb-checkbox">
                        <span class="wprb-text-sm wprb-font-medium">
                            <?php _e('Test Mode (send only to test email addresses)', 'wp-password-reset-bulk'); ?>
                        </span>
                    </label>
                    <p class="wprb-text-sm wprb-text-gray-500 wprb-mt-1">
                        <?php _e('In test mode, emails will only be sent to the test addresses defined in the plugin.', 'wp-password-reset-bulk'); ?>
                    </p>
                </div>
                
                <div id="roles-selection" class="wprb-hidden wprb-mt-6">
                    <h3 class="wprb-text-lg wprb-font-semibold wprb-mb-2"><?php _e('Select User Roles', 'wp-password-reset-bulk'); ?></h3>
                    <p class="wprb-text-sm wprb-text-gray-500 wprb-mb-4"><?php _e('Choose which user roles will receive the password reset email.', 'wp-password-reset-bulk'); ?></p>
                    
                    <div class="wprb-space-y-3">
                        <?php 
                        $editable_roles = array_reverse(get_editable_roles());
                        unset($editable_roles['administrator']);
                        
                        foreach ($editable_roles as $role => $details) {
                            $count = isset($user_stats['by_role'][$role]) ? $user_stats['by_role'][$role] : 0;
                            if ($count > 0) {
                                echo sprintf(
                                    '<label class="wprb-flex wprb-items-center wprb-space-x-3">' .
                                    '<input type="checkbox" name="roles[]" value="%s" checked class="wprb-checkbox">' .
                                    '<span class="wprb-text-sm wprb-font-medium">%s</span>' .
                                    '<span class="wprb-text-sm wprb-text-gray-500">(%d %s)</span>' .
                                    '</label>',
                                    esc_attr($role),
                                    translate_user_role($details['name']),
                                    $count,
                                    _n('user', 'users', $count, 'wp-password-reset-bulk')
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
                    <div class="wprb-spinner wprb-hidden"></div>
                </div>

                <div id="progress-section" class="wprb-hidden wprb-mt-6">
                    <div class="wprb-relative wprb-h-4 wprb-bg-gray-200 wprb-rounded-full wprb-overflow-hidden">
                        <div class="wprb-progress wprb-absolute wprb-inset-y-0 wprb-left-0 wprb-bg-blue-600 wprb-transition-all"></div>
                    </div>
                    <p class="wprb-text-sm wprb-text-gray-600 wprb-mt-2 wprb-flex wprb-items-center wprb-space-x-2">
                        <span><?php _e('Sending emails...', 'wp-password-reset-bulk'); ?></span>
                        <span id="progress-text" class="wprb-font-medium">0%</span>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal d'aperçu de l'email -->
    <div id="emailPreview" class="wprb-modal wprb-hidden">
        <div class="wprb-modal-content wprb-bg-white wprb-rounded-lg wprb-shadow-xl wprb-max-w-2xl wprb-mx-auto wprb-my-8 wprb-p-6">
            <div class="wprb-flex wprb-justify-between wprb-items-center wprb-mb-4">
                <h3 class="wprb-text-xl wprb-font-semibold"><?php _e('Aperçu de l\'email', 'wp-password-reset-bulk'); ?></h3>
                <button type="button" class="wprb-close wprb-text-gray-500 hover:wprb-text-gray-700" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="wprb-modal-body">
                <iframe id="email-preview-frame" class="wprb-w-full wprb-h-[500px] wprb-border-0"></iframe>
            </div>
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
