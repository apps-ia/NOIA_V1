# 🔒 NOIA - Analyse RGPD : Solution Assistants API + RAG

## 📋 Vue d'Ensemble

L'utilisation des **Assistants API OpenAI** avec **File Search (RAG)** implique le transfert et le stockage de données sur les serveurs OpenAI (USA). Voici l'analyse complète pour la conformité RGPD.

---

## 🎯 Données Concernées par NOIA

### **Données Uploadées vers OpenAI**

| Type de Données | Localisation | RGPD | Risque |
|----------------|--------------|------|--------|
| **Legal facts** (lois, décrets, articles CGCT) | Serveurs OpenAI (USA) | ✅ OK | ❌ Aucun (données publiques) |
| **Grilles indiciaires** | Serveurs OpenAI (USA) | ✅ OK | ❌ Aucun (données publiques) |
| **Guides administratifs** | Serveurs OpenAI (USA) | ✅ OK | ❌ Aucun (données publiques) |
| **Questions utilisateurs** | Threads OpenAI (USA) | ⚠️ ATTENTION | ⚠️ Moyen si données perso |
| **Réponses NOIA** | Threads OpenAI (USA) | ✅ OK | ❌ Aucun (conseils généraux) |

**Conclusion :** Les **documents de base** (RAG) ne posent **AUCUN problème RGPD** car ce sont des **données publiques** (législation française).

**Risque RGPD :** Les **questions des utilisateurs** peuvent contenir des données personnelles.

---

## ⚖️ Analyse Juridique RGPD

### **Article 5 RGPD - Principes**

