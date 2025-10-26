<?php
/**
 * NOIA - Web Search Module
 * Recherche sur sites officiels avant de répondre
 */

require_once __DIR__ . '/../config/config.php';

class WebSearchEngine {

    private $official_sites = [
        'legifrance' => 'https://www.legifrance.gouv.fr',
        'service_public' => 'https://www.service-public.fr',
        'dgcl' => 'https://www.collectivites-locales.gouv.fr',
        'dgfip' => 'https://www.impots.gouv.fr',
        'cnfpt' => 'https://www.cnfpt.fr',
        'emploi_collectivites' => 'https://www.emploi-collectivites.fr'
    ];

    /**
     * Recherche via DuckDuckGo (gratuit, pas d'API key)
     */
    public function searchDuckDuckGo($query, $site = null) {
        $search_query = $site ? "site:{$site} {$query}" : $query;
        $encoded_query = urlencode($search_query);

        $url = "https://html.duckduckgo.com/html/?q={$encoded_query}";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; NOIA/2.0)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 || !$response) {
            return [];
        }

        return $this->parseDuckDuckGoResults($response);
    }

    /**
     * Parse les résultats HTML DuckDuckGo
     */
    private function parseDuckDuckGoResults($html) {
        $results = [];

        // Extraction basique avec regex (améliorer avec DOMDocument si besoin)
        preg_match_all('/<a class="result__a" href="([^"]+)">([^<]+)<\/a>/', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $i => $match) {
            if ($i >= 5) break; // Limiter à 5 résultats

            $results[] = [
                'title' => html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5),
                'url' => html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5),
                'source' => $this->identifyOfficialSource($match[1])
            ];
        }

        return $results;
    }

    /**
     * Identifie la source officielle
     */
    private function identifyOfficialSource($url) {
        foreach ($this->official_sites as $name => $domain) {
            if (strpos($url, str_replace('https://www.', '', $domain)) !== false) {
                return $name;
            }
        }
        return 'web';
    }

    /**
     * Récupère le contenu d'une page
     */
    public function fetchPageContent($url, $max_length = 5000) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; NOIA/2.0)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 || !$response) {
            return null;
        }

        // Extraire le texte du HTML
        $text = $this->extractTextFromHTML($response);

        // Limiter la longueur
        return substr($text, 0, $max_length);
    }

    /**
     * Extrait le texte d'un HTML
     */
    private function extractTextFromHTML($html) {
        // Supprimer scripts et styles
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);

        // Convertir en texte
        $text = strip_tags($html);

        // Nettoyer les espaces multiples
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Recherche multi-sources sur sites officiels
     */
    public function searchOfficialSources($query, $topic = 'general') {
        $results = [];

        // Définir les sites pertinents selon le sujet
        $relevant_sites = $this->getRelevantSites($topic);

        foreach ($relevant_sites as $site_name => $site_url) {
            $site_results = $this->searchDuckDuckGo($query, $site_url);

            foreach ($site_results as $result) {
                $result['official_site'] = $site_name;
                $results[] = $result;
            }

            // Limiter les appels pour ne pas bloquer
            if (count($results) >= 10) break;
            usleep(500000); // 0.5 seconde entre chaque recherche
        }

        return array_slice($results, 0, 10);
    }

    /**
     * Détermine les sites pertinents selon le sujet
     */
    private function getRelevantSites($topic) {
        $sites_map = [
            'fctva' => ['legifrance', 'dgfip', 'dgcl'],
            'comptabilite' => ['dgfip', 'dgcl'],
            'm57' => ['dgfip', 'dgcl'],
            'rh' => ['cnfpt', 'emploi_collectivites', 'service_public'],
            'juridique' => ['legifrance', 'service_public', 'dgcl'],
            'deliberation' => ['legifrance', 'dgcl'],
            'marches_publics' => ['service_public', 'dgcl'],
            'general' => ['legifrance', 'service_public', 'dgcl']
        ];

        $topic_key = strtolower($topic);
        $selected_sites = $sites_map[$topic_key] ?? $sites_map['general'];

        $result = [];
        foreach ($selected_sites as $site_key) {
            if (isset($this->official_sites[$site_key])) {
                $result[$site_key] = $this->official_sites[$site_key];
            }
        }

        return $result;
    }

    /**
     * Détecte le sujet de la question
     */
    public function detectTopic($question) {
        $keywords = [
            'fctva' => ['fctva', 'tva', 'remboursement tva'],
            'comptabilite' => ['compte', 'comptable', 'imputation', 'budget'],
            'm57' => ['m57', 'm14', 'instruction comptable'],
            'rh' => ['rh', 'agent', 'recrutement', 'salaire', 'grille', 'indiciaire', 'titulaire', 'contractuel'],
            'juridique' => ['juridique', 'droit', 'légal', 'article', 'loi', 'décret'],
            'deliberation' => ['délibération', 'conseil municipal', 'séance', 'vote'],
            'marches_publics' => ['marché', 'marchés publics', 'appel d\'offres', 'ccag']
        ];

        $question_lower = mb_strtolower($question);

        foreach ($keywords as $topic => $words) {
            foreach ($words as $word) {
                if (strpos($question_lower, $word) !== false) {
                    return $topic;
                }
            }
        }

        return 'general';
    }
}

/**
 * Fonction helper pour recherche rapide
 */
function searchWeb($question, $fetch_content = false) {
    $engine = new WebSearchEngine();
    $topic = $engine->detectTopic($question);
    $results = $engine->searchOfficialSources($question, $topic);

    // Optionnellement récupérer le contenu des pages
    if ($fetch_content && !empty($results)) {
        foreach ($results as &$result) {
            $content = $engine->fetchPageContent($result['url'], 2000);
            if ($content) {
                $result['content'] = $content;
            }
        }
    }

    return [
        'topic' => $topic,
        'results' => $results,
        'count' => count($results)
    ];
}
