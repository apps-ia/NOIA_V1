<?php
/**
 * NOIA - Test Proxy
 * Version: 2.0.0
 *
 * Ce fichier permet de tester si proxy.php fonctionne
 */

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'test' => 'OK',
    'message' => 'Le serveur PHP fonctionne',
    'php_version' => phpversion(),
    'curl_available' => function_exists('curl_init') ? 'Oui' : 'Non',
    'config_file' => file_exists(__DIR__ . '/config/config.php') ? 'Trouvé' : 'Manquant',
    'proxy_file' => file_exists(__DIR__ . '/api/proxy.php') ? 'Trouvé' : 'Manquant',
    'time' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);
