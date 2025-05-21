# WP Password Reset Bulk

Un plugin WordPress pour envoyer des emails de réinitialisation de mot de passe en masse aux utilisateurs.

## Description

Ce plugin permet aux administrateurs de site WordPress d'envoyer des emails de réinitialisation de mot de passe à plusieurs utilisateurs à la fois, avec la possibilité de filtrer par rôle d'utilisateur. Il inclut également un mode test pour vérifier le bon fonctionnement avant d'envoyer les vrais emails.

## Fonctionnalités

- Envoi d'emails de réinitialisation de mot de passe en masse
- Filtrage des utilisateurs par rôle
- Mode test avec des adresses email de test
- Interface utilisateur intuitive avec suivi en temps réel
- Journalisation des résultats avec détails des échecs
- Compatible avec les dernières versions de WordPress

## Installation

1. Téléchargez le dossier du plugin
2. Copiez le dossier dans le répertoire `/wp-content/plugins/` de votre installation WordPress
3. Activez le plugin dans le menu "Extensions" de WordPress
4. Accédez à la page "Outils > Réinitialisation MDP" pour commencer

## Utilisation

1. **Mode Test** : Cochez la case "Mode test" pour envoyer des emails uniquement aux adresses de test définies.
2. **Sélection des rôles** : Décochez le mode test pour sélectionner les rôles des utilisateurs qui recevront l'email.
3. **Aperçu** : Cliquez sur "Aperçu de l'email" pour voir à quoi ressemblera l'email envoyé.
4. **Envoi** : Cliquez sur "Envoyer les emails de réinitialisation" pour lancer le processus.
5. **Suivi** : Suivez la progression en temps réel et consultez les résultats détaillés.

## Personnalisation

### Traductions

Les fichiers de traduction sont disponibles dans le dossier `/languages/`. Vous pouvez les copier et les adapter à votre langue.

### Style CSS

Vous pouvez personnaliser l'apparence en ajoutant votre propre CSS dans le fichier `/assets/css/admin.css`.

## Sécurité

- Seuls les utilisateurs avec la capacité `manage_options` peuvent accéder à cette fonctionnalité.
- Les nonces de sécurité sont utilisés pour toutes les actions AJAX.
- Les emails de réinitialisation génèrent des liens uniques et sécurisés.

## Support

Pour toute question ou problème, veuillez ouvrir une issue sur le dépôt GitHub du plugin.

## Licence

Ce plugin est sous licence GPL v2 ou ultérieure.

## Auteur

Votre Nom - [Votre Site](https://votresite.com)

## Version

2.0.0
