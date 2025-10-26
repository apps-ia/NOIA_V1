# 📦 NOIA v4.0 - Fichiers à Uploader (Phase 1)

## 🎯 Résumé Rapide

**7 fichiers à ajouter** + **2 scripts SQL à exécuter**

---

## 📂 FICHIERS À UPLOADER (via FTP)

### 1️⃣ Racine `/www/noia/`

```
✅ login.html                          [NOUVEAU]
   → Page de connexion moderne
```

### 2️⃣ Dossier `/www/noia/api/`

```
✅ auth.php                            [NOUVEAU]
   → Système d'authentification

✅ middleware.php                      [NOUVEAU]
   → Protection des APIs
```

### 3️⃣ Dossier `/www/noia/api/auth/` (À CRÉER)

```
✅ login.php                           [NOUVEAU]
   → Endpoint connexion

✅ logout.php                          [NOUVEAU]
   → Endpoint déconnexion

✅ check.php                           [NOUVEAU]
   → Vérification session
```

### 4️⃣ Optionnel : Documentation

```
📄 ARCHITECTURE_V4.md                  [NOUVEAU]
   → Documentation technique (optionnel)
```

---

## 🗄️ SCRIPTS SQL (via phpMyAdmin)

### Script 1 : `database/upgrade_v4_complete.sql`

**Crée 8 nouvelles tables :**
- users (authentification)
- sessions (gestion sessions)
- historique_conversation (mémoire)
- documents_generes (traçabilité)
- statistiques (métriques)
- legal_facts (base juridique)
- access_logs (logs sécurité)
- cache_rag (performance)

**+ 1 utilisateur admin par défaut**

### Script 2 : `database/legal_facts_data.sql`

**Charge 20+ règles juridiques validées :**
- Quorum (3 règles)
- FCTVA (3 règles)
- M57 (2 règles)
- CGCT (2 règles)
- RH (2 règles)
- Marchés publics, IFSE, Budget, etc.

---

## ✋ FICHIERS À NE PAS TOUCHER

**Ces fichiers restent INCHANGÉS :**

```
❌ NE PAS REMPLACER :
   ├─ index.html                  (interface actuelle)
   ├─ script.js                   (logique frontend)
   ├─ api/proxy.php               (sera mis à jour Phase 2)
   ├─ api/embeddings.php          (RAG - Phase 2)
   ├─ api/web_search.php          (recherche web)
   ├─ api/doc_manager.php         (gestion documents)
   ├─ config/config.php           (garder vos clés API !)
   └─ admin_documents.html        (gestion docs)
```

---

## 🚀 ORDRE D'INSTALLATION

### Étape 1 : MySQL (phpMyAdmin)
```
1. Ouvrir phpMyAdmin
2. Sélectionner base NOIA
3. Onglet "SQL"
4. Copier/coller upgrade_v4_complete.sql → Exécuter
5. Copier/coller legal_facts_data.sql → Exécuter
```

### Étape 2 : FTP (FileZilla)
```
1. Créer dossier /api/auth/
2. Uploader login.html (racine)
3. Uploader auth.php et middleware.php (dans /api/)
4. Uploader login.php, logout.php, check.php (dans /api/auth/)
```

### Étape 3 : Test
```
1. Aller sur : https://noia.votre-domaine.fr/login.html
2. Se connecter avec : admin@noia.local / admin123
3. Changer immédiatement le mot de passe admin !
```

---

## 📋 Checklist Complète

```
Base de données :
☐ Script upgrade_v4_complete.sql exécuté
☐ Script legal_facts_data.sql exécuté
☐ 8 nouvelles tables créées
☐ 1 utilisateur admin créé
☐ 20+ règles juridiques chargées

Fichiers FTP :
☐ Dossier /api/auth/ créé
☐ login.html uploadé (racine)
☐ api/auth.php uploadé
☐ api/middleware.php uploadé
☐ api/auth/login.php uploadé
☐ api/auth/logout.php uploadé
☐ api/auth/check.php uploadé

Tests :
☐ Page login.html accessible
☐ Connexion admin réussie
☐ Mot de passe admin changé
☐ Fichiers SQL supprimés du serveur

Sécurité :
☐ Permissions correctes (644 pour .php)
☐ DEBUG_MODE = false (dans config.php)
☐ Mot de passe admin fort (min 8 caractères)
```

---

## ⚡ Aide Rapide

**Si erreur "Table already exists" :**
```sql
DROP TABLE IF EXISTS users, sessions, historique_conversation,
                     documents_generes, statistiques, legal_facts,
                     access_logs, cache_rag;
-- Puis réexécuter upgrade_v4_complete.sql
```

**Si erreur 500 sur login :**
```
1. Vérifier config.php (DB_HOST, DB_NAME, DB_USER, DB_PASS)
2. Activer DEBUG_MODE temporairement
3. Consulter logs PHP
```

**Si connexion en boucle :**
```
1. Vider le cache navigateur
2. Tester avec un autre navigateur
3. Vérifier que les cookies sont autorisés
```

---

## ✅ Résultat Final

Après installation, vous aurez :

✅ **Page de connexion moderne** (`login.html`)
✅ **Système d'authentification sécurisé** (admin/agent/guest)
✅ **Base juridique de 20+ règles** (quorum, FCTVA, M57, etc.)
✅ **8 nouvelles tables MySQL** (historique, stats, logs)
✅ **Protection brute force** (5 tentatives max)
✅ **Sessions sécurisées** (CSRF, expiration, logging)

**Durée totale : 15-20 minutes**

---

📖 **Guide détaillé : INSTALLATION_PHASE1.md**
🏗️ **Architecture complète : ARCHITECTURE_V4.md**
