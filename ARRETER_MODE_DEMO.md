# 🚨 URGENT : Fichiers à Uploader pour Arrêter le Mode Démo

## ❌ Problème Actuel

Vous voyez ce message :
```
⚠️ Mode démonstration — Réponse simulée (serveur PHP requis pour l'API réelle)
```

**Cause :** Les fichiers Phase 2 ne sont **pas sur votre serveur** erelys.fr.

---

## ✅ Solution (3 étapes - 10 minutes)

### **ÉTAPE 1 : Télécharger les fichiers depuis Git**

**Option A : Interface GitHub Web**
1. Aller sur votre dépôt GitHub
2. Cliquer sur la branche : `claude/remove-make-interface-011CUTxrLten4c6qKf8YMNek`
3. Naviguer vers le dossier `api/`
4. Télécharger ces 3 fichiers :
   - `pre_analysis.php` (clic → Raw → Ctrl+S)
   - `legal_facts_manager.php` (clic → Raw → Ctrl+S)
   - `proxy.php` (clic → Raw → Ctrl+S)

**Option B : Git Clone (si Git installé)**
```bash
git clone https://github.com/[VOTRE-DEPOT]/NOIA_V1.git
cd NOIA_V1
git checkout claude/remove-make-interface-011CUTxrLten4c6qKf8YMNek
```

Les fichiers sont dans : `NOIA_V1/api/`

---

### **ÉTAPE 2 : Connexion FTP (FileZilla)**

1. **Ouvrir FileZilla**

2. **Configurer la connexion :**
   ```
   Hôte : ftp.cluster0XX.hosting.ovh.net
   (ou l'hôte que vous utilisez habituellement)

   Identifiant : [votre login FTP OVH]
   Mot de passe : [votre mot de passe FTP]
   Port : 21
   ```

3. **Se connecter**

4. **Naviguer vers le dossier :**
   - Côté serveur (droite) : `/www/noia/api/`

---

### **ÉTAPE 3 : Upload des fichiers**

1. **IMPORTANT - Faire un backup d'abord :**
   - Clic droit sur `proxy.php` → **Renommer** → `proxy_BACKUP_OLD.php`
   - ✅ Maintenant vous avez une sauvegarde

2. **Uploader les 3 nouveaux fichiers :**
   - Côté local (gauche) : Sélectionner les 3 fichiers téléchargés
   - Glisser-déposer vers `/www/noia/api/` (côté droit)

   ```
   Fichiers à uploader :
   ✅ pre_analysis.php              → /www/noia/api/
   ✅ legal_facts_manager.php       → /www/noia/api/
   ✅ proxy.php                     → /www/noia/api/ (écrase l'ancien)
   ```

3. **Vérifier les permissions :**
   - Clic droit sur chaque fichier → **Permissions**
   - Valeur numérique : **644**
   - Ou cocher : `Lecture` + `Écriture` (propriétaire), `Lecture` (groupe et public)

---

## 🧪 Test Final

### **Test 1 : API accessible ?**

Ouvrir dans le navigateur :
```
https://noia.erelys.fr/api/proxy.php
```

**Résultat attendu :**
```json
{
  "error": "Méthode non autorisée. Utilisez POST."
}
```

✅ **Si vous voyez cette erreur = C'EST BON !** L'API fonctionne.

❌ **Si erreur 404 ou 500 = Problème d'upload** (vérifier que les fichiers sont bien là)

---

### **Test 2 : Poser une question sur NOIA**

1. Aller sur : `https://noia.erelys.fr/`
2. Se connecter (si pas déjà fait)
3. Poser cette question :
   ```
   Quelle est la grille indiciaire pour un adjoint administratif territorial ?
   ```

**Résultat attendu :**

✅ **PLUS de message "Mode démonstration"**

✅ **Réponse complète avec :**
- 1️⃣ Références juridiques (Décret n° 85-1148, n° 2016-604)
- 2️⃣ Analyse de la situation
- 3️⃣ Application pratique (grille complète avec TOUS les échelons)
- 4️⃣ Proposition d'usage
- Valeur du point : 4,92302 €
- Indices bruts + majorés + salaires pour CHAQUE échelon

---

## 📋 Checklist Ultra-Simple

```
☐ Télécharger pre_analysis.php depuis Git
☐ Télécharger legal_facts_manager.php depuis Git
☐ Télécharger proxy.php depuis Git
☐ Ouvrir FileZilla
☐ Se connecter au FTP OVH
☐ Naviguer vers /www/noia/api/
☐ Renommer l'ancien proxy.php en proxy_BACKUP_OLD.php
☐ Glisser-déposer les 3 nouveaux fichiers
☐ Vérifier permissions : 644
☐ Test : https://noia.erelys.fr/api/proxy.php → erreur 405 = OK
☐ Test question NOIA → Plus de "Mode démonstration"
```

---

## 🔧 Dépannage Express

### **Problème : Erreur 500 après upload**

**Cause :** Erreur PHP dans les fichiers

**Solution :**

1. Activer DEBUG_MODE dans `config/config.php` :
   ```php
   define('DEBUG_MODE', true);
   ```

2. Recharger `https://noia.erelys.fr/api/proxy.php`

3. Regarder l'erreur affichée

4. **Erreurs courantes :**
   - `Class 'PreAnalysis' not found` → `pre_analysis.php` pas uploadé
   - `Class 'LegalFactsManager' not found` → `legal_facts_manager.php` pas uploadé
   - `Table 'legal_facts' doesn't exist` → Base de données Phase 1 incomplète

---

### **Problème : Table 'legal_facts' doesn't exist**

**Solution :**

Exécuter dans phpMyAdmin :
```sql
-- Vérifier si la table existe
SHOW TABLES LIKE 'legal_facts';

-- Si absente, exécuter :
-- Fichier : database/upgrade_v4_PARTIE_A.sql
-- Puis : database/legal_facts_data.sql
```

---

## 🎯 Résumé Ultra-Rapide

**3 actions à faire :**

1. **Télécharger** les 3 fichiers depuis Git (branche `claude/remove-make-interface-011CUTxrLten4c6qKf8YMNek`)
2. **Uploader** via FTP vers `/www/noia/api/`
3. **Tester** sur https://noia.erelys.fr/

**Temps estimé :** 10 minutes

---

## 📞 Besoin d'Aide ?

Si vous n'arrivez pas à :

**Télécharger depuis Git :**
- Envoyez-moi le lien de votre dépôt Git
- Je vous fournirai les liens directs vers les fichiers

**Vous connecter en FTP :**
- Vérifiez vos identifiants OVH
- Hôte FTP : visible dans l'espace client OVH

**Uploader les fichiers :**
- Tutoriel FileZilla : https://docs.ovh.com/fr/hosting/mutualise-guide-utilisation-filezilla/

---

**🎉 Une fois uploadé, le mode démo disparaîtra et NOIA utilisera l'intelligence Phase 2 !**
