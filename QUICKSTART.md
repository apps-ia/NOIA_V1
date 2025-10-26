# 🚀 Guide de démarrage rapide NOIA

**Version 2.0.0 - Intégration OpenAI directe**

---

## 🔄 Mise à jour ou nouvelle installation ?

### ⚠️ Vous avez déjà NOIA v1.0.0 installé ?

👉 **Ce guide est pour une NOUVELLE installation**

**Pour migrer depuis v1.0.0 → v2.0.0** : Consultez le [Guide de migration](MIGRATION.md)

---

## ⏱️ Nouvelle installation (20 min)

### ☐ 1. Préparer OVH (5 min)

1. Créez un sous-domaine : `noia.votre-domaine.fr`
2. Notez vos identifiants MySQL (espace client OVH > Bases de données)
3. Activez le SSL si disponible (Let's Encrypt gratuit)

### ☐ 2. Uploader les fichiers (5 min)

Via FTP (FileZilla, WinSCP, etc.) :

```
/www/noia/
├── index.html
├── style.css
├── script.js
├── .htaccess
├── database_init.sql
├── /api/
│   └── proxy.php
├── /config/
│   └── config.php (à créer à partir de config.example.php)
└── /logs/           (créer le dossier, vide)
```

**Permissions :**
- Créer dossier `/logs/` avec permission `755`
- Tous les fichiers PHP : `644`

### ☐ 3. Configurer la base de données (5 min)

1. Ouvrez **phpMyAdmin** (depuis l'espace OVH)
2. Sélectionnez votre base de données
3. Cliquez sur **Importer**
4. Uploadez le fichier `database_init.sql`
5. Cliquez sur **Exécuter**

✅ Vous devriez voir :
- Table `base_centrale` avec plusieurs enregistrements
- Table `base_locale` avec des exemples

### ☐ 4. Obtenir une clé API OpenAI (3 min)

1. Créez un compte sur [OpenAI Platform](https://platform.openai.com/)
2. Allez dans **API Keys** : https://platform.openai.com/api-keys
3. Cliquez sur **Create new secret key**
4. **Copiez la clé** (format : `sk-...`) - vous n'aurez qu'une seule chance !
5. Configurez des limites de dépense si nécessaire

💡 **Astuce :** Commencez avec un budget de 5-10€ pour tester

### ☐ 5. Configurer config.php (3 min)

Copiez `/config/config.example.php` vers `/config/config.php` et éditez :

```php
// Configuration de la base de données
define('DB_HOST', 'mysql47.perso.ovh.net');  // ← Votre serveur MySQL
define('DB_NAME', 'ma_base_noia');            // ← Nom de votre base
define('DB_USER', 'mon_utilisateur');         // ← Votre utilisateur
define('DB_PASS', 'mon_mot_de_passe');        // ← Votre mot de passe

// Configuration OpenAI (NOUVEAU!)
define('OPENAI_API_KEY', 'sk-proj-xxxxx');    // ← Collez votre clé API
define('OPENAI_MODEL', 'gpt-4-turbo');        // ← Modèle recommandé
define('OPENAI_MAX_TOKENS', 1500);
define('OPENAI_TEMPERATURE', 0.7);
```

**⚠️ Important :** Ne commitez JAMAIS config.php avec la vraie clé API dans Git !

### ☐ 6. Test final (2 min)

1. Accédez à `https://noia.votre-domaine.fr/`
2. Posez une question : *"Comment imputer un broyeur en M57 ?"*
3. Attendez 2-3 secondes
4. Vous devriez recevoir une réponse structurée !

✅ **Si ça marche :** Félicitations ! NOIA est opérationnel

❌ **Si ça ne marche pas :** Voir la section Dépannage ci-dessous

---

## 🎯 Personnalisation rapide

### Ajouter une commune

```sql
-- Via phpMyAdmin
INSERT INTO base_locale (commune, titre, contenu, categorie)
VALUES (
  'ma_commune',
  'Délibération 2024-01',
  'Contenu de votre délibération...',
  'Délibération'
);
```

Puis dans `index.html`, ajoutez l'option :

```html
<select id="commune">
  <option value="general">Général</option>
  <option value="ma_commune">Ma commune</option>
</select>
```

### Ajouter des règles générales

```sql
INSERT INTO base_centrale (titre, contenu, categorie, source)
VALUES (
  'Titre de la règle',
  'Contenu détaillé avec références...',
  'RH',
  'Légifrance'
);
```

---

## 🆘 Dépannage

### Problème : Erreur 500

**Solutions :**
- Vérifiez les identifiants MySQL dans `config.php`
- Vérifiez que le dossier `/logs/` existe avec permission `755`
- Activez `DEBUG_MODE = true` dans config.php pour voir les erreurs

### Problème : "Erreur OpenAI"

**Solutions :**
- Vérifiez que votre clé API est correcte (commence par `sk-`)
- Vérifiez que vous avez du crédit sur https://platform.openai.com/usage
- Testez votre clé sur https://platform.openai.com/playground

### Problème : Pas de réponse

**Solutions :**
1. Ouvrez la console développeur (F12) → onglet Network
2. Vérifiez si l'appel à `/api/proxy.php` réussit
3. Consultez `/logs/queries.log` pour voir les erreurs

### Problème : "Rate limit"

**Cause :** Normal après 30 requêtes/heure

**Solutions :**
- Attendez 1 heure
- Ou augmentez la limite dans `config.php` :
  ```php
  define('RATE_LIMIT_REQUESTS', 60); // 60 au lieu de 30
  ```

---

## 📱 Support

- **Documentation complète :** Voir `README.md`
- **Forum OVH :** https://community.ovh.com/
- **OpenAI Status :** https://status.openai.com/

---

## 🎉 Prochaines étapes

Une fois NOIA fonctionnel :

1. ✅ Activez le HTTPS (SSL Let's Encrypt)
2. ✅ Configurez des limites de dépense sur OpenAI
3. ✅ Alimentez vos bases de données
4. ✅ Partagez l'accès à vos agents
5. ✅ Surveillez les logs et l'usage OpenAI régulièrement

---

## 💰 Coûts estimés

| Service | Coût mensuel | Note |
|---------|--------------|------|
| OVH Perso | 2-5€ | Hébergement web |
| OpenAI API | 5-30€ | Selon usage (GPT-4-turbo: ~0,01-0,03€/requête) |
| **TOTAL** | **7-35€** | Pour 100-500 questions/mois |

💡 **Économie v2.0.0 :** Plus besoin de Make.com (économie de 9-30€/mois) !

---

## 🔑 Choix du modèle OpenAI

| Modèle | Prix/requête | Qualité | Vitesse | Recommandation |
|--------|--------------|---------|---------|----------------|
| **gpt-4-turbo** | 0,01-0,03€ | Excellente | Moyenne | ✅ Production |
| **gpt-4o** | 0,005-0,015€ | Excellente | Rapide | ✅ Production |
| **gpt-3.5-turbo** | 0,001-0,002€ | Bonne | Très rapide | 🧪 Tests/Dev |

---

**Temps total estimé : 20 minutes**
**Difficulté : Facile** (copier-coller)
**Plus simple qu'avant :** Plus besoin de Make.com ! 🎉

Bon déploiement ! 🚀
