# 🚀 Guide de démarrage rapide NOIA

Installation en **moins de 30 minutes** !

---

## ⏱️ Checklist d'installation (30 min)

### ☐ 1. Préparer OVH (5 min)

1. Créez un sous-domaine : `noia.votre-domaine.fr`
2. Notez vos identifiants MySQL (espace client OVH > Bases de données)
3. Activez le SSL si disponible (Let's Encrypt gratuit)

### ☐ 2. Uploader les fichiers (10 min)

Via FTP (FileZilla, WinSCP, etc.) :

```
/www/noia/
├── index.html
├── style.css
├── script.js
├── .htaccess
├── test.php
├── database_init.sql
├── /api/
│   └── proxy.php
└── /config/
    └── config.php
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
- Table `base_centrale` avec 5 enregistrements
- Table `base_locale` avec 4 enregistrements

### ☐ 4. Modifier config.php (3 min)

Éditez `/config/config.php` via FTP ou l'éditeur de fichiers OVH :

```php
define('DB_HOST', 'mysql47.perso.ovh.net');  // ← Votre serveur MySQL
define('DB_NAME', 'ma_base_noia');            // ← Nom de votre base
define('DB_USER', 'mon_utilisateur');         // ← Votre utilisateur
define('DB_PASS', 'mon_mot_de_passe');        // ← Votre mot de passe

// Laisser ça pour l'instant (on configurera Make après)
define('MAKE_WEBHOOK', 'https://hook.eu1.make.com/xxxxxx');
```

### ☐ 5. Tester l'installation (2 min)

Accédez à : `https://noia.votre-domaine.fr/test.php`

Tous les tests doivent être ✅ verts (sauf le webhook Make, normal pour l'instant).

**⚠️ Important :** Supprimez `test.php` après validation !

### ☐ 6. Configurer Make.com (10 min)

#### A. Créer un compte Make
- Allez sur [make.com](https://www.make.com)
- Inscrivez-vous (gratuit)

#### B. Créer le scénario

1. **Module 1 - Webhook :**
   - Ajoutez "Webhooks > Custom Webhook"
   - Créez un nouveau webhook
   - **Copiez l'URL** (ex: `https://hook.eu1.make.com/abc123xyz`)

2. **Module 2 - OpenAI :**
   - Ajoutez "OpenAI > Create a Chat Completion"
   - Connectez votre compte OpenAI (besoin d'une clé API)
   - Modèle : `gpt-4-turbo`
   - **Messages :**

   ```
   Role: system
   Content:
   Tu es NOIA, assistant spécialisé pour les collectivités territoriales françaises.
   
   CONTEXTE :
   Commune : {{1.commune}}
   Question : {{1.question}}
   
   SOURCES :
   - Base centrale : {{1.central_results}}
   - Base locale : {{1.local_results}}
   
   Réponds en structurant avec :
   📚 Références juridiques
   🔍 Analyse réglementaire
   ✅ Application pratique
   📄 Proposition d'acte (si pertinent)
   
   Format HTML avec <div class="structured-response"> etc.
   
   ---
   
   Role: user
   Content: {{1.question}}
   ```

3. **Module 3 - Webhook Response :**
   - Ajoutez "Webhooks > Webhook Response"
   - Status : 200
   - Body :
   ```json
   {
     "response": "{{2.choices[0].message.content}}"
   }
   ```

4. **Activez le scénario** (bouton ON)

#### C. Mettre à jour config.php

Collez l'URL du webhook Make dans `config.php` :

```php
define('MAKE_WEBHOOK', 'https://hook.eu1.make.com/abc123xyz');
```

---

## ✅ Test final

1. Accédez à `https://noia.votre-domaine.fr/`
2. Posez une question : *"Comment imputer un broyeur en M57 ?"*
3. Attendez 2-3 secondes
4. Vous devriez recevoir une réponse structurée !

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

## 🆘 Problèmes courants

| Problème | Solution rapide |
|----------|----------------|
| Erreur 500 | Vérifiez les identifiants MySQL dans `config.php` |
| Pas de réponse | Vérifiez que le scénario Make est activé (ON) |
| "Erreur JSON" | Vérifiez le format du prompt OpenAI dans Make |
| "Rate limit" | Normal après 30 requêtes/h, attendez ou augmentez dans config |

---

## 📱 Support

- **Documentation complète :** Voir `README.md`
- **Forum OVH :** https://community.ovh.com/
- **Forum Make :** https://community.make.com/

---

## 🎉 Prochaines étapes

Une fois NOIA fonctionnel :

1. ✅ Supprimez `test.php`
2. ✅ Activez le HTTPS (SSL)
3. ✅ Alimentez vos bases de données
4. ✅ Partagez l'accès à vos agents
5. ✅ Surveillez les logs régulièrement

---

**Temps total estimé : 30 minutes**  
**Difficulté : Facile** (copier-coller)

Bon déploiement ! 🚀
