<?php
/**
 * NOIA - Système de Pré-Analyse Intelligente
 * Version: 4.0.0 - Phase 2
 *
 * Analyse la question AVANT l'appel OpenAI pour :
 * - Détecter les cas spéciaux (quorum, FCTVA, IFSE, M57, etc.)
 * - Classifier la question par domaine
 * - Suggérer les sources prioritaires
 * - Forcer certaines actions (consultation legal_facts, sources officielles)
 */

class PreAnalysis {

    /**
     * Mots-clés par catégorie avec niveau de priorité
     */
    private const KEYWORDS = [
        'quorum' => [
            'keywords' => ['quorum', 'reconvocation', 'séance', 'conseil municipal', 'délibération', 'majorité', 'élus présents'],
            'category' => 'juridique',
            'priority' => 10,
            'force_legal_facts' => true,
            'force_official_sources' => true,
            'official_sites' => ['legifrance.gouv.fr'],
            'legal_facts_tags' => ['quorum']
        ],

        'fctva' => [
            'keywords' => ['fctva', 'fonds de compensation tva', 'compensation tva', 'tva déductible', 'article 1615'],
            'category' => 'finances',
            'priority' => 10,
            'force_legal_facts' => true,
            'force_official_sources' => true,
            'official_sites' => ['collectivites-locales.gouv.fr', 'legifrance.gouv.fr'],
            'legal_facts_tags' => ['FCTVA']
        ],

        'ifse' => [
            'keywords' => ['ifse', 'régime indemnitaire', 'rifseep', 'prime', 'indemnité'],
            'category' => 'rh',
            'priority' => 9,
            'force_legal_facts' => true,
            'force_official_sources' => true,
            'official_sites' => ['emploi-collectivites.fr', 'cdg'],
            'legal_facts_tags' => ['IFSE', 'RH']
        ],

        'm57' => [
            'keywords' => ['m57', 'compte', 'imputation', 'comptabilité', 'budget', 'nomenclature', 'chapitre', 'article comptable'],
            'category' => 'finances',
            'priority' => 9,
            'force_legal_facts' => true,
            'force_official_sources' => true,
            'official_sites' => ['collectivites-locales.gouv.fr'],
            'legal_facts_tags' => ['M57']
        ],

        'rh_grilles' => [
            'keywords' => ['grille', 'indice', 'salaire', 'rémunération', 'traitement', 'échelon', 'grade', 'catégorie'],
            'category' => 'rh',
            'priority' => 8,
            'force_legal_facts' => false,
            'force_official_sources' => true,
            'official_sites' => ['emploi-collectivites.fr', 'cdg'],
            'legal_facts_tags' => ['RH']
        ],

        'marches_publics' => [
            'keywords' => ['marché public', 'mapa', 'procédure', 'seuil', 'appel d\'offres', 'code commande publique'],
            'category' => 'juridique',
            'priority' => 8,
            'force_legal_facts' => true,
            'force_official_sources' => true,
            'official_sites' => ['economie.gouv.fr', 'legifrance.gouv.fr'],
            'legal_facts_tags' => ['marchés publics']
        ],

        'deliberation' => [
            'keywords' => ['délibération', 'modèle', 'vote', 'conseil', 'séance', 'ordre du jour'],
            'category' => 'juridique',
            'priority' => 7,
            'force_legal_facts' => false,
            'force_official_sources' => true,
            'official_sites' => ['collectivites-locales.gouv.fr', 'legifrance.gouv.fr'],
            'legal_facts_tags' => []
        ],

        'cgct' => [
            'keywords' => ['cgct', 'code général collectivités', 'article l', 'article r'],
            'category' => 'juridique',
            'priority' => 9,
            'force_legal_facts' => false,
            'force_official_sources' => true,
            'official_sites' => ['legifrance.gouv.fr'],
            'legal_facts_tags' => ['CGCT']
        ],

        'urbanisme' => [
            'keywords' => ['permis de construire', 'urbanisme', 'plu', 'certificat', 'déclaration préalable', 'autorisation'],
            'category' => 'urbanisme',
            'priority' => 7,
            'force_legal_facts' => false,
            'force_official_sources' => true,
            'official_sites' => ['service-public.fr', 'legifrance.gouv.fr'],
            'legal_facts_tags' => []
        ]
    ];

