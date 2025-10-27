<?php
/**
 * NOIA - Gestionnaire de Legal Facts (Règles Légales Validées)
 * Version: 4.0.0 - Phase 2
 *
 * Gère la consultation et l'injection des règles légales validées
 * avec PRIORITÉ ABSOLUE (10/10) sur toutes les autres sources
 */

require_once __DIR__ . '/../config/config.php';

class LegalFactsManager {

    private $pdo;

    public function __construct() {
        try {
            $this->pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("LegalFactsManager - DB error: " . $e->getMessage());
            }
            throw new Exception("Erreur de connexion à la base de données");
        }
    }

    /**
     * Recherche les legal facts pertinents selon les tags détectés par pré-analyse
     *
     * @param array $tags Tags détectés (ex: ['quorum', 'FCTVA'])
     * @param string $question Question complète pour recherche textuelle
     * @return array Legal facts trouvés
     */
    public function findRelevantFacts($tags, $question = '') {
        $facts = [];

        // 1. Recherche par catégorie exacte (priorité absolue)
        if (!empty($tags)) {
            $placeholders = implode(',', array_fill(0, count($tags), '?'));

            $stmt = $this->pdo->prepare("
                SELECT
                    id,
                    categorie,
                    titre,
                    reference_legale,
                    contenu,
                    priority,
                    created_at
                FROM legal_facts
                WHERE categorie IN ($placeholders)
                AND is_active = 1
                ORDER BY priority DESC, created_at DESC
            ");

            $stmt->execute($tags);
            $facts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // 2. Si pas de résultats par catégorie, recherche textuelle dans le contenu
        if (empty($facts) && !empty($question)) {
            $stmt = $this->pdo->prepare("
                SELECT
                    id,
                    categorie,
                    titre,
                    reference_legale,
                    contenu,
                    priority,
                    created_at
                FROM legal_facts
                WHERE (
                    MATCH(titre, contenu) AGAINST(:question IN NATURAL LANGUAGE MODE)
                    OR titre LIKE :like_query
                    OR reference_legale LIKE :like_query
                )
                AND is_active = 1
                ORDER BY priority DESC, created_at DESC
                LIMIT 5
            ");

            $like_query = '%' . $question . '%';
            $stmt->execute([
                'question' => $question,
                'like_query' => $like_query
            ]);
            $facts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $facts;
    }

    /**
     * Recherche les legal facts par catégorie spécifique
     *
     * @param string $category Catégorie (quorum, FCTVA, M57, etc.)
     * @return array Legal facts de cette catégorie
     */
    public function getByCategory($category) {
        $stmt = $this->pdo->prepare("
            SELECT
                id,
                categorie,
                titre,
                reference_legale,
                contenu,
                priority,
                created_at
            FROM legal_facts
            WHERE categorie = :category
            AND is_active = 1
            ORDER BY priority DESC, created_at DESC
        ");

        $stmt->execute(['category' => $category]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtenir tous les legal facts actifs (pour cache ou backup)
     *
     * @return array Tous les legal facts
     */
    public function getAllActive() {
        $stmt = $this->pdo->query("
            SELECT
                id,
                categorie,
                titre,
                reference_legale,
                contenu,
                priority,
                created_at
            FROM legal_facts
            WHERE is_active = 1
            ORDER BY categorie ASC, priority DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Formate les legal facts pour injection dans le system prompt OpenAI
     *
     * @param array $facts Legal facts trouvés
     * @return string Texte formaté pour injection
     */
    public static function formatForPrompt($facts) {
        if (empty($facts)) {
            return '';
        }

        $formatted = "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $formatted .= "🔴 RÈGLES OFFICIELLES VALIDÉES - PRIORITÉ ABSOLUE 10/10\n";
        $formatted .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $formatted .= "Ces règles ont été VALIDÉES par des experts juridiques.\n";
        $formatted .= "Elles DOIVENT être utilisées EN PRIORITÉ sur toute autre source.\n";
        $formatted .= "NE PAS inventer de règles si elles sont déjà définies ci-dessous.\n\n";

        foreach ($facts as $fact) {
            $formatted .= "┌─────────────────────────────────────────────────┐\n";
            $formatted .= "│ CATÉGORIE : " . strtoupper($fact['categorie']) . "\n";
            $formatted .= "│ TITRE : {$fact['titre']}\n";
            $formatted .= "│ RÉFÉRENCE : {$fact['reference_legale']}\n";
            $formatted .= "│ PRIORITÉ : {$fact['priority']}/10 (ABSOLUE)\n";
            $formatted .= "└─────────────────────────────────────────────────┘\n\n";
            $formatted .= "RÈGLE VALIDÉE :\n";
            $formatted .= $fact['contenu'] . "\n\n";
            $formatted .= "⚠️ CETTE RÈGLE EST OFFICIELLE ET DOIT ÊTRE CITÉE EXACTEMENT.\n";
            $formatted .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        }

        $formatted .= "💡 INSTRUCTION CRITIQUE :\n";
        $formatted .= "Si la question concerne l'une de ces règles validées :\n";
        $formatted .= "1. Utilise UNIQUEMENT les informations ci-dessus\n";
        $formatted .= "2. Cite EXACTEMENT la référence légale indiquée\n";
        $formatted .= "3. NE PAS rechercher d'autres sources pour ces points spécifiques\n";
        $formatted .= "4. Si besoin de compléments, tu peux consulter les sources officielles APRÈS\n\n";

        return $formatted;
    }

    /**
     * Génère un résumé des legal facts pour le logging
     *
     * @param array $facts Legal facts utilisés
     * @return array Résumé pour logs
     */
    public static function getSummaryForLog($facts) {
        if (empty($facts)) {
            return [
                'count' => 0,
                'categories' => [],
                'used' => false
            ];
        }

        $categories = array_unique(array_column($facts, 'categorie'));

        return [
            'count' => count($facts),
            'categories' => $categories,
            'used' => true,
            'highest_priority' => max(array_column($facts, 'priority'))
        ];
    }

    /**
     * Vérifie si la table legal_facts existe et contient des données
     *
     * @return array Statut de la table
     */
    public function checkTableStatus() {
        try {
            // Vérifier l'existence de la table
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'legal_facts'");
            $table_exists = $stmt->rowCount() > 0;

            if (!$table_exists) {
                return [
                    'exists' => false,
                    'count' => 0,
                    'categories' => []
                ];
            }

            // Compter les entrées actives
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM legal_facts WHERE is_active = 1");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Lister les catégories disponibles
            $stmt = $this->pdo->query("
                SELECT DISTINCT categorie, COUNT(*) as count
                FROM legal_facts
                WHERE is_active = 1
                GROUP BY categorie
                ORDER BY categorie
            ");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'exists' => true,
                'count' => $count,
                'categories' => $categories
            ];

        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("LegalFactsManager - checkTableStatus error: " . $e->getMessage());
            }
            return [
                'exists' => false,
                'count' => 0,
                'categories' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Recherche intelligente combinant catégorie ET mots-clés
     *
     * @param array $tags Catégories détectées
     * @param string $question Question complète
     * @return array Legal facts pertinents
     */
    public function smartSearch($tags, $question) {
        // 1. Recherche par catégorie (priorité haute)
        $category_facts = [];
        if (!empty($tags)) {
            $category_facts = $this->findRelevantFacts($tags, '');
        }

        // 2. Recherche textuelle complémentaire
        $text_facts = [];
        if (!empty($question)) {
            $text_facts = $this->findRelevantFacts([], $question);
        }

        // 3. Fusionner et dédupliquer par ID
        $all_facts = array_merge($category_facts, $text_facts);
        $unique_facts = [];
        $seen_ids = [];

        foreach ($all_facts as $fact) {
            if (!in_array($fact['id'], $seen_ids)) {
                $unique_facts[] = $fact;
                $seen_ids[] = $fact['id'];
            }
        }

        // 4. Trier par priorité
        usort($unique_facts, function($a, $b) {
            return $b['priority'] - $a['priority'];
        });

        // 5. Limiter à 5 facts maximum pour ne pas surcharger le prompt
        return array_slice($unique_facts, 0, 5);
    }
}
