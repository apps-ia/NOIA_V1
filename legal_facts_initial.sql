-- ============================================================================
-- NOIA v4.0 - Legal Facts Initiaux
-- Base de connaissances validée avec priorité ABSOLUE (10/10)
-- ============================================================================
--
-- INSTRUCTIONS :
-- 1. Aller sur cPanel → phpMyAdmin
-- 2. Sélectionner votre base de données NOIA
-- 3. Onglet "SQL"
-- 4. Copier-coller ce fichier complet
-- 5. Cliquer sur "Exécuter"
--
-- MAINTENANCE :
-- - Mettre à jour date_maj lors de modifications de législation
-- - Vérifier les seuils et montants annuellement (ex: marchés publics)
-- - Ajouter de nouveaux legal facts selon besoins
-- ============================================================================

-- ============================================================================
-- 1. QUORUM - Reconvocation conseil municipal
-- ============================================================================
INSERT INTO legal_facts (categorie, titre, reference_legale, contenu, priority, source_url, date_maj, is_active)
VALUES (
  'quorum',
  'Quorum - Reconvocation (aucun quorum requis)',
  'Article L2121-17 du CGCT',
  'Lors d\'une reconvocation du conseil municipal, AUCUN QUORUM n\'est requis. Le conseil municipal peut valablement délibérer quel que soit le nombre de membres présents. Cette règle s\'applique lorsque le quorum (majorité absolue des membres en exercice) n\'a pas été atteint lors de la première convocation. La reconvocation doit intervenir au moins trois jours francs après la première réunion. L\'ordre du jour de la reconvocation doit être strictement identique à celui de la première séance.',
  10,
  'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006389868',
  CURDATE(),
  1
);

-- ============================================================================
-- 2. QUORUM - Première convocation (majorité absolue)
-- ============================================================================
INSERT INTO legal_facts (categorie, titre, reference_legale, contenu, priority, source_url, date_maj, is_active)
VALUES (
  'quorum',
  'Quorum - Première convocation (majorité absolue requise)',
  'Article L2121-17 du CGCT',
  'Pour la première convocation d\'une séance du conseil municipal, le quorum est atteint lorsque la MAJORITÉ ABSOLUE des membres en exercice est présente. Calcul : nombre de conseillers en exercice / 2 (arrondi à l\'entier supérieur). Exemple : commune de 15 conseillers → quorum = 8 conseillers minimum. Les absents excusés ne comptent pas dans le quorum. Si le quorum n\'est pas atteint, la séance ne peut avoir lieu et une reconvocation est nécessaire (sans quorum requis).',
  10,
  'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006389868',
  CURDATE(),
  1
);

-- ============================================================================
-- 3. FCTVA - Dépenses éligibles
-- ============================================================================
INSERT INTO legal_facts (categorie, titre, reference_legale, contenu, priority, source_url, date_maj, is_active)
VALUES (
  'fctva',
  'FCTVA - Dépenses éligibles et taux 2024',
  'Article L1615-1 du CGCT',
  'Le Fonds de Compensation pour la TVA (FCTVA) compense la TVA acquittée sur les dépenses réelles d\'investissement des collectivités. DÉPENSES ÉLIGIBLES : travaux sur biens immeubles (constructions, voirie, réseaux), acquisitions immobilières, études et honoraires liés aux investissements, certains matériels et mobiliers spécifiques. DÉPENSES NON ÉLIGIBLES : dépenses de fonctionnement, matériel mobilier courant, véhicules (sauf exceptions : véhicules incendie, bennes à ordures), subventions d\'équipement versées à des tiers. TAUX 2024 : 16,404% du montant TTC. DÉCLARATION : annuelle avant le 31 janvier N+2 via l\'application FCTVA dématérialisée.',
  10,
  'https://www.collectivites-locales.gouv.fr/fonds-de-compensation-pour-la-tva-fctva',
  CURDATE(),
  1
);