    /**
     * Analyse une question et retourne le contexte détecté
     *
     * @param string $question La question posée
     * @return array Contexte de pré-analyse
     */
    public static function analyze($question) {
        $question_lower = mb_strtolower($question, 'UTF-8');

        // Détection des mots-clés
        $detected_contexts = [];
        $max_priority = 0;
        $force_legal_facts = false;
        $force_official_sources = false;
        $suggested_sites = [];
        $legal_facts_tags = [];

        foreach (self::KEYWORDS as $context_name => $config) {
            $match_count = 0;

            foreach ($config['keywords'] as $keyword) {
                if (strpos($question_lower, mb_strtolower($keyword, 'UTF-8')) !== false) {
                    $match_count++;
                }
            }

            // Si au moins 1 mot-clé correspond
            if ($match_count > 0) {
                $detected_contexts[$context_name] = [
                    'name' => $context_name,
                    'category' => $config['category'],
                    'priority' => $config['priority'],
                    'match_count' => $match_count,
                    'confidence' => min(100, ($match_count / count($config['keywords'])) * 100)
                ];

                // Mettre à jour les flags
                if ($config['priority'] > $max_priority) {
                    $max_priority = $config['priority'];
                }

                if ($config['force_legal_facts']) {
                    $force_legal_facts = true;
                    $legal_facts_tags = array_merge($legal_facts_tags, $config['legal_facts_tags']);
                }

                if ($config['force_official_sources']) {
                    $force_official_sources = true;
                    $suggested_sites = array_merge($suggested_sites, $config['official_sites']);
                }
            }
        }

        // Trier par priorité
        usort($detected_contexts, function($a, $b) {
            return $b['priority'] - $a['priority'];
        });

        // Déterminer la catégorie principale
        $main_category = !empty($detected_contexts)
            ? $detected_contexts[0]['category']
            : self::classifyByGeneral($question_lower);

        // Déterminer si c'est une question critique
        $is_critical = $max_priority >= 9;

        return [
            'detected_contexts' => $detected_contexts,
            'main_category' => $main_category,
            'priority' => $max_priority,
            'is_critical' => $is_critical,
            'force_legal_facts' => $force_legal_facts,
            'force_official_sources' => $force_official_sources,
            'suggested_sites' => array_unique($suggested_sites),
            'legal_facts_tags' => array_unique($legal_facts_tags),
            'analysis_timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Classification générale si aucun mot-clé spécifique détecté
     */
    private static function classifyByGeneral($question_lower) {
        // Finances
        if (preg_match('/(budget|compte|dépense|recette|taxe|impôt|euro|€|subvention)/u', $question_lower)) {
            return 'finances';
        }

        // RH
        if (preg_match('/(agent|employé|personnel|recrutement|carrière|congé|absence|contrat)/u', $question_lower)) {
            return 'rh';
        }

        // Juridique
        if (preg_match('/(article|loi|décret|arrêté|circulaire|règlement|code|juridique)/u', $question_lower)) {
            return 'juridique';
        }

        // Urbanisme
        if (preg_match('/(construire|bâtiment|terrain|lotissement|zone|plan)/u', $question_lower)) {
            return 'urbanisme';
        }

        return 'general';
    }

    /**
     * Génère des suggestions de recherche optimisées
     */
    public static function generateSearchQueries($question, $analysis) {
        $queries = [];

        // Query principale
        $queries[] = [
            'query' => $question,
            'type' => 'main',
            'priority' => 10
        ];

        // Queries spécifiques selon le contexte détecté
        foreach ($analysis['detected_contexts'] as $context) {
            switch ($context['name']) {
                case 'quorum':
                    $queries[] = [
                        'query' => 'Article L2121-17 CGCT quorum conseil municipal reconvocation',
                        'type' => 'legal_reference',
                        'priority' => 10
                    ];
                    break;

                case 'fctva':
                    $queries[] = [
                        'query' => 'Article L1615-1 CGCT FCTVA dépenses éligibles',
                        'type' => 'legal_reference',
                        'priority' => 10
                    ];
                    break;

                case 'ifse':
                    $queries[] = [
                        'query' => 'RIFSEEP régime indemnitaire montants plafonds',
                        'type' => 'legal_reference',
                        'priority' => 9
                    ];
                    break;

                case 'm57':
                    $queries[] = [
                        'query' => 'Instruction M57 nomenclature comptable DGFiP',
                        'type' => 'official_doc',
                        'priority' => 9
                    ];
                    break;
            }
        }

        return $queries;
    }

    /**
     * Détermine le tool_choice pour OpenAI en fonction du contexte
     */
    public static function determineToolChoice($analysis) {
        // Si contexte critique ou force_official_sources = true
        if ($analysis['is_critical'] || $analysis['force_official_sources']) {
            // Forcer l'appel de search_official_websites
            return [
                'type' => 'function',
                'function' => ['name' => 'search_official_websites']
            ];
        }

        // Sinon, laisser GPT décider
        return 'auto';
    }

    /**
     * Génère un message de log pour le suivi
     */
    public static function getAnalysisLog($analysis) {
        return [
            'category' => $analysis['main_category'],
            'priority' => $analysis['priority'],
            'is_critical' => $analysis['is_critical'],
            'contexts_detected' => count($analysis['detected_contexts']),
            'force_legal_facts' => $analysis['force_legal_facts'],
            'force_official_sources' => $analysis['force_official_sources']
        ];
    }
}
