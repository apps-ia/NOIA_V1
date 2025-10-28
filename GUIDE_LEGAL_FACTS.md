# 📚 Guide de Gestion des Legal Facts - NOIA v4.0

## 🎯 Qu'est-ce qu'un Legal Fact ?

Un **legal fact** est une règle juridique **validée et vérifiée** qui a une **priorité absolue (10/10)** sur toutes les autres sources d'information de NOIA.

**Quand NOIA répond à une question, il suit cet ordre de priorité :**

1. **🔴 Legal Facts (priorité 10/10)** ← PRIORITÉ ABSOLUE
2. Documents RAG uploadés (priorité 8/10)
3. Recherche web temps réel (priorité 5/10)
4. Connaissance générale OpenAI (priorité 3/10)

---

## 📊 Structure de la Table `legal_facts`

```sql
CREATE TABLE legal_facts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  categorie ENUM('quorum', 'fctva', 'ifse', 'cgct', 'm57', 'rh', 'marches', 'budget', 'deliberation', 'autre'),
  titre VARCHAR(255),          -- Titre court du legal fact
  reference_legale VARCHAR(255),-- Article de loi, décret, etc.
  contenu TEXT,                 -- Contenu détaillé de la règle
  priority INT DEFAULT 10,      -- Priorité (toujours 10 pour legal facts)
  source_url VARCHAR(500),      -- URL de la source officielle
  date_maj DATE,                -- Date de dernière mise à jour
  is_active TINYINT(1) DEFAULT 1 -- 1 = actif, 0 = désactivé
);
```

---

## 📝 Exemples de Legal Facts à Ajouter

### **1. Quorum - Reconvocation**

```sql
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
```

---

### **2. FCTVA - Dépenses éligibles**

```sql
INSERT INTO legal_facts (categorie, titre, reference_legale, contenu, priority, source_url, date_maj, is_active)
VALUES (
  'fctva',
  'FCTVA - Dépenses éligibles',
  'Article L1615-1 du CGCT',
  'Le Fonds de Compensation pour la TVA (FCTVA) compense la TVA acquittée sur les dépenses réelles d\'investissement des collectivités. Dépenses éligibles : travaux sur biens immeubles (constructions, voirie, réseaux), acquisitions immobilières, études, honoraires et frais liés aux investissements. Dépenses NON éligibles : fonctionnement, matériel mobilier, véhicules (sauf exceptions), subventions d\'équipement versées. Le taux de compensation est de 16,404% (2024). Déclaration annuelle obligatoire avant le 31 janvier N+2 via l\'application FCTVA en ligne.',
  10,
  'https://www.collectivites-locales.gouv.fr/fonds-de-compensation-pour-la-tva-fctva',
  CURDATE(),
  1
);
```

---

### **3. IFSE / RIFSEEP - Régime indemnitaire**

```sql
INSERT INTO legal_facts (categorie, titre, reference_legale, contenu, priority, source_url, date_maj, is_active)
VALUES (
  'ifse',
  'RIFSEEP - Régime indemnitaire fonction publique territoriale',
  'Décret n° 2014-513 du 20 mai 2014',
  'Le RIFSEEP (Régime Indemnitaire tenant compte des Fonctions, Sujétions, Expertise et Engagement Professionnel) se compose de 2 parts : 1) IFSE (Indemnité de Fonctions, de Sujétions et d\'Expertise) : part principale versée mensuellement, montant annuel déterminé par délibération selon critères professionnels. 2) CIA (Complément Indemnitaire Annuel) : facultatif, lié à l\'engagement professionnel et à la manière de servir. Plafonds définis par arrêtés ministériels selon cadres d\'emplois. Mise en place : délibération obligatoire précisant critères, montants et bénéficiaires. Compatible avec NBI (Nouvelle Bonification Indiciaire).',
  10,
  'https://www.legifrance.gouv.fr/loda/id/JORFTEXT000028965448/',
  CURDATE(),
  1
);
```

---

### **4. M57 - Nomenclature comptable**

```sql
INSERT INTO legal_facts (categorie, titre, reference_legale, contenu, priority, source_url, date_maj, is_active)
VALUES (
  'm57',
  'M57 - Instruction comptable et budgétaire (Nomenclature)',
  'Arrêté du 20 décembre 2018 - Instruction budgétaire et comptable M57',
  'L\'instruction M57 est le cadre comptable de référence pour les collectivités territoriales et leurs établissements publics. Elle se compose de : Section de fonctionnement (comptes 6 et 7) : charges et produits de l\'exercice. Section d\'investissement (comptes 1, 2, 4) : opérations affectant le patrimoine. Structure : Chapitre > Article > Compte. Exemples : 011 (charges à caractère général), 012 (charges de personnel), 65 (autres charges de gestion), 70 (produits des services), 21 (immobilisations corporelles), 23 (immobilisations en cours). Obligatoire pour toutes les collectivités.',
  10,
  'https://www.collectivites-locales.gouv.fr/finances-locales/instruction-budgetaire-et-comptable-m57',
  CURDATE(),
  1
);
```

