-- ============================================================================
-- NOIA v4.0 - Base de Faits Juridiques Validés
-- ============================================================================
-- Cette base de données juridique interne garantit la précision des réponses
-- sur les sujets critiques : quorum, FCTVA, IFSE, CGCT, M57, RH, etc.
--
-- PRIORITÉ : Ces faits juridiques PRIMENT sur toute autre source (RAG, web)
-- Source officielle : Légifrance + DGFiP + DGCL
-- ============================================================================

-- Vider la table avant insertion (si mise à jour)
TRUNCATE TABLE `legal_facts`;

-- ============================================================================
-- CATÉGORIE : QUORUM (Conseil Municipal)
-- ============================================================================

INSERT INTO `legal_facts` (`categorie`, `titre`, `reference_legale`, `contenu`, `priority`, `source_url`, `date_maj`, `is_active`) VALUES

('quorum', 'Quorum - Première convocation', 'Article L2121-17 du CGCT',
'Le conseil municipal ne délibère valablement que lorsque la MAJORITÉ ABSOLUE de ses membres en exercice est présente à la première convocation.

Calcul du quorum :
- Conseil de 11 membres : 6 présents minimum
- Conseil de 15 membres : 8 présents minimum
- Conseil de 19 membres : 10 présents minimum
- Conseil de 23 membres : 12 présents minimum
- Conseil de 29 membres : 15 présents minimum

Si le quorum n\'est pas atteint, la séance ne peut se tenir et une reconvocation est obligatoire.',
10, 'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006389873', CURDATE(), 1),