-- ============================================================================
-- 4. IFSE / RIFSEEP - Régime indemnitaire
-- ============================================================================
INSERT INTO legal_facts (categorie, titre, reference_legale, contenu, priority, source_url, date_maj, is_active)
VALUES (
  'ifse',
  'RIFSEEP - Régime indemnitaire fonction publique territoriale',
  'Décret n° 2014-513 du 20 mai 2014',
  'Le RIFSEEP (Régime Indemnitaire tenant compte des Fonctions, Sujétions, Expertise et Engagement Professionnel) se compose de 2 parts : 1) IFSE (Indemnité de Fonctions, de Sujétions et d\'Expertise) : part principale versée mensuellement, montant annuel déterminé par délibération selon critères professionnels (fonctions, sujétions, expertise). 2) CIA (Complément Indemnitaire Annuel) : facultatif, plafonné à 20% de l\'IFSE, lié à l\'engagement professionnel et à la manière de servir. PLAFONDS : définis par arrêtés ministériels selon cadres d\'emplois. MISE EN PLACE : délibération obligatoire précisant critères, montants, groupes de fonctions et bénéficiaires. COMPATIBLE avec NBI (Nouvelle Bonification Indiciaire). NON CUMULABLE avec anciennes primes (PFR, IAT, etc.).',
  10,
  'https://www.legifrance.gouv.fr/loda/id/JORFTEXT000028965448/',
  CURDATE(),
  1
);

-- ============================================================================
-- 5. M57 - Nomenclature comptable
-- ============================================================================
INSERT INTO legal_facts (categorie, titre, reference_legale, contenu, priority, source_url, date_maj, is_active)
VALUES (
  'm57',
  'M57 - Instruction budgétaire et comptable',
  'Arrêté du 20 décembre 2018 - Instruction budgétaire et comptable M57',
  'L\'instruction M57 est le cadre comptable de référence pour les collectivités territoriales et leurs établissements publics. STRUCTURE : Section de fonctionnement (comptes 6 et 7) : charges et produits de l\'exercice. Section d\'investissement (comptes 1, 2, 4) : opérations affectant le patrimoine. ORGANISATION : Chapitre > Article > Compte. EXEMPLES PRINCIPAUX : Chapitre 011 (charges à caractère général : fournitures, entretien, assurances), Chapitre 012 (charges de personnel et frais assimilés), Chapitre 65 (autres charges de gestion courante), Chapitre 70 (produits des services et du domaine), Chapitre 21 (immobilisations corporelles), Chapitre 23 (immobilisations en cours). OBLIGATOIRE pour toutes les collectivités territoriales.',
  10,
  'https://www.collectivites-locales.gouv.fr/finances-locales/instruction-budgetaire-et-comptable-m57',
  CURDATE(),
  1
);

-- ============================================================================
-- 6. Convocation conseil municipal - Délais légaux
-- ============================================================================
INSERT INTO legal_facts (categorie, titre, reference_legale, contenu, priority, source_url, date_maj, is_active)
VALUES (
  'deliberation',
  'Convocation conseil municipal - Délais légaux',
  'Articles L2121-10 et L2121-11 du CGCT',
  'Le conseil municipal est convoqué par le maire. DÉLAIS DE CONVOCATION : 5 jours francs minimum avant la séance (délai normal), 3 jours francs en cas d\'URGENCE (décision motivée du maire inscrite en début de séance), 1 jour franc pour la première réunion suivant l\'élection du maire. Les jours francs excluent le jour de l\'acte de convocation ET le jour de la réunion. CONTENU : la convocation doit mentionner l\'ordre du jour arrêté par le maire. Elle est accompagnée d\'une note explicative de synthèse pour chaque question inscrite à l\'ordre du jour. TRANSMISSION : simultanée au préfet (dématérialisation obligatoire via plateforme @CTES).',
  10,
  'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006389858',
  CURDATE(),
  1
);

-- ============================================================================
-- 7. Marchés publics - Seuils et procédures 2024
-- ============================================================================
INSERT INTO legal_facts (categorie, titre, reference_legale, contenu, priority, source_url, date_maj, is_active)
VALUES (
  'marches',
  'Marchés publics - Seuils et procédures 2024',
  'Décret n° 2023-1303 du 29 décembre 2023 - Code de la commande publique',
  'SEUILS DE MARCHÉS PUBLICS 2024 (HT) : Moins de 40 000 € : Marché à procédure adaptée (MAPA) simplifié, pas de publicité obligatoire en dessous de 25 000 € (mais mise en concurrence recommandée). De 40 000 € à 214 000 € (fournitures/services) ou 5 382 000 € (travaux) : MAPA avec publicité et mise en concurrence obligatoire. Au-delà de ces seuils : Procédure formalisée (appel d\'offres ouvert ou restreint, procédure concurrentielle avec négociation). EXCEPTIONS : urgence impérieuse (art. R2122-1), marchés négociés sans publicité dans cas limitatifs. DÉMATÉRIALISATION : obligatoire via profil acheteur. DÉLAIS MINIMAUX : 11 jours (MAPA) à 35 jours (appel d\'offres).',
  10,
  'https://www.economie.gouv.fr/daj/marches-publics',
  CURDATE(),
  1
);

