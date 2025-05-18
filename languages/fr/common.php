<?php
/**
 * French Common Translations
 */

return [
    // General
    'app_name' => 'Annuaire du Personnel',
    'admin_area' => 'Espace Admin',
    'login' => 'Connexion',
    'logout' => 'Déconnexion',
    'save' => 'Enregistrer',
    'cancel' => 'Annuler',
    'delete' => 'Supprimer',
    'edit' => 'Modifier',
    'add' => 'Ajouter',
    'logo' => 'Logo',
    'search' => 'Rechercher',
    'filter' => 'Filtrer',
    'sort' => 'Trier',
    'actions' => 'Actions',
    'confirm' => 'Confirmer',
    'yes' => 'Oui',
    'no' => 'Non',
    'back' => 'Retour',
    'next' => 'Suivant',
    'previous' => 'Précédent',
    'submit' => 'Soumettre',
    'reset' => 'Réinitialiser',
    'success' => 'Succès',
    'error' => 'Erreur',
    'warning' => 'Avertissement',
    'info' => 'Information',
    'loading' => 'Chargement...',

    // Navigation
    'home' => 'Accueil',
    'dashboard' => 'Tableau de bord',
    'settings' => 'Paramètres',
    'profile' => 'Profil',

    // Filters
    'all_departments' => 'Tous les services',
    'all_companies' => 'Toutes les entreprises',
    'search' => 'Rechercher',
    'search_placeholder' => 'Rechercher par nom ou titre de poste...',
    'search_admin_placeholder' => 'Rechercher par nom ou titre de poste',

    // Sorting
    'name_asc' => 'Nom (A-Z)',
    'name_desc' => 'Nom (Z-A)',
    'department_asc' => 'Service (A-Z)',
    'department_desc' => 'Service (Z-A)',
    'company_asc' => 'Entreprise (A-Z)',
    'company_desc' => 'Entreprise (Z-A)',

    // 404 Page
    'page_not_found' => 'Page non trouvée',
    'page_not_found_message' => 'La page que vous recherchiez n\'a pas été trouvée. Vous avez été redirigé vers l\'Annuaire du Personnel.',

    // Language
    'language' => 'Langue',
    'english' => 'Anglais',
    'french' => 'Français',
    'change_language' => 'Changer de langue',
    'save_language_settings' => 'Enregistrer les paramètres de langue',

    // Installer
    'installer' => 'Installateur',
    'installation_setup' => 'Configuration de l\'installation',
    'configure_database_admin' => 'Configurez votre base de données et votre compte administrateur',
    'is_installed' => 'L\'application est installée !',
    'reinstall_instructions' => 'Si vous souhaitez réinstaller, veuillez supprimer la ligne <code class="bg-gray-100 px-2 py-1 rounded text-sm">DB_INSTALLED=true</code> de votre fichier <code class="bg-gray-100 px-2 py-1 rounded text-sm">.env</code>.',
    'go_to_application' => 'Accéder à l\'application',
    'database_configuration' => 'Configuration de la base de données',
    'database_host' => 'L\'hôte de la base de données',
    'database_name' => 'Le mom de la base de données',
    'database_user' => 'L\'utilisateur de la base de données',
    'database_password' => 'Le mot de passe de la base de données',
    'table_prefix' => 'Préfixe de table',
    'prefix_note' => 'Le caractère underscore sera ajouté automatiquement s\'il est manquant (ex: "sd" devient "sd_")',
    'create_database' => 'Créer la base de données si elle n\'existe pas',
    'include_example_data' => 'Inclure des données d\'exemple (personnel, départements, entreprises)',
    'test_connection' => 'Tester la connexion',
    'admin_account' => 'Compte administrateur',
    'admin_username' => 'Nom d\'utilisateur admin',
    'admin_password' => 'Mot de passe admin',
    'required_fields_note' => 'Tous les champs avec des étiquettes sont obligatoires',
    'install_now' => 'Installer maintenant',
    'installer_version' => 'Installateur v',

    // Validation messages
    'field_required' => '%s est requis',
    'please_fix_errors' => 'Veuillez corriger les erreurs ci-dessous avant de continuer.',
    'database_name_invalid_chars' => 'Le nom de la base de données ne peut contenir que des lettres, des chiffres et des underscores',
    'prefix_invalid_chars' => 'Le préfixe de table ne peut contenir que des lettres, des chiffres et des underscores',
    'password_too_short' => 'Le mot de passe doit comporter au moins 6 caractères',
    'connection_successful' => 'Connexion réussie !',
    'connection_failed' => 'Échec de la connexion : %s',
    'installation_completed' => 'Installation terminée avec succès !',
    'database_initialized_env_failed' => 'Base de données initialisée mais échec de la mise à jour du fichier .env.',
    'database_creation_failed' => 'Erreur lors de la création de la base de données : %s',
];