('quorum', 'Quorum - Reconvocation (aucun quorum requis)', 'Article L2121-17 du CGCT',
'Après une première convocation où le quorum n\'était pas atteint, le conseil est RECONVOQUÉ à au moins TROIS JOURS d\'intervalle.

IMPORTANT : Lors de cette reconvocation, le conseil délibère SANS CONDITION DE QUORUM, quel que soit le nombre de membres présents.

Conditions :
- Délai minimum : 3 jours francs entre les deux convocations
- Ordre du jour : identique ou restreint (pas d\'ajout de points)
- Validité : délibérations valables même avec 1 seul membre présent

Jurisprudence : CE, 15 octobre 2003, n°245481',
10, 'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006389873', CURDATE(), 1),

('quorum', 'Calcul des membres en exercice', 'Article L2121-17 du CGCT',
'Les membres en exercice sont les conseillers municipaux en fonction, à l\'exclusion de ceux dont le siège est devenu vacant (démission acceptée, décès, révocation).

Cas particuliers :
- Démission non encore acceptée : membre COMPTÉ
- Démission acceptée : membre NON COMPTÉ
- Suspension : membre NON COMPTÉ pendant la durée
- Absence : membre COMPTÉ (même absent à la séance)

Le quorum se calcule sur le nombre de membres en exercice AU JOUR DE LA SÉANCE.',
9, 'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006389873', CURDATE(), 1);

-- ============================================================================
-- CATÉGORIE : FCTVA (Fonds de Compensation TVA)
-- ============================================================================

INSERT INTO `legal_facts` (`categorie`, `titre`, `reference_legale`, `contenu`, `priority`, `source_url`, `date_maj`, `is_active`) VALUES

('fctva', 'FCTVA - Comptes d\'imputation investissement', 'Article L1615-1 CGCT + Instruction M57 DGFiP',
'Les dépenses d\'investissement éligibles au FCTVA s\'imputent sur les comptes de la classe 2 (immobilisations) :

BÂTIMENTS :
- Compte 2131 : Bâtiments publics (mairie, école, salle des fêtes)
- Compte 2132 : Bâtiments fonctionnels (ateliers municipaux, hangars)
- Compte 2135 : Installations générales, agencements (climatisation, alarme)

TRAVAUX EN COURS :
- Compte 2313 : Immobilisations corporelles en cours (chantiers non réceptionnés)

VOIRIE :
- Compte 2151 : Réseaux de voirie
- Compte 21534 : Matériel et outillage de voirie

FONCTIONNEMENT (EXCEPTIONNEL) :
- Compte 615221 : Entretien bâtiments publics (si grosses réparations éligibles)

Source : Instruction M57 mise à jour 2025 - DGFiP',
10, 'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006389404', CURDATE(), 1),

('fctva', 'FCTVA - Taux de compensation 2024', 'Arrêté du 30 janvier 2024 (NOR: IOMB2401791A)',
'Le taux de compensation de la FCTVA applicable aux dépenses éligibles est fixé à :

TAUX 2024 : 16,404 %

Ce taux s\'applique aux dépenses réelles d\'investissement éligibles inscrites au budget de la collectivité.

Calcul : Montant FCTVA = Dépense TTC × 16,404 %

Exemple :
- Travaux de réfection toiture : 100 000 € TTC
- FCTVA récupérable : 100 000 × 16,404 % = 16 404 €

Paiement : Versement l\'année suivante (N+1) sur déclaration.',
9, 'https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000049078307', '2024-01-30', 1),

('fctva', 'FCTVA - Conditions d\'éligibilité', 'Article L1615-1 CGCT + Circulaire du 17 février 2015',
'Dépenses ÉLIGIBLES au FCTVA :
✅ Dépenses d\'investissement réelles (hors opérations d\'ordre)
✅ Soumises à TVA (pas de TVA forfaitaire)
✅ Inscrites au budget principal ou annexe
✅ Payées l\'année de référence

Dépenses NON ÉLIGIBLES :
❌ Dépenses de fonctionnement (sauf exceptions)
❌ Dépenses non soumises à TVA
❌ Achats de terrains
❌ Subventions versées
❌ Opérations d\'ordre (amortissements, dotations)
❌ Travaux en régie directe (sauf matériaux achetés)

Cas particuliers :
- Travaux d\'entretien : éligibles si > 50% de la valeur du bien
- Matériel et mobilier : éligibles si immobilisés
- Études : éligibles si préalables à investissement réalisé',
9, 'https://www.collectivites-locales.gouv.fr/fctva', CURDATE(), 1);

-- ============================================================================
-- CATÉGORIE : DÉLIBÉRATIONS (Conseil Municipal)
-- ============================================================================

INSERT INTO `legal_facts` (`categorie`, `titre`, `reference_legale`, `contenu`, `priority`, `source_url`, `date_maj`, `is_active`) VALUES

('deliberation', 'Délai de convocation conseil municipal', 'Article L2121-11 du CGCT',
'Les membres du conseil municipal sont convoqués par le maire au moins CINQ JOURS FRANCS avant la réunion.

Calcul des jours francs :
- Ne compte ni le jour de la convocation, ni le jour de la séance
- Exemple : convocation lundi pour séance lundi suivant = 6 jours francs ✅

CAS D\'URGENCE :
Le délai peut être ABRÉGÉ sans être inférieur à UN JOUR FRANC.
- L\'urgence doit être motivée dans la convocation
- Exemple : catastrophe naturelle, nécessité impérieuse

Sanction : irrégularité annulable par le juge si vice de procédure établi.',
9, 'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006389869', CURDATE(), 1),

('deliberation', 'Majorité pour adoption d\'une délibération', 'Article L2121-20 du CGCT',
'Les délibérations sont prises à la MAJORITÉ ABSOLUE des suffrages exprimés.

RÈGLES DE VOTE :
- Majorité absolue = plus de la moitié des suffrages exprimés
- Suffrages exprimés = votes POUR + votes CONTRE (abstentions non comptées)
- En cas de partage : voix du MAIRE prépondérante

VOTES NULS :
- Bulletins blancs : NON comptés
- Votes nuls : NON comptés

Exemple :
- 15 membres présents
- 7 votes POUR, 5 CONTRE, 3 abstentions
- Suffrages exprimés : 7 + 5 = 12
- Majorité absolue : 12 / 2 + 1 = 7 ✅ ADOPTÉ

Vote à bulletin secret OBLIGATOIRE pour :
- Élection du maire et des adjoints
- Désignation des délégués au conseil communautaire (commune de + 1000 hab.)
- Présentation de candidats à certains organismes',
9, 'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006389878', CURDATE(), 1);

-- ============================================================================
-- CATÉGORIE : M57 (Nomenclature Comptable)
-- ============================================================================

INSERT INTO `legal_facts` (`categorie`, `titre`, `reference_legale`, `contenu`, `priority`, `source_url`, `date_maj`, `is_active`) VALUES

('m57', 'Compte 2131 - Bâtiments publics', 'Instruction M57 DGFiP 2025',
'COMPTE 2131 : Bâtiments publics

DÉFINITION :
Constructions édifiées sur sol propre ou sur sol d\'autrui, affectées à un usage public ou administratif.

INCLUS :
- Mairie, mairie annexe
- Écoles (maternelle, élémentaire)
- Salle des fêtes, salle polyvalente
- Bibliothèque, médiathèque
- Crèche, garderie
- Centre de loisirs
- Bâtiments administratifs

OPÉRATIONS :
- Construction neuve : compte 2131 (après transfert du 2313)
- Extension : compte 2131
- Réhabilitation lourde : compte 2131
- Grosses réparations : compte 2131 (si > 50% valeur)

ÉLIGIBILITÉ FCTVA : OUI (si respect conditions générales)

Subdivision possible :
- 21311 : Bâtiments affectés aux services généraux
- 21318 : Autres bâtiments publics',
9, 'https://www.collectivites-locales.gouv.fr/instruction-m57', CURDATE(), 1),

('m57', 'Compte 2313 - Immobilisations corporelles en cours', 'Instruction M57 DGFiP 2025',
'COMPTE 2313 : Immobilisations corporelles en cours

DÉFINITION :
Travaux d\'investissement non encore achevés à la clôture de l\'exercice.

UTILISATION :
- Chantiers en cours de réalisation
- Travaux non réceptionnés
- Immobilisations non mises en service

FONCTIONNEMENT :
1. Pendant le chantier : imputation compte 2313
2. À la réception des travaux : transfert vers compte définitif (2131, 2135, etc.)

OPÉRATION DE TRANSFERT (ordre) :
- Débit : Compte définitif (ex: 2131)
- Crédit : Compte 2313

ÉLIGIBILITÉ FCTVA : OUI (dépenses engagées sur 2313 éligibles)

IMPORTANT : Ne pas laisser de montants indéfiniment sur le 2313 après réception !',
9, 'https://www.collectivites-locales.gouv.fr/instruction-m57', CURDATE(), 1);

-- ============================================================================
-- CATÉGORIE : RH (Ressources Humaines FPT)
-- ============================================================================

INSERT INTO `legal_facts` (`categorie`, `titre`, `reference_legale`, `contenu`, `priority`, `source_url`, `date_maj`, `is_active`) VALUES

('rh', 'Calcul du traitement indiciaire', 'Décret n°87-1107 du 30 décembre 1987',
'FORMULE DE CALCUL DU TRAITEMENT BRUT INDICIAIRE :

Traitement brut = (Indice majoré × Valeur du point) / 100

VALEUR DU POINT AU 1ER JUILLET 2023 :
Valeur du point d\'indice = 4,92302 €

EXEMPLE :
Agent avec IM 350 (adjoint administratif)
Traitement mensuel brut = (350 × 4,92302) / 100 = 1 723,06 €

INDICES :
- Indice brut (IB) : indice de référence dans la grille
- Indice majoré (IM) : indice de calcul du traitement

Référence grilles : https://www.emploi-collectivites.fr/grille-indiciaire

NB : Le traitement ne comprend pas les primes et indemnités (IFSE, CIA, heures sup., etc.)',
9, 'https://www.legifrance.gouv.fr/loda/id/JORFTEXT000000699956/', CURDATE(), 1),

('rh', 'Temps de travail fonction publique territoriale', 'Décret n°2001-623 du 12 juillet 2001',
'DURÉE LÉGALE DU TRAVAIL :

TEMPS COMPLET : 1 607 heures par an (35h/semaine en moyenne)

CALCUL :
- 365 jours - 104 jours de repos hebdomadaire - 25 jours de congés annuels - 8 jours fériés en moyenne
- = 228 jours travaillés
- 228 jours × 7h = 1 596h (arrondi à 1 607h par décret)

ORGANISATION :
- 35h hebdomadaires en moyenne
- Possibilité de RTT si cycle > 35h
- Possibilité d\'annualisation

TEMPS PARTIEL :
- 50%, 60%, 70%, 80%, 90% du temps complet
- Droits à congés prorata temporis

HEURES SUPPLÉMENTAIRES :
- Au-delà de 35h/semaine ou 1 607h/an
- Contingent réglementaire : 25h/an (sauf dérogation)',
8, 'https://www.legifrance.gouv.fr/loda/id/JORFTEXT000000408220', CURDATE(), 1);

-- ============================================================================
-- CATÉGORIE : CGCT (Code Général des Collectivités Territoriales)
-- ============================================================================

INSERT INTO `legal_facts` (`categorie`, `titre`, `reference_legale`, `contenu`, `priority`, `source_url`, `date_maj`, `is_active`) VALUES

('cgct', 'Compétences du conseil municipal', 'Article L2121-29 du CGCT',
'Le conseil municipal règle par ses délibérations les affaires de la commune.

COMPÉTENCES EXCLUSIVES (non déléguables au maire) :
- Vote du budget et approbation du compte administratif
- Création et suppression de services publics communaux
- Adhésion à un EPCI
- Aliénation de biens de la commune
- Taux et tarifs des taxes et redevances

COMPÉTENCES DÉLÉGUABLES (article L2122-22) :
- Passation des marchés publics (sous seuils)
- Décisions relatives à la gestion du domaine
- Actions en justice
- Tarifs des services publics (sauf taxes)

SANCTION :
Délibération prise par le maire sur compétence exclusive du CM = NULLE',
9, 'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006389906', CURDATE(), 1),

('cgct', 'Pouvoirs de police du maire', 'Article L2212-2 du CGCT',
'Le maire est chargé, sous le contrôle administratif du représentant de l\'État, de la police municipale, de la police rurale et de l\'exécution des actes de l\'État qui y sont relatifs.

POUVOIRS DE POLICE GÉNÉRALE :
1. Sûreté publique (ordre, tranquillité, sécurité)
2. Salubrité publique (hygiène, santé)
3. Sécurité et commodité de passage (voirie, circulation)

MOYENS :
- Arrêtés de police (réglementation)
- Mesures d\'exécution d\'office
- Sanctions administratives (amendes, astreintes)

COMPÉTENCE TERRITORIALE :
- Sur le territoire de la commune
- Extension possible hors commune (article L2212-5)

CONTRÔLE :
- Légalité : par le préfet et le juge administratif
- Opportunité : non contrôlable (libre appréciation du maire)',
9, 'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006390153', CURDATE(), 1);

-- ============================================================================
-- CATÉGORIE : BUDGET
-- ============================================================================

INSERT INTO `legal_facts` (`categorie`, `titre`, `reference_legale`, `contenu`, `priority`, `source_url`, `date_maj`, `is_active`) VALUES

('budget', 'Vote du budget primitif - Délai', 'Article L1612-2 du CGCT',
'DÉLAI DE VOTE DU BUDGET PRIMITIF :

Le budget primitif de l\'exercice doit être voté AVANT LE 15 AVRIL de l\'année (ou avant le 30 avril l\'année de renouvellement des assemblées).

ANNÉE NORMALE : avant le 15 avril N
ANNÉE ÉLECTORALE : avant le 30 avril N

En cas de retard :
- Le préfet peut saisir la chambre régionale des comptes
- Inscription d\'office des dépenses obligatoires

Contenu minimal :
- Équilibre réel (recettes = dépenses en section)
- Sincérité des évaluations
- Autofinancement de l\'investissement (règle d\'or)',
8, 'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006390294', CURDATE(), 1);

-- ============================================================================
-- CATÉGORIE : MARCHÉS PUBLICS
-- ============================================================================

INSERT INTO `legal_facts` (`categorie`, `titre`, `reference_legale`, `contenu`, `priority`, `source_url`, `date_maj`, `is_active`) VALUES

('marches', 'Seuils des marchés publics 2024', 'Code de la commande publique (art. R2124-1)',
'SEUILS DE PROCÉDURE POUR LES MARCHÉS PUBLICS (2024) :

MARCHÉS DE FOURNITURES ET SERVICES :
- < 40 000 € HT : Marché sans formalités (achat libre)
- ≥ 40 000 € HT : Procédure adaptée (MAPA)
- ≥ 214 000 € HT : Procédure formalisée (appel d\'offres)

MARCHÉS DE TRAVAUX :
- < 40 000 € HT : Marché sans formalités
- ≥ 40 000 € HT : Procédure adaptée (MAPA)
- ≥ 5 382 000 € HT : Procédure formalisée (appel d\'offres)

IMPORTANTES PRÉCISIONS :
- Montants HT (TVA non comprise)
- Seuils calculés tous besoins confondus (globalisation)
- Fractionnement de marché = illégal si vise à éluder seuils

Source : Décret n°2023-1299 du 28 décembre 2023',
8, 'https://www.legifrance.gouv.fr/codes/id/LEGIARTI000044685484/', '2024-01-01', 1);

-- ============================================================================
-- CATÉGORIE : IFSE (Indemnité de Fonctions, Sujétions et Expertise)
-- ============================================================================

INSERT INTO `legal_facts` (`categorie`, `titre`, `reference_legale`, `contenu`, `priority`, `source_url`, `date_maj`, `is_active`) VALUES

('ifse', 'IFSE - Montants et critères', 'Décret n°2014-513 du 20 mai 2014',
'IFSE : Indemnité de Fonctions, de Sujétions et d\'Expertise

PRINCIPE :
L\'IFSE est modulable selon :
- Les fonctions exercées
- Les sujétions (contraintes du poste)
- L\'expertise (compétences techniques)

MONTANTS (plafonds annuels bruts) :
Catégorie A+ : jusqu\'à 46 170 €
Catégorie A : jusqu\'à 25 500 €
Catégorie B : jusqu\'à 15 480 €
Catégorie C : jusqu\'à 11 880 €

CRITÈRES DE MODULATION :
1. Fonctions d\'encadrement, de coordination, de pilotage
2. Technicité, expertise, expérience
3. Sujétions (astreintes, contraintes particulières)

RÈGLES :
- Montant individuel fixé par arrêté du maire
- Réexamen annuel obligatoire
- Versement mensuel
- Compatible avec autres primes (CIA, heures sup.)',
8, 'https://www.legifrance.gouv.fr/loda/id/JORFTEXT000028965302', CURDATE(), 1);

-- ============================================================================
-- VÉRIFICATION DE L\'INSERTION
-- ============================================================================

SELECT
  categorie,
  COUNT(*) as nombre_faits,
  AVG(priority) as priorite_moyenne
FROM legal_facts
GROUP BY categorie
ORDER BY categorie;

SELECT 'Base juridique initialisée avec succès !' AS status, COUNT(*) AS total_faits FROM legal_facts;
