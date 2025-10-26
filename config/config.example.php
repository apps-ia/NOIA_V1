<?php
/**
 * NOIA - Configuration Example
 * Version: 2.0.0 (Direct OpenAI Integration)
 *
 * IMPORTANT : Copiez ce fichier en config.php et remplissez vos vraies valeurs
 * Ne commitez JAMAIS config.php avec de vraies clés API !
 */

// Configuration de la base de données
define('DB_HOST', 'mysql47.perso.ovh.net');  // Serveur MySQL OVH
define('DB_NAME', 'votre_base');              // Nom de votre base de données
define('DB_USER', 'votre_utilisateur');       // Utilisateur MySQL
define('DB_PASS', 'votre_mot_de_passe');      // Mot de passe MySQL

// Configuration OpenAI (DIRECT - Plus besoin de Make.com!)
define('OPENAI_API_KEY', 'sk-votre-cle-api-openai');  // Votre clé API OpenAI
define('OPENAI_MODEL', 'gpt-4-turbo');                 // Modèle à utiliser (gpt-4-turbo, gpt-4o, gpt-3.5-turbo)
define('OPENAI_MAX_TOKENS', 2500);                     // Limite de tokens pour la réponse (augmenté pour réponses détaillées de niveau Secrétaire Général)
define('OPENAI_TEMPERATURE', 0.3);                     // Température (0-2, plus bas = plus déterministe et précis pour administration)
define('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'); // Modèle pour embeddings (RAG)

// Configuration de sécurité
define('RATE_LIMIT_REQUESTS', 30);    // Nombre maximum de requêtes
define('RATE_LIMIT_PERIOD', 3600);    // Période en secondes (3600 = 1 heure)
define('MAX_QUESTION_LENGTH', 500);   // Longueur maximale d'une question
define('ENABLE_LOGGING', true);       // Activer les logs
define('LOG_FILE', __DIR__ . '/../logs/queries.log');
define('RATE_LIMIT_FILE', __DIR__ . '/../logs/rate_limit.json');

// Configuration CORS (si nécessaire)
define('ALLOWED_ORIGINS', '*');  // À restreindre en production (ex: 'https://noia.votre-domaine.fr')

// Configuration RAG et Recherche Web
define('ENABLE_WEB_SEARCH', true);        // Activer la recherche web sur sites officiels
define('ENABLE_RAG', true);                // Activer la recherche dans la base documentaire
define('DOCUMENTS_DIR', __DIR__ . '/../documents'); // Répertoire de stockage des documents

// Timezone
date_default_timezone_set('Europe/Paris');

// Mode debug (désactiver en production)
define('DEBUG_MODE', false);
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
