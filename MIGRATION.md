# 🔄 Guide de migration NOIA v1.0.0 → v2.0.0

**Migration de Make.com vers OpenAI Direct**

---

## 📋 Vue d'ensemble

Vous avez actuellement NOIA v1.0.0 installé et fonctionnel avec Make.com. Ce guide vous explique comment migrer vers la v2.0.0 qui utilise OpenAI directement, sans Make.com.

### Avantages de la migration

✅ **Simplicité** : Plus besoin de gérer un compte Make.com
✅ **Économies** : Suppression de l'abonnement Make.com (9-30€/mois)
✅ **Performance** : Latence réduite (un service en moins)
✅ **Contrôle** : Configuration fine du modèle OpenAI
✅ **Fiabilité** : Moins de points de défaillance

### Ce qui reste identique

✅ **Base de données** : Aucune modification nécessaire
✅ **Données** : Toutes vos règles et documents sont conservés
✅ **Domaine** : Votre URL reste la même
✅ **Interface** : Design modernisé mais fonctionnalités identiques

---

## ⏱️ Temps estimé : 15 minutes

---

## 🔑 Étape 1 : Obtenir une clé API OpenAI (5 min)

### A. Créer un compte OpenAI

1. Allez sur [OpenAI Platform](https://platform.openai.com/)
2. Créez un compte ou connectez-vous
3. Ajoutez un moyen de paiement (carte bancaire)
4. Configurez une limite de dépense (recommandé : 10-20€/mois pour commencer)

### B. Générer une clé API

1. Allez dans **API Keys** : https://platform.openai.com/api-keys
2. Cliquez sur **Create new secret key**
3. Donnez-lui un nom (ex: "NOIA Production")
4. **⚠️ IMPORTANT** : Copiez la clé immédiatement (commence par `sk-`)
   - Vous ne pourrez plus la voir après !
   - Format : `sk-proj-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`

### C. Configurer les limites (optionnel mais recommandé)

1. Allez dans **Settings > Limits**
2. Définissez un budget mensuel (ex: 20€)
3. Activez les alertes par email

💡 **Astuce** : Commencez avec GPT-3.5-turbo pour tester (moins cher), puis passez à GPT-4-turbo

---

## 📦 Étape 2 : Sauvegarder l'existant (2 min)

### Avant de modifier quoi que ce soit, faites une sauvegarde !

**Via FTP :**
```
Téléchargez ces dossiers en local :
- /www/noia/config/      (sauvegarde de config.php)
- /www/noia/logs/        (sauvegarde des logs)
```

**Via phpMyAdmin :**
```
1. Sélectionnez votre base de données
2. Onglet "Exporter"
3. Format SQL
4. Téléchargez le fichier .sql
```

💾 **Important** : Gardez ces sauvegardes, elles vous permettront de revenir en arrière si besoin.

---

## 🔄 Étape 3 : Mettre à jour les fichiers (5 min)

### Fichiers à REMPLACER (via FTP)

| Ancien fichier | Nouveau fichier | Action |
|----------------|-----------------|--------|
| `index.html` | ✅ Remplacer | Nouvelle interface modernisée |
| `style.css` | ✅ Remplacer | Nouveaux styles |
| `script.js` | ✅ Remplacer | Appel direct à proxy.php |
| `api/proxy.php` | ✅ Remplacer | **Nouveau : appel OpenAI direct** |

### Fichiers à CRÉER

| Nouveau fichier | Description |
|----------------|-------------|
| `.htaccess` | Protection améliorée (à la racine) |
| `.gitignore` | Protection des fichiers sensibles |
| `config/config.example.php` | Exemple de configuration |

### Fichiers à MODIFIER

| Fichier | Action |
|---------|--------|
| `config/config.php` | ⚠️ **À modifier** (voir étape 4) |

### Fichiers à CONSERVER (ne pas toucher !)

| Fichier | Raison |
|---------|--------|
| `database_init.sql` | ✅ Base de données inchangée |
| `logs/` | ✅ Historique à conserver |
| Base de données MySQL | ✅ **Aucune modification** |

---

## ⚙️ Étape 4 : Modifier config.php (3 min)

### AVANT (v1.0.0 avec Make.com)

```php
<?php
// Configuration de la base de données
define('DB_HOST', 'mysql47.perso.ovh.net');
define('DB_NAME', 'votre_base');
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', 'votre_mot_de_passe');

// Configuration Make.com
define('MAKE_WEBHOOK', 'https://hook.eu1.make.com/xxxxx');  // ← À SUPPRIMER

// Rate limiting
define('RATE_LIMIT_REQUESTS', 30);
define('RATE_LIMIT_PERIOD', 3600);
```

### APRÈS (v2.0.0 avec OpenAI)

```php
<?php
/**
 * NOIA - Configuration
 * Version: 2.0.0 (Direct OpenAI Integration)
 */

// Configuration de la base de données (IDENTIQUE)
define('DB_HOST', 'mysql47.perso.ovh.net');
define('DB_NAME', 'votre_base');
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', 'votre_mot_de_passe');

// Configuration OpenAI (NOUVEAU !)
define('OPENAI_API_KEY', 'sk-proj-xxxxx');     // ← Collez votre clé API
define('OPENAI_MODEL', 'gpt-4-turbo');          // ou 'gpt-4o', 'gpt-3.5-turbo'
define('OPENAI_MAX_TOKENS', 1500);
define('OPENAI_TEMPERATURE', 0.7);

// Configuration de sécurité (AMÉLIORÉ)
define('RATE_LIMIT_REQUESTS', 30);
define('RATE_LIMIT_PERIOD', 3600);
define('MAX_QUESTION_LENGTH', 500);
define('ENABLE_LOGGING', true);
define('LOG_FILE', __DIR__ . '/../logs/queries.log');
define('RATE_LIMIT_FILE', __DIR__ . '/../logs/rate_limit.json');

// Configuration CORS
define('ALLOWED_ORIGINS', '*');  // À restreindre en production

// Timezone
date_default_timezone_set('Europe/Paris');

// Mode debug (désactiver en production)
define('DEBUG_MODE', false);
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
```

### Actions à faire

1. **Ouvrez** votre `config/config.php` actuel
2. **Supprimez** la ligne `MAKE_WEBHOOK`
3. **Ajoutez** les lignes de configuration OpenAI (voir ci-dessus)
4. **Collez** votre clé API OpenAI dans `OPENAI_API_KEY`
5. **Sauvegardez** le fichier

💡 **Astuce** : Vous pouvez aussi copier `config.example.php` et le remplir avec vos valeurs.

---

## ✅ Étape 5 : Tester la migration (2 min)

### Test 1 : Vérifier que le site est accessible

1. Accédez à `https://noia.votre-domaine.fr/`
2. Vous devriez voir la nouvelle interface modernisée
3. Le message de bienvenue doit s'afficher

### Test 2 : Poser une question

1. Sélectionnez une commune (ou "Général")
2. Posez une question simple : *"Comment imputer un broyeur en M57 ?"*
3. Attendez 2-3 secondes
4. Vous devriez recevoir une réponse structurée

✅ **Si ça marche** : Migration réussie ! 🎉

❌ **Si ça ne marche pas** : Voir la section Dépannage ci-dessous

### Test 3 : Vérifier les logs

1. Consultez `/logs/queries.log` via FTP
2. Vous devriez voir une nouvelle ligne avec votre question
3. Format JSON avec temps de réponse

---

## 🧹 Étape 6 : Nettoyer l'ancien système (optionnel)

### A. Désactiver le scénario Make.com

1. Connectez-vous à [Make.com](https://www.make.com)
2. Allez dans votre scénario NOIA
3. **Désactivez-le** (bouton OFF)
4. Vous pouvez le supprimer après quelques jours de test

### B. Résilier l'abonnement Make.com (optionnel)

Si vous n'utilisez Make.com que pour NOIA :
1. Allez dans **Settings > Subscription**
2. Annulez votre abonnement
3. **Économie** : 9-30€/mois

### C. Supprimer les anciens fichiers (optionnel)

Aucun fichier spécifique à supprimer, mais vous pouvez :
- Archiver vos sauvegardes locales
- Nettoyer les vieux logs (si trop volumineux)

---

## 🆘 Dépannage

### Problème : "Erreur 500" après migration

**Solutions :**
1. Vérifiez que `config.php` est bien formaté (pas d'erreur PHP)
2. Vérifiez que tous les `define()` se terminent par `;`
3. Vérifiez que le dossier `/logs/` existe avec permission 755
4. Activez temporairement `DEBUG_MODE = true` dans config.php

### Problème : "Erreur OpenAI (401)"

**Cause :** Clé API invalide

**Solutions :**
1. Vérifiez que la clé commence par `sk-`
2. Vérifiez qu'il n'y a pas d'espace avant/après
3. Régénérez une nouvelle clé sur OpenAI Platform
4. Testez la clé sur https://platform.openai.com/playground

### Problème : "Erreur OpenAI (429)"

**Cause :** Limite de débit atteinte

**Solutions :**
1. Vérifiez vos limites sur https://platform.openai.com/account/limits
2. Attendez quelques minutes
3. Augmentez vos limites (compte payant)

### Problème : "Aucune réponse" ou timeout

**Solutions :**
1. Vérifiez que PHP a l'extension cURL activée
2. Consultez `/logs/queries.log` pour voir les erreurs
3. Testez avec `gpt-3.5-turbo` (plus rapide)
4. Augmentez le timeout dans proxy.php si nécessaire

### Problème : "Les anciennes conversations ont disparu"

**Normal :** NOIA ne stocke pas l'historique des conversations (RGPD).
Les logs sont dans `/logs/queries.log`.

### Revenir en arrière (rollback)

Si la migration ne fonctionne pas :

1. **Restaurez** les fichiers sauvegardés (étape 2)
2. **Remettez** `MAKE_WEBHOOK` dans config.php
3. **Réactivez** le scénario Make.com
4. Contactez le support pour analyse

---

## 📊 Comparaison des coûts

### AVANT (v1.0.0 avec Make.com)

| Service | Coût mensuel |
|---------|--------------|
| OVH Perso | 3-5€ |
| Make.com | 9-30€ |
| OpenAI (via Make) | 10-30€ |
| **TOTAL** | **22-65€/mois** |

### APRÈS (v2.0.0 sans Make.com)

| Service | Coût mensuel |
|---------|--------------|
| OVH Perso | 3-5€ |
| ~~Make.com~~ | ~~0€~~ ✅ |
| OpenAI (direct) | 10-30€ |
| **TOTAL** | **13-35€/mois** |

**💰 Économie : 9-30€/mois (jusqu'à 360€/an !)**

---

## 🔑 Choix du modèle OpenAI

Pour la migration, nous recommandons :

| Modèle | Usage | Prix/requête | Recommandation |
|--------|-------|--------------|----------------|
| **gpt-3.5-turbo** | Tests initiaux | 0,001-0,002€ | 🧪 Pour tester la migration |
| **gpt-4-turbo** | Production | 0,01-0,03€ | ✅ Même qualité qu'avant |
| **gpt-4o** | Production | 0,005-0,015€ | ✅ Plus rapide, moins cher |

💡 **Conseil** : Commencez avec `gpt-3.5-turbo` pour valider la migration, puis passez à `gpt-4-turbo` une fois satisfait.

---

## 📝 Checklist finale

Après la migration, vérifiez que :

- [ ] L'interface web s'affiche correctement
- [ ] Les questions reçoivent des réponses structurées
- [ ] Les logs sont créés dans `/logs/queries.log`
- [ ] Les sources (base centrale/locale) sont toujours consultées
- [ ] Le sélecteur de commune fonctionne
- [ ] Les réponses sont de qualité similaire à avant
- [ ] Le scénario Make.com est désactivé
- [ ] Vous surveillez vos coûts OpenAI sur https://platform.openai.com/usage

---

## 📞 Support

### Problèmes techniques
- **Documentation** : Consultez `README.md` mis à jour
- **Logs** : Consultez `/logs/queries.log`
- **OpenAI Status** : https://status.openai.com/

### Questions fréquentes

**Q : Dois-je migrer ma base de données ?**
R : Non, la base de données reste identique. Aucune modification nécessaire.

**Q : Puis-je garder Make.com en parallèle pendant les tests ?**
R : Oui, mais changez l'URL du webhook dans l'ancienne version pour éviter les conflits.

**Q : Quelle est la différence de qualité des réponses ?**
R : Identique si vous utilisez le même modèle (GPT-4-turbo). Vous avez même plus de contrôle maintenant.

**Q : Combien de temps garder la sauvegarde ?**
R : Au moins 1 mois après migration, le temps de valider la stabilité.

---

## 🎉 Migration terminée !

Félicitations ! Vous utilisez maintenant NOIA v2.0.0 avec OpenAI direct.

**Avantages obtenus :**
- ✅ Architecture simplifiée
- ✅ Coûts réduits
- ✅ Plus de contrôle
- ✅ Latence améliorée
- ✅ Moins de dépendances externes

**N'oubliez pas de :**
- Surveiller vos coûts OpenAI les premiers jours
- Configurer des alertes de budget
- Désactiver le scénario Make.com après validation

---

**Version du guide :** 2.0.0
**Date :** Octobre 2024
**Temps de migration :** ~15 minutes
**Compatibilité :** NOIA v1.0.0 → v2.0.0