---

### **5. Convocation conseil municipal - Délais**

```sql
INSERT INTO legal_facts (categorie, titre, reference_legale, contenu, priority, source_url, date_maj, is_active)
VALUES (
  'deliberation',
  'Convocation conseil municipal - Délais légaux',
  'Article L2121-11 du CGCT',
  'Le conseil municipal est convoqué par le maire. Délais de convocation : 5 jours francs minimum avant la séance (délai normal). 3 jours francs en cas d\'urgence (décision motivée du maire). 1 jour franc pour la première réunion suivant l\'élection du maire. Les jours francs excluent le jour de l\'acte de convocation et celui de la réunion. La convocation doit mentionner l\'ordre du jour, arrêté par le maire (art. L2121-10). Elle est accompagnée d\'une note explicative de synthèse pour chaque question. Transmission simultanée au préfet (dématérialisation obligatoire).',
  10,
  'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006389858',
  CURDATE(),
  1
);
```

---

### **6. Marchés publics - Seuils 2024**

```sql
INSERT INTO legal_facts (categorie, titre, reference_legale, contenu, priority, source_url, date_maj, is_active)
VALUES (
  'marches',
  'Marchés publics - Seuils et procédures 2024',
  'Décret n° 2023-1303 du 29 décembre 2023',
  'Seuils de marchés publics 2024 (HT) : Moins de 40 000 € : Marché à procédure adaptée (MAPA) simplifié, pas de publicité obligatoire (sauf > 25 000 €). De 40 000 € à 214 000 € (fournitures/services) ou 5 382 000 € (travaux) : MAPA avec publicité et mise en concurrence. Au-delà : Procédure formalisée (appel d\'offres ouvert/restreint). Exceptions : urgence impérieuse, marché négocié sans publicité. Dématérialisation obligatoire via profil acheteur. Délai minimal : 11 jours (MAPA) à 35 jours (appel d\'offres).',
  10,
  'https://www.economie.gouv.fr/daj/marches-publics',
  CURDATE(),
  1
);
```

---

## 🔧 Méthodes de Gestion des Legal Facts

### **MÉTHODE 1 : Via phpMyAdmin (Recommandé)**

1. **Accéder à phpMyAdmin** :
   - cPanel → phpMyAdmin
   - Sélectionner la base de données NOIA

2. **Aller dans la table `legal_facts`**

3. **Ajouter un legal fact** :
   - Cliquer sur l'onglet "Insérer"
   - Remplir les champs
   - Cliquer sur "Exécuter"

4. **Modifier un legal fact** :
   - Onglet "Parcourir"
   - Cliquer sur l'icône "Modifier" (crayon)
   - Modifier les champs
   - "Exécuter"

5. **Désactiver (pas supprimer) un legal fact** :
   - Modifier le champ `is_active` → mettre à `0`

---

### **MÉTHODE 2 : Via SQL Direct (cPanel)**

**Ajouter un legal fact :**

```sql
INSERT INTO legal_facts (categorie, titre, reference_legale, contenu, priority, source_url, date_maj, is_active)
VALUES (
  'cgct',
  'Publicité des actes - Délibérations',
  'Article L2121-24 du CGCT',
  'Les délibérations sont rendues exécutoires après transmission au préfet et affichage. L\'affichage doit être effectué dans les 8 jours de la délibération. Le procès-verbal de la séance est établi par le secrétaire de séance, signé par lui et le maire. Il est transcrit sur un registre coté et paraphé. Communicabilité : toute personne peut demander communication des délibérations (CADA). Publication obligatoire sur le site internet pour les communes de plus de 3500 habitants.',
  10,
  'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006389878',
  CURDATE(),
  1
);
```

**Modifier un legal fact :**

```sql
UPDATE legal_facts
SET
  contenu = 'Nouveau contenu mis à jour...',
  date_maj = CURDATE()
WHERE id = 5;
```

**Désactiver un legal fact :**

```sql
UPDATE legal_facts
SET is_active = 0
WHERE id = 5;
```

**Lister tous les legal facts actifs :**

```sql
SELECT id, categorie, titre, reference_legale, date_maj
FROM legal_facts
WHERE is_active = 1
ORDER BY categorie, priority DESC;
```

---

### **MÉTHODE 3 : Interface Web Simple (À venir)**

Je peux créer une interface web admin pour gérer facilement les legal facts sans SQL. Voulez-vous que je la crée ?

---

## 📋 Checklist : Quand Ajouter un Legal Fact ?