-- ============================================================================
-- 8. Publicité des actes - Délibérations
-- ============================================================================
INSERT INTO legal_facts (categorie, titre, reference_legale, contenu, priority, source_url, date_maj, is_active)
VALUES (
  'deliberation',
  'Publicité des actes - Délibérations du conseil municipal',
  'Articles L2121-24 et L2131-1 du CGCT',
  'Les délibérations sont rendues EXÉCUTOIRES après transmission au préfet ET accomplissement des mesures de publicité. AFFICHAGE : obligatoire dans les 8 jours de la délibération au lieu habituel (mairie, panneaux officiels). PROCÈS-VERBAL : établi par le secrétaire de séance, signé par lui et le maire, transcrit sur un registre coté et paraphé. COMMUNICABILITÉ : toute personne peut demander communication des délibérations (CADA). PUBLICATION EN LIGNE : obligatoire sur le site internet de la commune pour les communes de plus de 3500 habitants (art. L2121-26). TRANSMISSION DÉMATÉRIALISÉE : obligatoire au contrôle de légalité via @CTES.',
  10,
  'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006389878',
  CURDATE(),
  1
);

-- ============================================================================
-- 9. Budget - Vote et délais
-- ============================================================================
INSERT INTO legal_facts (categorie, titre, reference_legale, contenu, priority, source_url, date_maj, is_active)
VALUES (
  'budget',
  'Budget primitif - Vote et délais légaux',
  'Articles L1612-2 et L2312-1 du CGCT',
  'Le budget primitif doit être voté par l\'assemblée délibérante avant le 15 AVRIL de l\'exercice (ou avant le 30 avril de l\'année de renouvellement des assemblées). CONTENU OBLIGATOIRE : budget en équilibre réel (section de fonctionnement et section d\'investissement), présentation par chapitres, annexes obligatoires. COMPTE ADMINISTRATIF : voté avant le 30 JUIN N+1. PRÉSENTATION PAR NATURE et par FONCTION obligatoire. ÉQUILIBRE RÉEL : recettes et dépenses égales dans chaque section, dépenses obligatoires inscrites, pas de déficit reporté en fonctionnement. PUBLICITÉ : affichage obligatoire pendant 15 jours minimum après vote.',
  10,
  'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006390137',
  CURDATE(),
  1
);

-- ============================================================================
-- 10. Délégations du maire - Pouvoirs
-- ============================================================================
INSERT INTO legal_facts (categorie, titre, reference_legale, contenu, priority, source_url, date_maj, is_active)
VALUES (
  'cgct',
  'Délégations du maire - Attributions déléguées au maire',
  'Article L2122-22 du CGCT',
  'Le conseil municipal peut déléguer au maire, pour la durée de son mandat, certaines attributions. DOMAINES DÉLÉGABLES : arrêter l\'affectation des propriétés communales, fixer tarifs des droits de voirie/stationnement/concessions cimetières, décider de la réalisation d\'emprunts, prendre toute décision concernant la préparation/passation/exécution de marchés < seuils européens, décider aliénations de gré à gré de biens mobiliers < 4 600 €, décider contentieux (exercer actions en justice), accepter dons et legs, décider subventions < 5% du budget. COMPTE RENDU : le maire doit rendre compte au conseil municipal. NON DÉLÉGABLES : taux et tarifs fiscaux, approbation budget, création services publics, décisions modificatives budgétaires > 7,5%.',
  10,
  'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006389930',
  CURDATE(),
  1
);

-- ============================================================================
-- Vérification : Afficher les legal facts insérés
-- ============================================================================
SELECT
  id,
  categorie,
  titre,
  reference_legale,
  DATE_FORMAT(date_maj, '%d/%m/%Y') as date_maj
FROM legal_facts
WHERE is_active = 1
ORDER BY categorie, id;
