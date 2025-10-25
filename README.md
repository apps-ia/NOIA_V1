# NOIA - Intelligence Partagée du Service Public Local

Interface d'intelligence documentaire et réglementaire dédiée au service public local.

## 📋 Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Prérequis](#prérequis)
3. [Installation](#installation)
4. [Configuration Make.com](#configuration-makecom)
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

**Architecture :**
```
Interface Web (OVH) → API PHP (Proxy) → Make.com → OpenAI → Réponse structurée
                           ↓
                    Bases de données MySQL (OVH)
                    - Base centrale (commune)
                    - Base locale (par commune)
```

---

## ✅ Prérequis

### Hébergement OVH Perso
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Accès FTP/SSH
- Certificat SSL (recommandé)

### Services externes
- Compte Make.com (gratuit ou payant)
- Clé API OpenAI (GPT-4 recommandé)

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
define('DB_HOST', 'mysql47.perso.ovh.net');    // Votre serveur MySQL
define('DB_NAME', 'votre_base');                 // Nom de votre base
define('DB_USER', 'votre_utilisateur');          // Utilisateur MySQL
define('DB_PASS', 'votre_mot_de_passe');         // Mot de passe MySQL
define('MAKE_WEBHOOK', 'https://hook.eu1.make.com/xxxxx'); // Voir étape 5
```

**Sécurité :** Assurez-vous que le fichier config.php n'est pas accessible via le web (protégé par .htaccess).

---

## ⚙️ Configuration Make.com

### Étape 5 : Créer le scénario Make

1. Connectez-vous à [Make.com](https://www.make.com)
2. Créez un nouveau scénario
3. Ajoutez les modules suivants :

#### Module 1 : Webhook (déclencheur)
- Type : **Custom Webhook**
- Créez un nouveau webhook
- Copiez l'URL générée (format : `https://hook.eu1.make.com/xxxxx`)
- Collez cette URL dans `config.php` (variable `MAKE_WEBHOOK`)

#### Module 2 : OpenAI - Create a Completion
- Connexion : ajoutez votre clé API OpenAI
- Modèle : `gpt-4-turbo` ou `gpt-4o`
- Messages :
  ```
  System: Tu es NOIA, assistant spécialisé dans la réglementation des collectivités françaises.
  
  CONTEXTE :
  - Commune : {{1.commune}}
  - Question : {{1.question}}
  
  SOURCES DISPONIBLES :
  Base centrale (règles communes) : {{1.central_results}}
  Base locale (documents spécifiques) : {{1.local_results}}
  
  CONSIGNES :
  1. Structure ta réponse avec ces sections (format HTML) :
     <div class="structured-response">
       <div class="response-section">
         <div class="section-title">📚 Références juridiques</div>
         <div class="section-content">...</div>
       </div>
       <div class="response-section">
         <div class="section-title">🔍 Analyse réglementaire</div>
         <div class="section-content">...</div>
       </div>
       <div class="response-section">
         <div class="section-title">✅ Application pratique</div>
         <div class="section-content">...</div>
       </div>
       <div class="response-section">
         <div class="section-title">📄 Proposition d'acte</div>
         <div class="section-content">...</div>
       </div>
     </div>
  
  2. Cite systématiquement tes sources (Légifrance, CGCT, etc.)
  3. Si manque d'info locale, indique clairement "Aucune donnée spécifique trouvée pour cette commune"
  4. Utilise un ton professionnel mais accessible
  5. Maximum 500 mots pour la clarté
  
  User: {{1.question}}
  ```

#### Module 3 : HTTP - Make a Request (réponse)
- URL : `https://noia.votre-domaine.fr/noia/api/response_handler.php` (optionnel)
- Méthode : POST
- Body :
  ```json
  {
    "response": "{{2.choices[0].message.content}}",
    "commune": "{{1.commune}}"
  }
  ```

**Note :** Pour une version simplifiée, vous pouvez connecter directement le module OpenAI au webhook de sortie.

4. **Activez le scénario** (bouton ON en bas à gauche)

### Test du scénario

Dans Make, cliquez sur "Run once" et testez avec :
```json
{
  "question": "Comment imputer un broyeur en M57 ?",
  "commune": "commune_a",
  "central_results": [],
  "local_results": []
}
```

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

1. **Masquage du webhook Make** via proxy PHP
2. **Validation stricte** des entrées utilisateur
3. **Rate limiting** : 30 requêtes/heure par IP
4. **Protection SQL injection** : requêtes préparées (PDO)
5. **Headers de sécurité** : CSP, X-Frame-Options, etc.
6. **Sanitization HTML** : liste blanche de balises autorisées
7. **Protection .htaccess** : fichiers de config non accessibles

### Recommandations

✅ **À FAIRE :**
- Activer le HTTPS (certificat SSL Let's Encrypt gratuit sur OVH)
- Modifier les identifiants par défaut dans `config.php`
- Restreindre les permissions fichiers (755 pour dossiers, 644 pour fichiers)
- Surveiller les logs régulièrement

❌ **À NE PAS FAIRE :**
- Ne jamais exposer le webhook Make directement
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
3. Si erreur 500 : consultez les logs PHP d'OVH
4. Si erreur 404 : vérifiez le chemin du fichier `proxy.php`

**Testez Make.com :**
1. Allez sur Make.com → votre scénario
2. Vérifiez l'historique d'exécution
3. Testez manuellement le scénario

### Problème : Réponse incohérente de NOIA

**Solutions :**
1. Vérifiez la qualité des données dans vos tables SQL
2. Améliorez le prompt OpenAI dans Make
3. Augmentez les limites de recherche (actuellement 5 résultats)

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

**Version :** 1.0.0  
**Date :** Octobre 2024  
**Dernière mise à jour :** Ce README
