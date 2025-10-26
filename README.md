# NOIA - Intelligence Partagée du Service Public Local

Interface d'intelligence documentaire et réglementaire dédiée au service public local.

**Version 2.0.0 - Intégration OpenAI directe (Make.com supprimé)**

---

## 🔄 Mise à jour depuis v1.0.0 ?

**⚠️ Vous avez déjà NOIA v1.0.0 installé avec Make.com ?**

👉 **Consultez le [Guide de migration v1.0.0 → v2.0.0](MIGRATION.md)**

Ce guide vous explique :
- Quels fichiers remplacer
- Comment obtenir une clé OpenAI
- Comment modifier config.php
- Comment tester la migration
- Comment désactiver Make.com

⏱️ **Temps de migration : 15 minutes**

---

## 📋 Table des matières

**Pour une nouvelle installation** :

1. [Vue d'ensemble](#vue-densemble)
2. [Prérequis](#prérequis)
3. [Installation](#installation)
4. [Configuration OpenAI](#configuration-openai)
5. [Structure du projet](#structure-du-projet)
6. [Sécurité](#sécurité)
7. [Maintenance](#maintenance)
8. [Dépannage](#dépannage)

---

## 🎯 Vue d'ensemble

NOIA est un assistant intelligent qui aide les agents territoriaux à :
- Comprendre et appliquer la réglementation (M57, RH, juridique, urbanisme)
- Accéder rapidement à des références officielles (Légifrance, DGCL, etc.)
- Obtenir des réponses contextualisées basées sur des sources fiables

**Architecture simplifiée (v2.0.0) :**
```
Interface Web (OVH) → API PHP (Proxy) → OpenAI API → Réponse structurée
                           ↓
                    Bases de données MySQL (OVH)
                    - Base centrale (commune)
                    - Base locale (par commune)
```

**Nouveauté v2.0.0 :** Make.com a été supprimé ! L'API OpenAI est maintenant appelée directement depuis PHP, ce qui simplifie l'architecture et réduit les coûts.

---

## ✅ Prérequis

### Hébergement OVH Perso
- PHP 7.4 ou supérieur avec extension cURL
- MySQL 5.7 ou supérieur
- Accès FTP/SSH
- Certificat SSL (recommandé)

### Services externes
- Clé API OpenAI (GPT-4 ou GPT-3.5-turbo recommandé)

---

## 🚀 Installation

### Étape 1 : Configuration du domaine OVH

1. Connectez-vous à votre espace client OVH
2. Allez dans **Hébergement > Multisite**
3. Créez un sous-domaine : `noia.votre-domaine.fr`
4. Notez le dossier cible (ex: `/www/noia/`)

### Étape 2 : Upload des fichiers

**Via FTP (FileZilla, WinSCP, etc.) :**

1. Connectez-vous à votre FTP OVH
2. Créez la structure suivante dans `/www/noia/` :

```
/www/noia/
├── index.html
├── style.css
├── script.js
├── .htaccess
├── /api/
│   └── proxy.php
├── /config/
│   └── config.php
├── /logs/           (créer le dossier, vide)
└── /assets/         (optionnel, pour images)
```

3. Uploadez tous les fichiers dans leurs dossiers respectifs

4. **Important :** Définir les permissions :
   - Dossier `/logs/` : `755` (lecture/écriture)
   - Fichiers PHP : `644`
   - `.htaccess` : `644`

### Étape 3 : Configuration de la base de données

1. Dans l'espace OVH, allez dans **Bases de données**
2. Créez ou utilisez une base existante
3. Notez les informations :
   - Nom de la base
   - Utilisateur
   - Mot de passe
   - Serveur (ex: `mysqlXX.ovh.net`)

4. Accédez à **phpMyAdmin**
5. Importez le fichier `database_init.sql`
6. Vérifiez que les tables sont créées :
   - `base_centrale`
   - `base_locale`

### Étape 4 : Configuration PHP

Modifiez le fichier `/config/config.php` avec vos informations :

```php
// Configuration de la base de données
define('DB_HOST', 'mysql47.perso.ovh.net');    // Votre serveur MySQL
define('DB_NAME', 'votre_base');                 // Nom de votre base
define('DB_USER', 'votre_utilisateur');          // Utilisateur MySQL
define('DB_PASS', 'votre_mot_de_passe');         // Mot de passe MySQL

// Configuration OpenAI (NOUVEAU - Plus besoin de Make.com!)
define('OPENAI_API_KEY', 'sk-votre-cle-api-openai');  // Votre clé API OpenAI
define('OPENAI_MODEL', 'gpt-4-turbo');                 // ou 'gpt-4o', 'gpt-3.5-turbo'
define('OPENAI_MAX_TOKENS', 1500);                     // Limite de tokens
define('OPENAI_TEMPERATURE', 0.7);                     // 0-2 (plus bas = plus déterministe)
```

**Sécurité :** Le fichier config.php est protégé par .htaccess et n'est pas accessible via le web.

---

## ⚙️ Configuration OpenAI

### Étape 5 : Obtenir votre clé API OpenAI

1. Créez un compte sur [OpenAI Platform](https://platform.openai.com/)
2. Allez dans **API Keys** : https://platform.openai.com/api-keys
3. Cliquez sur **Create new secret key**
4. Copiez la clé (format : `sk-...`)
5. Collez-la dans `config.php` (variable `OPENAI_API_KEY`)

**Important :**
- La clé API est confidentielle, ne la partagez jamais
- Configurez des limites de dépense sur OpenAI Platform
- Surveillez votre usage sur : https://platform.openai.com/usage

### Choix du modèle

| Modèle | Prix (approx.) | Qualité | Vitesse | Recommandation |
|--------|---------------|---------|---------|----------------|
| **gpt-4-turbo** | 0,01-0,03€/requête | Excellente | Moyenne | Production |
| **gpt-4o** | 0,005-0,015€/requête | Excellente | Rapide | Production |
| **gpt-3.5-turbo** | 0,001-0,002€/requête | Bonne | Très rapide | Tests/Dev |

### Test de la configuration

Accédez à : `https://noia.votre-domaine.fr/`

Posez une question test : *"Comment imputer un broyeur en M57 ?"*

Si tout fonctionne, vous devriez recevoir une réponse structurée en 2-3 secondes !

---

## 📁 Structure du projet

```
noia/
├── index.html              # Interface utilisateur
├── style.css               # Styles CSS (design moderne)
├── script.js               # Logique frontend (appels API, UI)
├── .htaccess               # Sécurité Apache
├── database_init.sql       # Script d'initialisation BDD
│
├── /api/
│   └── proxy.php           # Point d'entrée API (masque Make webhook)
│
├── /config/
│   └── config.php          # Configuration sensible (DB, webhooks)
│
├── /logs/                  # Logs applicatifs (créé automatiquement)
│   ├── queries.log         # Historique des requêtes
│   └── rate_limit.json     # Rate limiting par IP
│
└── /assets/                # Ressources statiques (optionnel)
    └── /images/
```

---

## 🔒 Sécurité

### Mesures implémentées

1. **Protection clé API OpenAI** : stockée dans config.php, protégé par .htaccess
2. **Validation stricte** des entrées utilisateur
3. **Rate limiting** : 30 requêtes/heure par IP (configurable)
4. **Protection SQL injection** : requêtes préparées (PDO)
5. **Headers de sécurité** : CSP, X-Frame-Options, X-XSS-Protection, etc.
6. **Sanitization HTML** : liste blanche de balises autorisées
7. **Protection .htaccess** : fichiers de config et logs non accessibles
8. **Timeout API** : limite de 30 secondes pour les appels OpenAI

### Recommandations

✅ **À FAIRE :**
- Activer le HTTPS (certificat SSL Let's Encrypt gratuit sur OVH)
- Modifier les identifiants par défaut dans `config.php`
- Configurer des limites de dépense sur OpenAI Platform
- Restreindre les permissions fichiers (755 pour dossiers, 644 pour fichiers)
- Surveiller les logs régulièrement
- Désactiver DEBUG_MODE en production

❌ **À NE PAS FAIRE :**
- Ne jamais exposer votre clé API OpenAI publiquement
- Ne jamais commiter config.php avec de vraies clés dans Git
- Ne pas stocker de données personnelles sensibles
- Ne pas désactiver les validations de sécurité

---

## 🔧 Maintenance

### Mise à jour des données

**Ajouter une règle à la base centrale :**
```sql
INSERT INTO base_centrale (titre, contenu, categorie, source, reference) 
VALUES (
  'Titre de la règle',
  'Contenu détaillé...',
  'Finances',
  'Légifrance',
  'Article L123-4'
);
```

**Ajouter un document local :**
```sql
INSERT INTO base_locale (commune, titre, contenu, categorie, date_document)
VALUES (
  'commune_a',
  'Délibération 2024-15',
  'Contenu de la délibération...',
  'Délibération',
  '2024-06-15'
);
```

### Consultation des logs

**Via FTP :**
- Téléchargez `/logs/queries.log`
- Format : JSON (une ligne par requête)

**Analyse :**
```bash
# Nombre de requêtes par jour
grep "2024-10-22" queries.log | wc -l

# Temps de réponse moyen
grep "response_time" queries.log | awk -F'"' '{sum+=$8; count++} END {print sum/count}'
```

### Sauvegarde

**Base de données :**
1. phpMyAdmin → Exporter → Format SQL
2. Planifier des exports automatiques (cron OVH si disponible)

**Fichiers :**
- Sauvegardez régulièrement via FTP
- Utilisez Git pour versionner le code

---

## 🐛 Dépannage

### Problème : "Erreur de connexion à la base de données"

**Solutions :**
1. Vérifiez les identifiants dans `config.php`
2. Testez la connexion MySQL via phpMyAdmin
3. Vérifiez que le serveur MySQL est actif (espace OVH)

### Problème : "Trop de requêtes. Veuillez patienter."

**Cause :** Rate limiting activé (30 req/h)

**Solutions :**
1. Attendez 1 heure
2. Ou augmentez la limite dans `config.php` :
   ```php
   define('RATE_LIMIT_REQUESTS', 60); // 60 au lieu de 30
   ```

### Problème : Pas de réponse de NOIA

**Diagnostic :**
1. Ouvrez la console développeur (F12) → onglet Network
2. Vérifiez si l'appel à `/api/proxy.php` réussit (code 200)
3. Si erreur 500 : consultez les logs PHP d'OVH ou activez DEBUG_MODE temporairement
4. Si erreur 404 : vérifiez le chemin du fichier `proxy.php`

**Vérifiez la clé OpenAI :**
1. Testez votre clé API sur : https://platform.openai.com/playground
2. Vérifiez que vous avez du crédit disponible
3. Vérifiez que la clé est correctement copiée dans config.php (commence par `sk-`)

**Vérifiez les logs :**
- Consultez `/logs/queries.log` pour voir les erreurs

### Problème : Erreur OpenAI (HTTP 401)

**Cause :** Clé API invalide ou expirée

**Solutions :**
1. Vérifiez que OPENAI_API_KEY dans config.php est correct
2. Générez une nouvelle clé sur https://platform.openai.com/api-keys
3. Vérifiez que votre compte OpenAI a du crédit

### Problème : Erreur OpenAI (HTTP 429)

**Cause :** Limite de débit OpenAI atteinte

**Solutions :**
1. Attendez quelques minutes
2. Vérifiez vos limites sur https://platform.openai.com/account/limits
3. Passez à un plan payant si nécessaire

### Problème : Réponse incohérente de NOIA

**Solutions :**
1. Vérifiez la qualité des données dans vos tables SQL
2. Ajustez OPENAI_TEMPERATURE dans config.php (valeur plus basse = plus déterministe)
3. Augmentez OPENAI_MAX_TOKENS si les réponses sont tronquées
4. Améliorez le prompt système dans proxy.php (ligne 158)
5. Augmentez les limites de recherche SQL (actuellement 5 résultats par base)

---

## 📞 Support

### Ressources

- [Documentation OVH](https://docs.ovh.com/)
- [Documentation Make.com](https://www.make.com/en/help)
- [Documentation OpenAI API](https://platform.openai.com/docs)

### Contact

Pour toute question technique, consultez :
- Forum OVH : https://community.ovh.com/
- Forum Make : https://community.make.com/

---

## 📜 Conformité RGPD

NOIA est conçu en conformité RGPD :
- ❌ Aucune donnée personnelle stockée
- ❌ Aucune conversation conservée après session
- ❌ Aucun tracking ou profilage
- ✅ Logs anonymisés (IP hachée si nécessaire)
- ✅ Données réglementaires publiques uniquement

**Note :** Si vous connectez NOIA à un système métier avec données RH/financières réelles, une analyse RGPD approfondie sera nécessaire.

---

## 📝 Licence

Projet NOIA - Usage interne collectivités territoriales

---

**Version :** 2.0.0
**Date :** Octobre 2024
**Dernière mise à jour :** Migration vers intégration OpenAI directe (Make.com supprimé)

## 📝 Changelog v2.0.0

### Nouveautés
- ✅ **Intégration OpenAI directe** : Plus besoin de Make.com !
- ✅ **Architecture simplifiée** : Moins de services externes = moins de points de défaillance
- ✅ **Coûts réduits** : Suppression de l'abonnement Make.com
- ✅ **Meilleure performance** : Réduction de la latence (un service en moins dans la chaîne)
- ✅ **Plus de contrôle** : Configuration fine du modèle OpenAI (température, max_tokens, etc.)
- ✅ **Logs améliorés** : Meilleure traçabilité des requêtes
- ✅ **Code open** : Tout le code est maintenant dans le repository

### Migration depuis v1.0.0
Si vous utilisez la version 1.0.0 avec Make.com :
1. Téléchargez les nouveaux fichiers (config.php, proxy.php, script.js)
2. Obtenez une clé API OpenAI
3. Mettez à jour config.php avec vos identifiants
4. Supprimez le scénario Make.com (optionnel)
5. Testez la nouvelle version

**Note :** La base de données reste identique, aucune migration SQL nécessaire.
