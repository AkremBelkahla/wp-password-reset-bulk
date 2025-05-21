jQuery(document).ready(function($) {
    'use strict';

    // État de l'application
    const app = {
        isRunning: false,
        stopRequested: false,
        currentIndex: 0,
        totalUsers: 0,
        results: {
            sent: 0,
            errors: 0,
            details: []
        }
    };

    // Sélecteurs
    const selectors = {
        form: '#wprb-form',
        testMode: '#test-mode',
        rolesSelection: '#roles-selection',
        sendButton: '#send-emails',
        previewButton: '#preview-email',
        progressContainer: '#progress-container',
        progressBar: '#progress-bar',
        progressText: '#progress-text',
        sentCount: '#sent-count',
        errorCount: '#error-count',
        resultsContainer: '#results-container',
        resultsSummary: '#results-summary',
        resultsTableBody: '#results-table-body',
        confirmModal: '#confirmSendModal',
        confirmMessage: '#confirm-message',
        testModeWarning: '#test-mode-warning',
        confirmSend: '#confirm-send',
        emailPreview: '#emailPreview',
        emailPreviewFrame: '#email-preview-frame'
    };

    // Initialisation
    function init() {
        // Basculer la visibilité de la sélection des rôles
        toggleRolesSelection();
        
        // Événements
        $(selectors.testMode).on('change', toggleRolesSelection);
        $(selectors.previewButton).on('click', showEmailPreview);
        $(selectors.sendButton).on('click', prepareSendEmails);
        $(selectors.confirmSend).on('click', startSendingEmails);
        
        // Fermer les modales
        $('.modal .close, .modal [data-dismiss="modal"]').on('click', function() {
            $(this).closest('.modal').hide();
        });
        
        // Fermer la modale en cliquant à l'extérieur
        $(window).on('click', function(event) {
            if ($(event.target).is(selectors.confirmModal)) {
                $(selectors.confirmModal).hide();
            }
            if ($(event.target).is(selectors.emailPreview)) {
                $(selectors.emailPreview).hide();
            }
        });
    }
    
    // Basculer la visibilité de la sélection des rôles
    function toggleRolesSelection() {
        const isTestMode = $(selectors.testMode).is(':checked');
        if (isTestMode) {
            $(selectors.rolesSelection).hide();
        } else {
            $(selectors.rolesSelection).show();
        }
    }
    
    // Afficher l'aperçu de l'email
    function showEmailPreview() {
        // Générer un aperçu factice
        const previewHtml = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <title>${wprb_vars.i18n.email_preview_title}</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                    .button { 
                        display: inline-block; 
                        padding: 10px 20px; 
                        background-color: #2271b1; 
                        color: #fff; 
                        text-decoration: none; 
                        border-radius: 3px; 
                        margin: 20px 0;
                    }
                    .footer { margin-top: 30px; font-size: 0.9em; color: #666; }
                </style>
            </head>
            <body>
                <h2>${wprb_vars.i18n.email_preview_heading}</h2>
                <p>${wprb_vars.i18n.email_preview_greeting.replace('%s', 'Utilisateur Test')}</p>
                <p>${wprb_vars.i18n.email_preview_instructions}</p>
                <p style="text-align: center;">
                    <a href="#" class="button">${wprb_vars.i18n.email_preview_button}</a>
                </p>
                <p>${wprb_vars.i18n.email_preview_alt_link} <a href="#">https://votresite.com/wp-login.php?action=rp&key=ABC123&login=test</a></p>
                <p><em>${wprb_vars.i18n.email_preview_expiry}</em></p>
                <p>${wprb_vars.i18n.email_preview_ignore}</p>
                <div class="footer">
                    <p>${wprb_vars.i18n.email_preview_signature},<br>${wprb_vars.site_name}</p>
                </div>
            </body>
            </html>
        `;
        
        // Mettre à jour l'iframe avec l'aperçu
        const iframe = $(selectors.emailPreviewFrame)[0];
        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
        iframeDoc.open();
        iframeDoc.write(previewHtml);
        iframeDoc.close();
        
        // Afficher la modale
        $(selectors.emailPreview).show();
    }
    
    // Préparer l'envoi des emails
    function prepareSendEmails() {
        if (app.isRunning) {
            stopSendingEmails();
            return;
        }
        
        const isTestMode = $(selectors.testMode).is(':checked');
        const selectedRoles = [];
        
        if (!isTestMode) {
            $('input[name="roles[]"]:checked').each(function() {
                selectedRoles.push($(this).val());
            });
            
            if (selectedRoles.length === 0) {
                alert(wprb_vars.i18n.select_at_least_one_role);
                return;
            }
        }
        
        // Afficher la modale de confirmation
        const message = isTestMode 
            ? wprb_vars.i18n.confirm_test_mode
            : wprb_vars.i18n.confirm_send.replace('%d', selectedRoles.length);
        
        $(selectors.confirmMessage).text(message);
        $(selectors.testModeWarning).toggle(isTestMode);
        $(selectors.confirmModal).show();
    }
    
    // Démarrer l'envoi des emails
    function startSendingEmails() {
        // Fermer la modale
        $(selectors.confirmModal).hide();
        
        // Réinitialiser l'état
        resetUI();
        app.isRunning = true;
        app.stopRequested = false;
        app.currentIndex = 0;
        app.results = {
            sent: 0,
            errors: 0,
            details: []
        };
        
        // Récupérer les paramètres
        const isTestMode = $(selectors.testMode).is(':checked');
        const selectedRoles = [];
        
        if (!isTestMode) {
            $('input[name="roles[]"]:checked').each(function() {
                selectedRoles.push($(this).val());
            });
        }
        
        // Afficher le conteneur de progression
        $(selectors.progressContainer).show();
        
        // Désactiver le formulaire
        $(selectors.form).find('input, button').prop('disabled', true);
        
        // Mettre à jour l'interface
        $(selectors.sendButton).text(wprb_vars.i18n.sending);
        
        // Démarrer l'envoi
        sendEmailsInBatches(isTestMode, selectedRoles);
    }
    
    // Envoyer les emails par lots
    function sendEmailsInBatches(isTestMode, selectedRoles) {
        if (app.stopRequested) {
            finishSending();
            return;
        }
        
        const batchSize = 5; // Nombre d'emails à envoyer par lot
        let processedInBatch = 0;
        
        // Préparer les données pour la requête AJAX
        const data = {
            action: 'send_reset_emails',
            security: wprb_vars.nonce,
            test_mode: isTestMode ? 1 : 0,
            roles: selectedRoles,
            offset: app.currentIndex,
            batch_size: batchSize
        };
        
        // Envoyer la requête AJAX
        $.ajax({
            url: wprb_vars.ajax_url,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Mettre à jour les résultats
                    app.results.sent += response.data.sent || 0;
                    app.results.errors += response.data.errors || 0;
                    app.results.details = app.results.details.concat(response.data.details || []);
                    
                    // Mettre à jour l'interface
                    updateProgressUI(response.data);
                    
                    // Vérifier si on a terminé
                    if (response.data.completed) {
                        finishSending();
                    } else {
                        // Passer au lot suivant
                        app.currentIndex += batchSize;
                        setTimeout(() => sendEmailsInBatches(isTestMode, selectedRoles), 500);
                    }
                } else {
                    // Afficher l'erreur
                    showError(response.data && response.data.message ? response.data.message : wprb_vars.i18n.unknown_error);
                    finishSending();
                }
            },
            error: function(xhr, status, error) {
                showError(wprb_vars.i18n.ajax_error + ' ' + error);
                finishSending();
            }
        });
    }
    
    // Arrêter l'envoi des emails
    function stopSendingEmails() {
        app.stopRequested = true;
        $(selectors.sendButton).html('<span class="spinner"></span> ' + wprb_vars.i18n.stopping);
    }
    
    // Terminer l'envoi des emails
    function finishSending() {
        app.isRunning = false;
        
        // Réactiver le formulaire
        $(selectors.form).find('input, button').prop('disabled', false);
        
        // Mettre à jour le bouton
        $(selectors.sendButton).text(wprb_vars.i18n.send_emails_again);
        
        // Afficher les résultats
        showResults();
    }
    
    // Mettre à jour l'interface de progression
    function updateProgressUI(data) {
        const total = data.total || 1;
        const processed = (data.sent || 0) + (data.errors || 0);
        const progress = Math.round((processed / total) * 100);
        
        // Mettre à jour la barre de progression
        $(selectors.progressBar)
            .css('width', progress + '%')
            .text(progress + '%');
        
        // Mettre à jour les compteurs
        $(selectors.progressText).text(`${processed}/${total}`);
        $(selectors.sentCount).text(data.sent || 0);
        $(selectors.errorCount).text(data.errors || 0);
        
        // Mettre à jour les détails
        if (data.details && data.details.length > 0) {
            updateResultsTable(data.details);
        }
    }
    
    // Mettre à jour le tableau des résultats
    function updateResultsTable(details) {
        const tbody = $(selectors.resultsTableBody);
        
        details.forEach(detail => {
            if (!detail) return;
            
            const statusClass = `status-${detail.status}`;
            const userLink = `<a href="${wprb_vars.admin_url}user-edit.php?user_id=${detail.user_id}" target="_blank">${detail.user_login || 'N/A'}</a>`;
            
            const row = `
                <tr class="${statusClass}">
                    <td>${userLink}</td>
                    <td>${detail.email || 'N/A'}</td>
                    <td><span class="badge badge-${detail.status === 'success' ? 'success' : 'danger'}">${detail.status || 'N/A'}</span></td>
                    <td>${detail.message || ''}</td>
                </tr>
            `;
            
            tbody.prepend(row);
        });
    }
    
    // Afficher les résultats finaux
    function showResults() {
        const results = app.results;
        const total = results.sent + results.errors;
        
        // Mettre à jour le résumé
        let summary = `
            <div class="alert ${results.errors > 0 ? 'alert-warning' : 'alert-success'}">
                <h3>${wprb_vars.i18n.summary_title}</h3>
                <p>${wprb_vars.i18n.summary_total.replace('%d', total)}</p>
                <p>${wprb_vars.i18n.summary_sent.replace('%d', results.sent)}</p>
                ${results.errors > 0 ? `<p>${wprb_vars.i18n.summary_errors.replace('%d', results.errors)}</p>` : ''}
            </div>
        `;
        
        $(selectors.resultsSummary).html(summary);
        
        // Afficher le conteneur des résultats
        $(selectors.resultsContainer).show();
        
        // Faire défiler jusqu'aux résultats
        $('html, body').animate({
            scrollTop: $(selectors.resultsContainer).offset().top - 100
        }, 500);
    }
    
    // Afficher une erreur
    function showError(message) {
        const errorHtml = `
            <div class="alert alert-danger">
                <strong>${wprb_vars.i18n.error}:</strong> ${message}
            </div>
        `;
        
        $(selectors.progressContainer).prepend(errorHtml);
    }
    
    // Réinitialiser l'interface utilisateur
    function resetUI() {
        $(selectors.progressBar).css('width', '0%').text('0%');
        $(selectors.progressText).text('0/0');
        $(selectors.sentCount).text('0');
        $(selectors.errorCount).text('0');
        $(selectors.resultsTableBody).empty();
        $(selectors.resultsContainer).hide();
        $(selectors.sendButton).text(wprb_vars.i18n.send_emails);
    }
    
    // Démarrer l'application
    init();
});
