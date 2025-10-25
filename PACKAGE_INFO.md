# 📦 NOIA - Package complet d'installation

## 🎯 Contenu du package

Vous disposez maintenant de **tous les fichiers** nécessaires pour déployer NOIA sur votre hébergement OVH.

### 📁 Structure des fichiers

```
noia/
├── 📄 index.html                    # Interface utilisateur principale
├── 🎨 style.css                     # Styles modernes et responsifs
├── ⚙️ script.js                     # Logique frontend et API
├── 🔒 .htaccess                     # Sécurité Apache
├── 🧪 test.php                      # Script de test (à supprimer après)
├── 📚 README.md                     # Documentation complète
├── 🚀 QUICKSTART.md                 # Guide de démarrage rapide (30 min)
│
├── 💾 database_init.sql             # Création tables + données de base
├── 📊 donnees_supplementaires.sql   # 20+ règles supplémentaires
│
├── 📂 api/
│   └── proxy.php                    # API sécurisée (masque Make webhook)
│
├── 📂 config/
│   └── config.php                   # Configuration (DB, webhooks, sécurité)
│
└── 📂 logs/                         # (à créer manuellement, permission 755)
```

---

## ✅ Checklist d'installation rapide

### Phase 1 : Hébergement OVH (10 min)
- [ ] Créer le sous-domaine `noia.votre-domaine.fr`
- [ ] Noter les identifiants MySQL (serveur, base, user, password)
- [ ] Activer le SSL (Let's Encrypt gratuit)

### Phase 2 : Upload FTP (5 min)
- [ ] Uploader tous les fichiers dans `/www/noia/`
- [ ] Créer le dossier `/logs/` avec permission 755
- [ ] Vérifier les permissions fichiers PHP (644)

### Phase 3 : Base de données (5 min)
- [ ] Ouvrir phpMyAdmin
- [ ] Importer `database_init.sql` (tables + 9 données)
- [ ] (Optionnel) Importer `donnees_supplementaires.sql` (+20 règles)

### Phase 4 : Configuration (3 min)
- [ ] Éditer `/config/config.php`
- [ ] Remplir DB_HOST, DB_NAME, DB_USER, DB_PASS
- [ ] Laisser MAKE_WEBHOOK pour l'instant

### Phase 5 : Test (2 min)
- [ ] Accéder à `https://noia.votre-domaine.fr/test.php`
- [ ] Vérifier que tous les tests sont ✅ verts
- [ ] **Supprimer test.php après validation**

### Phase 6 : Make.com (10 min)
- [ ] Créer compte sur make.com
- [ ] Créer scénario avec 3 modules :
  1. Webhook (copier l'URL générée)
  2. OpenAI (voir prompt dans QUICKSTART.md)
  3. Webhook Response
- [ ] Activer le scénario (bouton ON)
- [ ] Coller l'URL webhook dans `config.php`

### Phase 7 : Validation finale (2 min)
- [ ] Ouvrir `https://noia.votre-domaine.fr/`
- [ ] Poser une question test
- [ ] Vérifier la réponse structurée

**Total estimé : 30-40 minutes**

---

## 🔑 Informations importantes à configurer

### Dans config.php

```php
// À REMPLACER impérativement
define('DB_HOST', 'mysqlXX.ovh.net');      // ← Votre serveur MySQL OVH
define('DB_NAME', 'votre_base');            // ← Nom de votre base
define('DB_USER', 'votre_user');            // ← Utilisateur MySQL
define('DB_PASS', 'votre_password');        // ← Mot de passe MySQL

// À remplir après configuration Make.com
define('MAKE_WEBHOOK', 'https://hook.eu1.make.com/xxxxx');
```

### Dans index.html (optionnel)

Ligne 44-48 : Ajouter vos communes

```html
<select id="commune">
  <option value="general">Général</option>
  <option value="ma_commune_1">Ma commune 1</option>
  <option value="ma_commune_2">Ma commune 2</option>
</select>
```

---

## 🎨 Fonctionnalités de l'interface

### ✨ Interface moderne
- Design épuré inspiré de Génial (Dalloz)
- Responsive (mobile, tablette, desktop)
- Animations fluides
- Mode clair optimisé pour longue lecture

### 🔐 Sécurité renforcée
- Webhook Make masqué via proxy PHP
- Protection injection SQL (requêtes préparées)
- Validation stricte des entrées
- Rate limiting (30 req/h par IP)
- Headers de sécurité (XSS, CSRF, clickjacking)
- Sanitization HTML des réponses

### 📊 Fonctionnalités
- Chat conversationnel intuitif
- Suggestions de questions prédéfinies
- Indicateur de frappe pendant traitement
- Scroll automatique
- Gestion d'erreurs claire
- Compteur de questions quotidiennes
- Temps de réponse moyen affiché

### 📚 Réponses structurées
Chaque réponse NOIA contient :
- 📚 Références juridiques (Légifrance, CGCT, etc.)
- 🔍 Analyse réglementaire
- ✅ Application pratique
- 📄 Proposition d'acte (si pertinent)

---

## 📈 Données fournies

### Base centrale (29 règles)
- 5 règles dans `database_init.sql`
- 24 règles dans `donnees_supplementaires.sql`

**Catégories :**
- 💼 RH (8 règles) : recrutement, rémunération, congés, temps partiel...
- 💰 Finances (8 règles) : M57, subventions, budget, régies...
- ⚖️ Juridique (7 règles) : délibérations, police, délégations...
- 🏗️ Urbanisme (6 règles) : permis, certificats, servitudes...

### Base locale (4 exemples)
- Délibérations communes A et B
- Arrêtés municipaux
- Notes de service

**→ À enrichir avec vos propres documents !**

---

## 🚀 Évolutions possibles

### Court terme (facile)
- [ ] Ajouter d'autres communes dans le sélecteur
- [ ] Enrichir la base avec vos documents
- [ ] Personnaliser les couleurs (variables CSS dans style.css)
- [ ] Ajouter votre logo (remplacer la div .logo)

### Moyen terme (intermédiaire)
- [ ] Export PDF des conversations
- [ ] Historique des questions (localStorage)
- [ ] Favoris / questions fréquentes
- [ ] Mode sombre
- [ ] Recherche dans la base documentaire

### Long terme (avancé)
- [ ] Génération automatique de documents (délibérations, arrêtés)
- [ ] Upload de documents PDF pour analyse
- [ ] Intégration avec logiciels métier (paie, compta)
- [ ] API REST publique pour autres services
- [ ] Interface vocale (Whisper)

---

## 📞 Support et ressources

### Documentation
- **README.md** : Documentation complète et détaillée
- **QUICKSTART.md** : Installation en 30 minutes
- Ce fichier : Vue d'ensemble du package

### Communautés
- [Forum OVH](https://community.ovh.com/)
- [Forum Make.com](https://community.make.com/)
- [Documentation OpenAI](https://platform.openai.com/docs)

### Sites officiels pour alimenter la base
- [Légifrance](https://www.legifrance.gouv.fr/)
- [DGCL](https://www.collectivites-locales.gouv.fr/)
- [Service-public.fr Pro](https://www.service-public.fr/professionnels-entreprises)
- [Emploi-collectivités.fr](https://www.emploi-collectivites.fr/)

---

## ⚠️ Avertissements importants

### Sécurité
1. **Supprimez test.php** après validation
2. **Changez TOUS les mots de passe** par défaut
3. **Activez HTTPS** (SSL obligatoire)
4. **Ne partagez JAMAIS** votre config.php
5. **Sauvegardez régulièrement** votre base de données

### Conformité RGPD
NOIA est RGPD-compatible par conception :
- ❌ Aucune donnée personnelle stockée
- ❌ Aucune conversation conservée
- ❌ Aucun tracking utilisateur
- ✅ Logs anonymisés (IP hachée possible)
- ✅ Sources publiques uniquement

**Attention :** Si vous connectez NOIA à des systèmes métier avec données RH/financières nominatives, une analyse RGPD complète sera nécessaire.

### Coûts
- **OVH Perso** : ~2-5€/mois (hébergement)
- **Make.com** : Gratuit jusqu'à 1000 opérations/mois, puis ~9€/mois
- **OpenAI API** : ~0,01-0,03€ par requête (GPT-4)

**Estimation mensuelle :** 20-50€ selon usage (100-500 questions/mois)

---

## 🎉 Vous êtes prêt !

Tous les fichiers sont dans le dossier `noia/`.

**Prochaine étape :**
1. Lisez le **QUICKSTART.md** (5 min)
2. Suivez la checklist d'installation (30 min)
3. Testez votre première question !

**Bon déploiement ! 🚀**

---

## 📋 Fichiers à ne PAS oublier

### Essentiels
- ✅ index.html, style.css, script.js
- ✅ .htaccess (sécurité)
- ✅ config.php (configuration)
- ✅ proxy.php (API)
- ✅ database_init.sql (base)

### Optionnels mais recommandés
- ✅ donnees_supplementaires.sql (contenu enrichi)
- ✅ test.php (diagnostic, à supprimer après)
- ✅ README.md (référence)
- ✅ QUICKSTART.md (installation rapide)

### À créer manuellement
- ✅ Dossier `/logs/` avec permission 755

---

**Version du package :** 1.0.0  
**Date de création :** Octobre 2024  
**Licence :** Usage interne collectivités territoriales

🤖 Créé avec passion pour simplifier la vie des agents territoriaux.
