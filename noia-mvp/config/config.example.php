<?php
/**
 * NOIA MVP - Configuration
 * Charge les variables depuis .env
 */

// Fonction simple pour charger .env
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception(".env file not found at: {$path}");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorer commentaires
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parser KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Retirer guillemets si présents
            $value = trim($value, '"\'');

            // Définir comme variable d'environnement
            $_ENV[$key] = $value;
        }
    }
}

// Charger .env depuis la racine du projet
$envPath = __DIR__ . '/../.env';
loadEnv($envPath);

// Définir les constantes de configuration
define('OPENAI_API_KEY', $_ENV['OPENAI_API_KEY'] ?? '');
define('OPENAI_ASSISTANT_ID', $_ENV['OPENAI_ASSISTANT_ID'] ?? '');
define('OPENAI_MODEL', $_ENV['OPENAI_MODEL'] ?? 'gpt-4o');

define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'noia_db');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');

define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('APP_DEBUG', ($_ENV['APP_DEBUG'] ?? 'false') === 'true');
define('APP_URL', $_ENV['APP_URL'] ?? 'https://noia.erelys.fr');

define('SESSION_LIFETIME', (int)($_ENV['SESSION_LIFETIME'] ?? 7200));
define('SESSION_SECURE', ($_ENV['SESSION_SECURE'] ?? 'true') === 'true');

define('RATE_LIMIT_REQUESTS', (int)($_ENV['RATE_LIMIT_REQUESTS'] ?? 100));
define('RATE_LIMIT_PERIOD', (int)($_ENV['RATE_LIMIT_PERIOD'] ?? 3600));

// Configuration PHP
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configuration de session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', SESSION_SECURE ? 1 : 0);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

// Timezone
date_default_timezone_set('Europe/Paris');
