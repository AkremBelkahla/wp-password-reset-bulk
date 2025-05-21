<style>
        /* Styles de base */
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            font-size: 15px;
            background-color: #f0f0f1;
            color: #1d2327;
            padding: 20px;
            line-height: 1.5;
            margin: 0;
        }
        .wrap {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,.1);
        }
        h1 {
            color: #1d2327;
            margin-top: 0;
            padding-bottom: 15px;
            border-bottom: 1px solid #c3c4c7;
        }
        .card {
            background: #fff;
            border: 1px solid #c3c4c7;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin-bottom: 20px;
        }
        .card-body {
            padding: 20px;
        }
        .btn {
            display: inline-block;
            text-decoration: none;
            font-size: 13px;
            line-height: 2.15384615;
            min-height: 30px;
            margin: 0;
            padding: 0 10px;
            cursor: pointer;
            border-width: 1px;
            border-style: solid;
            -webkit-appearance: none;
            border-radius: 3px;
            white-space: nowrap;
            box-sizing: border-box;
        }
        .btn-primary {
            background: #2271b1;
            border-color: #2271b1;
            color: #fff;
            text-decoration: none;
            text-shadow: none;
        }
        .btn-lg {
            height: 40px;
            line-height: 2.15384615;
            padding: 0 20px;
        }
        .progress {
            height: 25px; 
            margin: 20px 0; 
            background: #f0f0f1;
            border-radius: 3px;
            overflow: hidden;
        }
        .progress-bar { 
            transition: width 0.3s; 
            background: #2271b1;
            height: 100%;
            color: white;
            display: flex;
            align-items: center;
            padding-left: 10px;
        }
    </style>
