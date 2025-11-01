<?php
/**
 * NOIA MVP - Cleanup Old Threads
 * Script RGPD: Supprime automatiquement les threads > 30 jours
 *
 * À configurer en CRON quotidien:
 * 0 2 * * * /usr/bin/php /path/to/scripts/cleanup_old_threads.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Database/Database.php';
require_once __DIR__ . '/../src/Assistant/AssistantManager.php';

echo "[" . date('Y-m-d H:i:s') . "] 🧹 NOIA - Nettoyage des threads\n";

$db = Database::getInstance()->getConnection();
$assistant = new AssistantManager();

// Récupérer les threads > 30 jours
$stmt = $db->prepare("
    SELECT id, thread_id
    FROM conversations
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stmt->execute();
$oldThreads = $stmt->fetchAll();

if (empty($oldThreads)) {
    echo "✅ Aucun thread à supprimer\n";
    exit(0);
}

echo "📊 " . count($oldThreads) . " threads à supprimer\n";

$deleted = 0;
$errors = 0;

foreach ($oldThreads as $thread) {
    try {
        // Supprimer sur OpenAI
        $assistant->deleteThread($thread['thread_id']);

        // Supprimer en base
        $stmt = $db->prepare("DELETE FROM conversations WHERE id = ?");
        $stmt->execute([$thread['id']]);

        $deleted++;
        echo "✅ Thread {$thread['thread_id']} supprimé\n";
    } catch (Exception $e) {
        $errors++;
        echo "❌ Erreur pour {$thread['thread_id']}: {$e->getMessage()}\n";
    }
}

echo "\n========================================\n";
echo "✅ Supprimés: {$deleted}\n";
echo "❌ Erreurs: {$errors}\n";
echo "[" . date('Y-m-d H:i:s') . "] 🏁 Nettoyage terminé\n";