| Principe | OpenAI Assistants API | Statut | Mesures à Prendre |
|----------|----------------------|--------|-------------------|
| **Licéité** | Consentement utilisateur | ✅ OK | Mentions légales + CGU |
| **Limitation de finalité** | Uniquement réponses NOIA | ✅ OK | DPA OpenAI (pas d'entraînement) |
| **Minimisation** | Seulement question/réponse | ✅ OK | Ne pas demander de données inutiles |
| **Exactitude** | Réponses basées sur legal facts | ✅ OK | Mise à jour régulière documents |
| **Limitation conservation** | Threads conservés indéfiniment | ❌ PROBLÈME | **Suppression auto après 30j** |
| **Intégrité/Confidentialité** | Chiffrement en transit | ✅ OK | HTTPS + TLS 1.3 |

---

### **Article 44-46 RGPD - Transferts Hors UE**

**Problème :** OpenAI stocke les données aux **USA** (hors UE).

**Solutions légales :**

| Solution | Description | Statut |
|----------|-------------|--------|
| **DPA OpenAI** | Data Processing Agreement | ✅ Disponible |
| **Clauses contractuelles types** | SCCs (Standard Contractual Clauses) | ✅ OpenAI les propose |
| **Décision d'adéquation** | EU-US Data Privacy Framework | ✅ USA considéré adéquat (juillet 2023) |
| **Azure OpenAI (EU)** | Hébergement en Europe | ⚠️ Alternative (+ cher) |

**Recommandation :** Signer le **DPA OpenAI** (disponible sur https://openai.com/enterprise-privacy)

---

## 🔐 Politique OpenAI sur les Données

### **Depuis Mars 2023 - API Enterprise**

| Aspect | Politique OpenAI | Impact NOIA |
|--------|-----------------|-------------|
| **Entraînement modèle** | ❌ Données API **NON utilisées** pour entraînement | ✅ Parfait |
| **Conservation** | 30 jours puis suppression (sauf si attachées à assistant) | ⚠️ Threads persistent |
| **Accès OpenAI** | Uniquement abus/debugging (avec consentement) | ✅ OK |
| **Chiffrement** | TLS 1.3 en transit, AES-256 au repos | ✅ Sécurisé |
| **Logs** | 30 jours pour monitoring, puis suppression | ✅ OK |

**Source :** https://openai.com/enterprise-privacy

---

## ✅ Mesures de Conformité RGPD pour NOIA

### **1. Mentions Légales et CGU**

**À ajouter sur NOIA :**

```
📋 PROTECTION DES DONNÉES

NOIA utilise l'intelligence artificielle OpenAI pour générer ses réponses.

Données traitées :
- Vos questions sont envoyées de manière sécurisée à OpenAI (USA)
- Les réponses sont générées à partir de notre base documentaire (lois françaises)
- Aucune donnée personnelle n'est requise pour utiliser NOIA

Conservation :
- Les conversations sont conservées 30 jours puis automatiquement supprimées
- Vous pouvez demander la suppression immédiate via contact@noia.fr

Transfert hors UE :
- Données traitées par OpenAI (USA) avec garanties contractuelles (SCCs)
- OpenAI ne réutilise PAS vos données pour entraîner ses modèles

Vos droits RGPD :
- Accès, rectification, suppression : contact@noia.fr
- Réclamation auprès de la CNIL : www.cnil.fr
```

---

### **2. Suppression Automatique des Threads**

**Code à implémenter :**

```php
// Supprimer threads > 30 jours
function cleanOldThreads() {
    $threads = listAllThreads();
    foreach ($threads as $thread) {
        $created = strtotime($thread['created_at']);
        $age_days = (time() - $created) / 86400;

        if ($age_days > 30) {
            deleteThread($thread['id']);
        }
    }
}

// Cron job quotidien
// 0 2 * * * /usr/bin/php /path/to/clean_threads.php
```

---

### **3. Anonymisation des Questions**

**Bonnes pratiques :**

❌ **Mauvais :**
```
"Jean Dupont, agent à Mairie de Toulouse,
veut savoir sa grille indiciaire..."
```

✅ **Bon :**
```
"Quelle est la grille indiciaire pour
un adjoint administratif territorial ?"
```

**Message à afficher :**
```
⚠️ Ne communiquez jamais de données personnelles
(noms, prénoms, adresses) dans vos questions.
```

---

### **4. Registre de Traitement RGPD**

**À déclarer :**

| Champ | Valeur |
|-------|--------|
| **Finalité** | Assistant IA pour questions administratives |
| **Base légale** | Consentement (CGU acceptées) |
| **Catégories de données** | Questions/réponses (textes) |
| **Destinataires** | OpenAI (sous-traitant USA) |
| **Transfert hors UE** | Oui (USA) - SCCs signées |
| **Durée conservation** | 30 jours |
| **Mesures sécurité** | Chiffrement TLS 1.3, HTTPS, authentification |

---

## 🌍 Alternative Azure OpenAI (Hébergement EU)

### **Si Exigence d'Hébergement en Europe**

| Critère | OpenAI Standard | Azure OpenAI (EU) |
|---------|----------------|-------------------|
| **Localisation** | USA | ✅ Europe (France, Pays-Bas) |
| **RGPD** | ⚠️ Transfert hors UE | ✅ Données en UE |
| **Coût** | Standard | ⚠️ +30% environ |
| **Fonctionnalités** | Tous les modèles | Tous les modèles |
| **Complexité** | Simple | ⚠️ Compte Azure requis |
| **DPA** | Signé séparément | ✅ Inclus (Microsoft) |

**Recommandation :** Azure OpenAI **seulement si** obligation stricte d'hébergement EU (secteur public sensible).

---

## 📊 Évaluation des Risques RGPD

### **Matrice de Risque**

| Risque | Probabilité | Gravité | Niveau | Mitigation |
|--------|------------|---------|--------|------------|
| Fuite données via questions utilisateurs | Faible | Moyenne | 🟡 MOYEN | Anonymisation + message avertissement |
| Threads conservés indéfiniment | Élevée | Faible | 🟡 MOYEN | ✅ Suppression auto 30j |
| Transfert hors UE | Certaine | Faible | 🟢 FAIBLE | ✅ DPA + SCCs signées |
| Accès non autorisé aux documents | Très faible | Faible | 🟢 FAIBLE | Authentification NOIA |
| Réutilisation données par OpenAI | Très faible | Élevée | 🟢 FAIBLE | ✅ Politique API Enterprise |

**Score global : 🟢 RISQUE FAIBLE** (avec mesures appliquées)

---

## ✅ Checklist de Mise en Conformité

### **Avant Lancement**

- [ ] Signer le **DPA OpenAI** (https://openai.com/enterprise-privacy)
- [ ] Ajouter **mentions légales RGPD** sur NOIA
- [ ] Implémenter **suppression auto threads** (30 jours)
- [ ] Ajouter **message d'avertissement** (pas de données perso)
- [ ] Mettre à jour **CGU/Politique confidentialité**
- [ ] Inscrire traitement au **registre RGPD** (obligation si > 250 employés)

### **En Production**

- [ ] Monitoring mensuel : vérifier suppression threads
- [ ] Audit annuel : conformité RGPD
- [ ] Mise à jour DPA si changements OpenAI

---

## 🎯 Recommandation Finale

### **Pour NOIA (Administration Territoriale - Usage Interne)**

✅ **Solution OpenAI Standard + Mesures RGPD = CONFORME**

**Justification :**
1. ✅ Documents uploadés = données **publiques** (lois françaises)
2. ✅ Questions = conseils généraux (pas de dossiers individuels)
3. ✅ DPA + SCCs = transfert USA légal
4. ✅ Suppression 30j = limitation conservation
5. ✅ Pas d'entraînement = finalité respectée

**Coût conformité :** ~0€ (juste implémentation technique)

---

### **Si Secteur Public Sensible (Préfecture, Ministère)**

⚠️ **Azure OpenAI (EU) recommandé**

**Justification :**
- Exigence hébergement données en Europe
- Contrats publics avec clauses strictes
- Audit de sécurité niveau élevé

**Coût supplémentaire :** ~+30% (~$180/mois au lieu de $140/mois)

---

## 📞 Contact et Ressources

**Documentation RGPD OpenAI :**
- DPA : https://openai.com/enterprise-privacy
- Trust Portal : https://trust.openai.com/
- Sécurité : https://openai.com/security

**Ressources CNIL :**
- Guide IA : https://www.cnil.fr/fr/intelligence-artificielle
- Transferts hors UE : https://www.cnil.fr/fr/les-transferts-de-donnees-hors-de-lue

**En cas de doute :**
- Contacter un DPO (Délégué Protection Données)
- Consulter avocat spécialisé RGPD

---

## 📄 Résumé Exécutif

| Question | Réponse |
|----------|---------|
| **NOIA avec Assistants API est-il conforme RGPD ?** | ✅ **OUI** (avec mesures listées) |
| **Peut-on uploader des legal facts (lois) ?** | ✅ **OUI** (données publiques) |
| **Les questions utilisateurs posent-elles problème ?** | ⚠️ **GÉRABLE** (anonymisation + suppression 30j) |
| **Transfert USA est-il légal ?** | ✅ **OUI** (DPA + SCCs) |
| **Risque global ?** | 🟢 **FAIBLE** (score 2/10) |
| **Coût conformité ?** | 💰 **0€** (juste dev) |
| **Recommandation ?** | ✅ **GO** avec OpenAI Standard |

---

**Conclusion : Vous pouvez utiliser les Assistants API + RAG en toute conformité RGPD avec les mesures de sécurité appropriées.** ✅