**✅ Ajouter un legal fact si :**

- ✅ La règle est **officielle** (loi, décret, arrêté, CGCT, instruction)
- ✅ La règle est **fréquemment demandée** par les utilisateurs
- ✅ La règle a des **conséquences importantes** si mal appliquée
- ✅ La règle est **précise et non ambiguë** (chiffres, délais, procédures)
- ✅ Vous avez une **source officielle vérifiable** (Légifrance, collectivites-locales.gouv.fr)

**❌ Ne pas ajouter de legal fact si :**

- ❌ C'est une **interprétation** ou un avis (pas une règle formelle)
- ❌ La règle est **trop générale** ou **contextuelle**
- ❌ La règle **change fréquemment** (dans ce cas, ajouter la date et prévoir une MAJ annuelle)
- ❌ C'est une **bonne pratique** (mais pas une obligation légale)

---

## 🔄 Maintenance des Legal Facts

### **Fréquence recommandée :**

- **Mensuel** : Vérifier les nouvelles lois et décrets importants
- **Trimestriel** : Mettre à jour les seuils et montants (ex: seuils marchés publics)
- **Annuel** : Révision complète de tous les legal facts

### **Sources à surveiller :**

1. **Légifrance** : https://www.legifrance.gouv.fr/
2. **Collectivités locales** : https://www.collectivites-locales.gouv.fr/
3. **AMF (Association des Maires de France)** : https://www.amf.asso.fr/
4. **DGCL (Direction Générale des Collectivités Locales)**
5. **Bulletins officiels** des ministères

### **Workflow de mise à jour :**

1. **Identifier** un changement de législation
2. **Vérifier** la source officielle
3. **Mettre à jour** le legal fact existant (ou en créer un nouveau)
4. **Modifier** `date_maj` à la date du jour
5. **Tester** avec NOIA pour vérifier que la réponse est correcte

---

## 🧪 Tester l'Impact d'un Legal Fact

**Après avoir ajouté un legal fact, testez-le :**

1. **Poser une question** qui devrait trigger ce legal fact
2. **Vérifier** que NOIA cite la référence légale exacte
3. **Vérifier** que le contenu du legal fact est utilisé dans la réponse

**Exemple :**

**Legal fact ajouté :** Quorum - Reconvocation

**Question test :**
```
Quel est le quorum pour une reconvocation du conseil municipal ?
```

**Réponse attendue de NOIA :**
- ✅ Cite "Article L2121-17 du CGCT"
- ✅ Mentionne "AUCUN quorum requis"
- ✅ Explique la différence 1ère convocation vs reconvocation

---

## 📊 Statistiques Utiles

**Nombre de legal facts par catégorie :**

```sql
SELECT categorie, COUNT(*) as nombre
FROM legal_facts
WHERE is_active = 1
GROUP BY categorie
ORDER BY nombre DESC;
```

**Legal facts les plus anciens (à mettre à jour) :**

```sql
SELECT id, titre, reference_legale, date_maj, DATEDIFF(CURDATE(), date_maj) as jours_depuis_maj
FROM legal_facts
WHERE is_active = 1
ORDER BY date_maj ASC
LIMIT 10;
```

---

## 🎯 Recommandations pour Votre NOIA

### **Legal Facts prioritaires à ajouter :**

1. ✅ **Quorum reconvocation** (déjà prévu ci-dessus)
2. ✅ **FCTVA dépenses éligibles** (déjà prévu)
3. ✅ **IFSE/RIFSEEP** (déjà prévu)
4. ✅ **M57 nomenclature** (déjà prévu)
5. ✅ **Convocation CM - délais** (déjà prévu)
6. ✅ **Marchés publics - seuils** (déjà prévu)
7. ⚠️ **Publicité des actes** (exemple fourni)
8. ⚠️ **Budget - vote et adoption** (à créer si besoin)
9. ⚠️ **Élections municipales - règles de base** (si demandes fréquentes)
10. ⚠️ **Délégations du maire** (Article L2122-22 CGCT)

---

## ✅ Actions Immédiates

**Pour bien démarrer avec les legal facts :**

1. **Copier les 6 exemples SQL** fournis ci-dessus
2. **Aller sur cPanel** → phpMyAdmin → legal_facts
3. **Onglet SQL**
4. **Coller et exécuter** les 6 INSERT INTO (un par un ou tous ensemble)
5. **Tester NOIA** avec les questions de test

**Voulez-vous que je :**
- **Option A** : Crée un fichier SQL prêt à l'emploi avec tous les INSERT ?
- **Option B** : Crée une interface web admin pour gérer les legal facts sans SQL ?
- **Option C** : Ajoute d'autres exemples de legal facts pour d'autres domaines ?

Dites-moi ce qui vous serait le plus utile ! 🚀