<?php
/**
 * Plugin Name: Envoi en masse - Réinitialisation des mots de passe
 * Description: Envoi d'emails de réinitialisation de mot de passe en masse
 * Version: 1.0
 * Author: Votre Nom
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit; // Sortie si accès direct
}

// Vérifier les capacités utilisateur
if (!current_user_can('manage_options')) {
    wp_die(__('Vous n\'avez pas les droits nécessaires pour accéder à cette page.'));
}

// Définir le mode test avant tout
$test_mode = isset($_GET['test']) && $_GET['test'] === '1';
if (!defined('ABSPATH')) {
    // Essayer de trouver wp-load.php en remontant les répertoires
    $wp_config_path = null;
    $current_dir = dirname(__FILE__);
    
    // Remonter jusqu'à 5 niveaux pour trouver wp-load.php
    for ($i = 0; $i < 5; $i++) {
        if (file_exists($current_dir . '/wp-load.php')) {
            $wp_load = $current_dir . '/wp-load.php';
            break;
        }
        $current_dir = dirname($current_dir);
    }
    
    if (isset($wp_load) && file_exists($wp_load)) {
        require_once($wp_load);
    } else {
        // Essayer avec le chemin absolu si on n'a pas trouvé
        $wp_load = '/home/occasionp/www/wp-load.php'; // Ajustez ce chemin selon votre hébergement
        if (file_exists($wp_load)) {
            require_once($wp_load);
        } else {
            die('Impossible de charger WordPress. Veuillez vérifier le chemin vers wp-load.php');
        }
    }
}

// Vérifier les droits d'administration
if (!current_user_can('manage_options')) {
    wp_die('Accès non autorisé');
}

// Traitement AJAX
if (isset($_POST['action']) && $_POST['action'] === 'send_reset_emails') {
    check_ajax_referer('bulk_password_reset_nonce', 'security');
    
    // Initialiser les variables
    $users = [];
    $total_users = 0;
    $roles_count = [];
    
    // Récupérer le mode test depuis la requête AJAX si défini
    if (isset($_POST['test_mode'])) {
        $test_mode = $_POST['test_mode'] === '1';
    }
    
    if ($test_mode) {
        // Mode test : utiliser des adresses email de test
        $test_emails = [
            'infinitywebtn@gmail.com',
            'akrem@email.com',
            'webrocket@mail.com'
        ];
        
        $users = [];
        $created_count = 0;
        $existing_count = 0;
        
        foreach ($test_emails as $email) {
            $user = get_user_by('email', $email);
            if ($user) {
                $users[] = $user->ID;
                $existing_count++;
            } else {
                // Créer un utilisateur factice pour le test si nécessaire
                $user_data = [
                    'user_login' => sanitize_email($email),
                    'user_email' => $email,
                    'display_name' => 'Utilisateur de test',
                    'user_pass' => wp_generate_password(),
                    'role' => 'customer'
                ];
                $user_id = wp_insert_user($user_data);
                if (!is_wp_error($user_id)) {
                    $users[] = $user_id;
                    $created_count++;
                }
            }
        }
        
        // Ajouter des informations de débogage
        error_log('Mode test : ' . count($users) . ' utilisateurs trouvés/créés');
        error_log('- Comptes existants : ' . $existing_count);
        error_log('- Nouveaux comptes : ' . $created_count);
        
        $total_users = count($users);
        $roles_count = $total_users > 0 ? ['Test: ' . $total_users] : ['Aucun utilisateur trouvé pour le test'];
        
    } else {
        // Mode normal : récupérer tous les utilisateurs sauf administrateurs
        $users = get_users([
            'role__not_in' => ['administrator'],
            'fields' => 'ID',
        ]);
        
        // Compter les utilisateurs par rôle
        $user_counts = count_users();
        $total_users = 0;
        $roles_count = [];
        
        foreach ($user_counts['avail_roles'] as $role => $count) {
            if ($role !== 'administrator') {
                $roles_count[] = ucfirst($role) . ": $count";
                $total_users += $count;
            }
        }
    }
    
    $results = [
        'total' => count($users),
        'sent' => 0,
        'errors' => 0,
        'emails' => []
    ];
    
    // Si aucun utilisateur trouvé, retourner une erreur
    if ($results['total'] === 0) {
        wp_send_json_error(['message' => 'Aucun utilisateur trouvé pour l\'envoi.']);
        exit;
    }
    
    foreach ($users as $user_id) {
        $user = get_user_by('id', $user_id);
        $result = [
            'user_id' => $user->ID,
            'email' => $user->user_email,
            'status' => 'pending',
            'message' => ''
        ];
        
        try {
            $reset_key = get_password_reset_key($user);
            if (is_wp_error($reset_key)) {
                throw new Exception('Erreur lors de la génération de la clé de réinitialisation');
            }
            
            $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');
            
            $subject = 'Bienvenue sur le nouveau site Occasion Photo !';
            
            // En-tête HTML avec le logo
            $message = '<!DOCTYPE html>
            <html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { text-align: center; margin-bottom: 20px; }
                    .logo { max-width: 200px; height: auto; }
                    .content { background-color: #f9f9f9; padding: 20px; border-radius: 5px; }
                    .button {
                        display: inline-block; 
                        padding: 10px 20px; 
                        background-color: #4CAF50; 
                        color: white !important; 
                        text-decoration: none; 
                        border-radius: 5px;
                        font-weight: bold;
                        margin: 15px 0;
                    }
                    .footer { 
                        margin-top: 30px; 
                        padding-top: 20px; 
                        border-top: 1px solid #ddd; 
                        font-size: 12px; 
                        color: #777; 
                        text-align: center;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <img src="https://www.occasionphoto.fr/wp-content/uploads/2025/04/logo-occasion-photo-2025.jpg" alt="Occasion Photo" class="logo">
                </div>
                <div class="content">
                    <p>Bonjour ' . esc_html($user->display_name) . ',</p>
                    
                    <p>Bienvenue sur le nouveau site !</p>
                    
                    <p>Nous avons le plaisir de vous accueillir sur la toute nouvelle version de notre site d\'annonces dédié au matériel photo.</p>
                    
                    <p>Afin de vous offrir une expérience plus fluide, plus rapide et plus agréable, nous avons entièrement repensé le site :</p>
                    
                    <ul>
                        <li>Une navigation simplifiée, pour trouver ou publier une annonce plus facilement</li>
                        <li>Une interface modernisée, adaptée à tous les appareils (ordinateur, tablette, mobile)</li>
                        <li>Des outils de gestion améliorés, pour suivre vos annonces en toute simplicité</li>
                    </ul>
                    
                    <p><strong>🔒 Important :</strong> pour des raisons de sécurité, les mots de passe n\'ont pas pu être transférés vers cette nouvelle version.</p>
                    
                    <p>Si vous aviez déjà un compte, il vous suffit de réinitialiser votre mot de passe pour accéder à votre espace personnel et gérer vos annonces.</p>
                    
                    <div style="text-align: center; margin: 25px 0;">
                        <a href="' . esc_url($reset_url) . '" class="button">Réinitialiser mon mot de passe</a>
                    </div>
                    
                    <p>Ou copiez ce lien dans votre navigateur :<br>
                    <a href="' . esc_url($reset_url) . '" style="word-break: break-all;">' . esc_html($reset_url) . '</a></p>
                    
                    <p>En quelques secondes, vous pourrez retrouver toutes vos annonces et profiter des nouvelles fonctionnalités du site.</p>
                    
                    <p>Cordialement,<br>L\'équipe Occasion Photo</p>
                </div>
                
                <div class="footer">
                    <p>© ' . date('Y') . ' Occasion Photo - Tous droits réservés<br>
                    <a href="https://www.occasionphoto.fr" style="color: #777;">www.occasionphoto.fr</a></p>
                </div>
            </body>
            </html>';
            
            // En-têtes pour l'email HTML
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: Occasion Photo <contact@occasionphoto.fr>',
                'Reply-To: contact@occasionphoto.fr'
            );
            $email_sent = wp_mail($user->user_email, $subject, $message, $headers);
            
            if ($email_sent) {
                $result['status'] = 'success';
                $result['message'] = 'Email envoyé avec succès';
                $results['sent']++;
            } else {
                throw new Exception('Échec de l\'envoi de l\'email');
            }
            
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['message'] = $e->getMessage();
            $results['errors']++;
        }
        
        $results['emails'][] = $result;
        
        // Envoyer les résultats partiels pour le suivi en temps réel
        if (ob_get_level() > 0) ob_flush();
        flush();
        usleep(100000); // Petit délai pour éviter de surcharger le serveur
    }
    
    wp_send_json_success($results);
    exit;
}

// Afficher l'interface
?>
<?php 
// En-tête WordPress
add_action('admin_enqueue_scripts', function() {
    wp_enqueue_style('wp-admin');
    wp_enqueue_script('jquery');
});

get_admin_page_title();
?>
<div class="wrap">
    <h1>Envoi en masse - Réinitialisation des mots de passe</h1>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .progress { height: 25px; margin: 20px 0; }
        .progress-bar { transition: width 0.3s; }
        .stats { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .stats .stat { font-size: 1.2em; font-weight: bold; }
        .email-list { max-height: 400px; overflow-y: auto; margin-top: 20px; }
        .email-item { padding: 10px; border-bottom: 1px solid #eee; }
        .email-item.success { background-color: #d4edda; }
        .email-item.error { background-color: #f8d7da; }
        .email-item.pending { background-color: #fff3cd; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Envoi en masse - Réinitialisation des mots de passe</h1>
        
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">
                    Configuration de l'envoi
                    <?php if ($test_mode): ?>
                        <span class="badge bg-warning text-dark ms-2">MODE TEST</span>
                    <?php endif; ?>
                </h5>
                <div class="alert alert-info">
                    <h6>Statistiques des utilisateurs :</h6>
                    <div>Total des utilisateurs (hors administrateurs) : <strong><?php echo $total_users; ?></strong></div>
                    <?php if (!empty($roles_count)): ?>
                        <div class="small text-muted"><?php echo is_array($roles_count) ? implode(' • ', $roles_count) : $roles_count; ?></div>
                    <?php endif; ?>
                </div>
                <p class="text-muted">
                    Ce script va envoyer un email de bienvenue et de réinitialisation de mot de passe à tous les utilisateurs (sauf les administrateurs).
                    <strong class="text-danger">Attention :</strong> Cette action est irréversible. Vérifiez bien le contenu de l'email avant de continuer.
                </p>
                <div class="alert alert-warning">
                    <h6>Contenu de l'email :</h6>
                    <div class="small">
                        <strong>Objet :</strong> Bienvenue sur le nouveau site Occasion Photo !
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" data-bs-toggle="modal" data-bs-target="#emailPreview">
                            Aperçu
                        </button>
                    </div>
                </div>
                
                <div class="stats">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div>Total</div>
                            <div class="stat" id="total-count">0</div>
                        </div>
                        <div class="col-md-4">
                            <div>Envoyés</div>
                            <div class="stat text-success" id="sent-count">0</div>
                        </div>
                        <div class="col-md-4">
                            <div>Erreurs</div>
                            <div class="stat text-danger" id="error-count">0</div>
                        </div>
                    </div>
                </div>
                
                <div class="progress">
                    <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%">0%</div>
                </div>
                
                <div class="d-grid gap-2">
                    <div class="test-buttons">
                        <?php if (!$test_mode): ?>
                            <a href="?test=1" class="btn btn-outline-warning me-2">
                                <i class="bi bi-flask me-1"></i>Tester avec 3 adresses
                            </a>
                        <?php else: ?>
                            <a href="?" class="btn btn-outline-secondary me-2">
                                <i class="bi bi-arrow-left me-1"></i>Retour au mode normal
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#confirmSendModal" id="prepare-send-btn">
                        <i class="bi bi-envelope-check me-2"></i><?php echo $test_mode ? 'Tester l\'envoi' : 'Préparer l\'envoi'; ?>
                    </button>
                    
                    <button id="start-btn" class="btn btn-success btn-lg d-none">
                        <span class="spinner-border spinner-border-sm d-none" id="spinner" role="status" aria-hidden="true"></span>
                        <i class="bi bi-send-check me-2"></i>Confirmer l'envoi (<span id="user-count"><?php echo $total_users; ?></span> utilisateurs)
                    </button>
                    <button id="stop-btn" class="btn btn-danger d-none">Arrêter</button>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title">Journal d'envoi</h5>
                <div id="email-list" class="email-list">
                    <div class="text-muted text-center py-3">Aucun envoi pour le moment</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de confirmation -->
    <div class="modal fade" id="confirmSendModal" tabindex="-1" aria-labelledby="confirmSendModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="confirmSendModalLabel">Confirmer l'envoi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p>Vous êtes sur le point d'envoyer un email à <strong><?php echo $total_users; ?> utilisateurs</strong>.</p>
                    <p class="mb-0">Êtes-vous sûr de vouloir continuer ?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="confirm-send-btn">
                        <i class="bi bi-send-check me-1"></i>Confirmer l'envoi
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal d'aperçu de l'email -->
    <div class="modal fade" id="emailPreview" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Aperçu de l'email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="card">
                        <div class="card-header">
                            <strong>À :</strong> utilisateur@exemple.com<br>
                            <strong>Objet :</strong> Bienvenue sur le nouveau site Occasion Photo !
                        </div>
                        <div class="card-body bg-light">
                            <?php 
                            $preview_message = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                                <div style="text-align: center; margin-bottom: 20px;">
                                    <img src="https://www.occasionphoto.fr/wp-content/uploads/2025/04/logo-occasion-photo-2025.jpg" alt="Occasion Photo" style="max-width: 200px; height: auto;">
                                </div>
                                <div style="background-color: #f9f9f9; padding: 20px; border-radius: 5px;">
                                    <p>Bonjour [Prénom Nom],</p>
                                    
                                    <p>Bienvenue sur le nouveau site !</p>
                                    
                                    <p>Nous avons le plaisir de vous accueillir sur la toute nouvelle version de notre site d\'annonces dédié au matériel photo.</p>
                                    
                                    <p>Afin de vous offrir une expérience plus fluide, plus rapide et plus agréable, nous avons entièrement repensé le site :</p>
                                    
                                    <ul>
                                        <li>Une navigation simplifiée, pour trouver ou publier une annonce plus facilement</li>
                                        <li>Une interface modernisée, adaptée à tous les appareils (ordinateur, tablette, mobile)</li>
                                        <li>Des outils de gestion améliorés, pour suivre vos annonces en toute simplicité</li>
                                    </ul>
                                    
                                    <p><strong>🔒 Important :</strong> pour des raisons de sécurité, les mots de passe n\'ont pas pu être transférés vers cette nouvelle version.</p>
                                    
                                    <p>Si vous aviez déjà un compte, il vous suffit de réinitialiser votre mot de passe pour accéder à votre espace personnel et gérer vos annonces.</p>
                                    
                                    <div style="text-align: center; margin: 25px 0;">
                                        <a href="#" style="display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">Réinitialiser mon mot de passe</a>
                                    </div>
                                    
                                    <p>Ou copiez ce lien dans votre navigateur :<br>
                                    <a href="#" style="word-break: break-all;">[LIEN_DE_REINITIALISATION]</a></p>
                                    
                                    <p>En quelques secondes, vous pourrez retrouver toutes vos annonces et profiter des nouvelles fonctionnalités du site.</p>
                                    
                                    <p>Cordialement,<br>L\'équipe Occasion Photo</p>
                                </div>
                                
                                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #777; text-align: center;">
                                    <p>© ' . date('Y') . ' Occasion Photo - Tous droits réservés<br>
                                    <a href="https://www.occasionphoto.fr" style="color: #777;">www.occasionphoto.fr</a></p>
                                </div>
                            </div>';
                            echo nl2br(htmlspecialchars($preview_message));
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php wp_enqueue_script('jquery'); ?>
    <script type="text/javascript">
    // Utiliser la variable ajaxurl de WordPress
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    </script>
    <script>
    jQuery(document).ready(function($) {
        let isRunning = false;
        let stopRequested = false;
        
        // Gérer la confirmation d'envoi
        $('#confirm-send-btn').click(function() {
            $('#confirmSendModal').modal('hide');
            $('button[data-bs-target="#confirmSendModal"]').addClass('d-none');
            $('#start-btn').removeClass('d-none');
        });
        
        // Démarrer l'envoi
        $('#start-btn').click(function() {
            if (isRunning) return;
            
            isRunning = true;
            stopRequested = false;
            $(this).prop('disabled', true);
            $('#spinner').removeClass('d-none');
            $('#stop-btn').removeClass('d-none');
            
            // Réinitialiser l'interface
            $('#email-list').html('');
            updateCounts(0, 0, 0);
            
            // Lancer l'envoi
            sendEmails();
        });
        
        // Arrêter l'envoi
        $('#stop-btn').click(function() {
            if (confirm('Voulez-vous vraiment arrêter l\'envoi ?')) {
                stopRequested = true;
                $(this).prop('disabled', true).text('Arrêt en cours...');
            }
        });
        
        function sendEmails() {
            if (stopRequested) {
                finishProcess('Arrêt demandé par l\'utilisateur');
                return;
            }
            
            const data = {
                action: 'send_reset_emails',
                security: '<?php echo wp_create_nonce('bulk_password_reset_nonce'); ?>',
                test_mode: '<?php echo $test_mode ? '1' : '0'; ?>'
            };
            
            // Ajouter un timestamp pour éviter la mise en cache
            data.timestamp = new Date().getTime();
            
            console.log('Envoi de la requête AJAX avec les données :', data);
            
            $.ajax({
                url: ajaxurl || window.location.href,
                type: 'POST',
                data: data,
                dataType: 'json',
                beforeSend: function(xhr) {
                    console.log('En-têtes de la requête :', xhr);
                },
                success: function(response, status, xhr) {
                    console.log('Réponse reçue :', response);
                    console.log('En-têtes de la réponse :', xhr.getAllResponseHeaders());
                    
                    if (response && response.success) {
                        const data = response.data;
                        updateResults(data);
                        finishProcess('Envoi terminé avec succès');
                    } else if (response && response.data && response.data.message) {
                        showError('Erreur : ' + response.data.message);
                    } else {
                        showError('Réponse inattendue du serveur : ' + JSON.stringify(response));
                    }
                },
                error: function(xhr, status, error) {
                    let errorMsg = 'Erreur ' + xhr.status + ': ' + status + '\n';
                    try {
                        // Essayer d'extraire le message d'erreur de la réponse
                        const response = xhr.responseText;
                        if (response) {
                            if (response.startsWith('{')) {
                                const jsonResponse = JSON.parse(response);
                                errorMsg += jsonResponse.data && jsonResponse.data.message 
                                    ? jsonResponse.data.message 
                                    : response;
                            } else {
                                errorMsg += response;
                            }
                        }
                    } catch (e) {
                        console.error('Erreur lors de l\'analyse de la réponse :', e);
                        errorMsg += xhr.responseText || 'Aucun détail supplémentaire';
                    }
                    
                    console.error('Erreur AJAX complète:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    });
                    
                    showError('Erreur lors de la communication avec le serveur :\n' + errorMsg);
                }
            });
        }
        
        function updateResults(data) {
            // Mettre à jour les compteurs
            updateCounts(data.total, data.sent, data.errors);
            
            // Mettre à jour la liste des emails
            const $emailList = $('#email-list');
            if ($emailList.find('.text-muted').length > 0) {
                $emailList.html('');
            }
            
            // Ajouter chaque email à la liste
            data.emails.forEach(function(email) {
                const statusClass = {
                    'success': 'success',
                    'error': 'error',
                    'pending': 'warning'
                }[email.status] || '';
                
                $emailList.prepend(`
                    <div class="email-item ${statusClass}">
                        <strong>${email.email}</strong>
                        <span class="float-end badge bg-${email.status === 'success' ? 'success' : 'danger'}">
                            ${email.status === 'success' ? '✓ Envoyé' : '✗ Erreur'}
                        </span>
                        <div class="small text-muted">${email.message || ''}</div>
                    </div>
                `);
            });
            
            // Faire défiler vers le haut pour voir le dernier élément
            $emailList.scrollTop(0);
        }
        
        function updateCounts(total, sent, errors) {
            $('#total-count').text(total);
            $('#sent-count').text(sent);
            $('#error-count').text(errors);
            
            // Mettre à jour la barre de progression
            const progress = total > 0 ? Math.round((sent + errors) / total * 100) : 0;
            const $progressBar = $('#progress-bar');
            $progressBar.css('width', progress + '%').text(progress + '%');
            
            // Changer la couleur en fonction de la progression
            $progressBar.removeClass('bg-success bg-warning bg-danger');
            if (progress < 70) {
                $progressBar.addClass('bg-primary');
            } else if (progress < 90) {
                $progressBar.addClass('bg-warning');
            } else {
                $progressBar.addClass('bg-success');
            }
        }
        
        function finishProcess(message) {
            isRunning = false;
            stopRequested = false;
            
            $('#start-btn').prop('disabled', false);
            $('#spinner').addClass('d-none');
            $('#stop-btn').addClass('d-none');
            
            if (message) {
                const $alert = $(`
                    <div class="alert alert-info alert-dismissible fade show mt-3" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                    </div>
                `);
                $('.container').prepend($alert);
                
                // Fermer automatiquement après 5 secondes
                setTimeout(() => {
                    $alert.alert('close');
                }, 5000);
            }
        }
    });
    </script>
</div> <!-- .wrap -->
