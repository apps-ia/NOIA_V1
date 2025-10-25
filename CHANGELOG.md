# 📝 Changelog NOIA

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/lang/fr/).

---

## [2.0.0] - 2024-10-25

### 🚀 Version majeure - Intégration OpenAI directe

#### ⚠️ BREAKING CHANGES
- **Make.com supprimé** : L'orchestration via Make.com n'est plus nécessaire
- **Nouvelle configuration** : `config.php` requiert maintenant `OPENAI_API_KEY` au lieu de `MAKE_WEBHOOK`
- **Architecture simplifiée** : Appel direct à l'API OpenAI depuis PHP

#### Ajouté
- **Intégration OpenAI directe**
  - Appels API OpenAI directement depuis `proxy.php`
  - Support cURL natif PHP
  - Gestion des timeouts (30 secondes)
  - Gestion des erreurs OpenAI (401, 429, 500, etc.)

- **Configuration enrichie**
  - `OPENAI_MODEL` : Choix du modèle (gpt-4-turbo, gpt-4o, gpt-3.5-turbo)
  - `OPENAI_MAX_TOKENS` : Limite de tokens configurable
  - `OPENAI_TEMPERATURE` : Contrôle de la créativité
  - `DEBUG_MODE` : Mode débogage pour développement

- **Nouveaux fichiers**
  - `.gitignore` : Protection des fichiers sensibles
  - `config.example.php` : Exemple de configuration
  - Fichiers d'implémentation complets (index.html, script.js, style.css, etc.)

- **Logs améliorés**
  - Temps de réponse précis
  - Compteur de sources utilisées
  - Meilleure traçabilité des erreurs

- **Sécurité renforcée**
  - Protection clé API OpenAI via .htaccess
  - Headers de sécurité améliorés (CSP mis à jour)
  - Timeout API pour éviter les blocages

#### Modifié
- **proxy.php** : Réécriture complète pour appel direct OpenAI
- **config.php** : Nouvelle structure de configuration
- **script.js** : Affichage du nombre de sources consultées
- **README.md** : Documentation mise à jour pour v2.0.0
- **QUICKSTART.md** : Guide d'installation simplifié
- **ARCHITECTURE.html** : Schéma mis à jour sans Make.com

#### Supprimé
- Dépendance Make.com
- Configuration `MAKE_WEBHOOK`
- Modules Make.com (webhook, orchestration)
- Complexité de l'architecture (un service en moins)

#### Avantages v2.0.0
- ✅ **Coûts réduits** : Pas d'abonnement Make.com nécessaire
- ✅ **Latence améliorée** : Un service en moins dans la chaîne = réponses plus rapides
- ✅ **Contrôle total** : Configuration fine du comportement OpenAI
- ✅ **Simplicité** : Moins de configuration requise (plus de compte Make.com)
- ✅ **Transparence** : Tout le code est dans le repository
- ✅ **Fiabilité** : Moins de points de défaillance

#### Migration depuis v1.0.0
Pour migrer depuis la version 1.0.0 :
1. Télécharger les nouveaux fichiers (proxy.php, config.php, script.js)
2. Obtenir une clé API OpenAI sur https://platform.openai.com/api-keys
3. Copier `config.example.php` vers `config.php`
4. Remplir `OPENAI_API_KEY` dans config.php
5. Supprimer la ligne `MAKE_WEBHOOK` de config.php
6. Tester l'interface

**Note :** La base de données reste identique, aucune migration SQL nécessaire.

#### Compatibilité
- **PHP** : 7.4+ avec cURL (recommandé : 8.0+)
- **MySQL** : 5.7+ (recommandé : 8.0+)
- **Navigateurs** : Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **OpenAI API** : GPT-4-turbo, GPT-4o, GPT-3.5-turbo

---

## [1.0.0] - 2024-10-22

### 🎉 Version initiale

#### Ajouté
- **Interface utilisateur complète** (index.html)
  - Design moderne et épuré inspiré de Génial (Dalloz)
  - Chat conversationnel intuitif
  - Suggestions de questions prédéfinies
  - Indicateur de frappe pendant traitement
  - Compteur de questions et temps de réponse moyen
  - Responsive design (mobile, tablette, desktop)

- **Architecture backend sécurisée**
  - Proxy API PHP pour masquer le webhook Make
  - Configuration centralisée (config.php)
  - Système de logging complet
  - Rate limiting (30 requêtes/heure par IP)

- **Base de données**
  - Script d'initialisation (database_init.sql)
  - 2 tables : base_centrale et base_locale
  - 9 règles de base dans base_centrale
  - 4 exemples dans base_locale
  - Index FULLTEXT pour recherches performantes

- **Données supplémentaires** (donnees_supplementaires.sql)
  - 24 règles additionnelles
  - Catégories : RH (8), Finances (8), Juridique (7), Urbanisme (6)
  - Couvre les cas d'usage les plus fréquents

- **Sécurité**
  - Protection contre injections SQL (requêtes préparées PDO)
  - Validation stricte des entrées utilisateur
  - Sanitization HTML (liste blanche de balises)
  - Headers de sécurité (CSP, XSS Protection, etc.)
  - Fichier .htaccess avec règles de protection
  - Masquage du webhook Make.com

- **Documentation complète**
  - README.md (documentation détaillée)
  - QUICKSTART.md (installation en 30 minutes)
  - PACKAGE_INFO.md (vue d'ensemble du package)
  - ARCHITECTURE.html (schéma visuel interactif)
  - CHANGELOG.md (ce fichier)

- **Outils de diagnostic**
  - test.php (script de vérification d'installation)
  - Logging automatique des requêtes
  - Gestion d'erreurs claire côté utilisateur

#### Fonctionnalités
- ✅ Recherche dans base centrale (commune à tous)
- ✅ Recherche dans base locale (par commune)
- ✅ Intégration OpenAI GPT-4 via Make.com
- ✅ Réponses structurées en 4 sections
- ✅ Citations de sources officielles
- ✅ Sélecteur de commune dynamique
- ✅ Gestion des erreurs réseau
- ✅ Auto-scroll dans le chat
- ✅ Auto-resize du champ de saisie

#### Conformité
- ✅ RGPD-compatible par conception
- ✅ Aucune donnée personnelle stockée
- ✅ Aucune conversation conservée
- ✅ Logs anonymisés

---

## [Prévu] - Versions futures

### [1.1.0] - À venir
#### Prévu
- [ ] Export PDF des conversations
- [ ] Historique local (localStorage)
- [ ] Système de favoris
- [ ] Mode sombre
- [ ] Amélioration du prompt OpenAI
- [ ] Plus de données dans base_centrale (50+ règles)

### [1.2.0] - À venir
#### Prévu
- [ ] Génération automatique de documents (délibérations, arrêtés)
- [ ] Templates de documents téléchargeables (.docx, .pdf)
- [ ] Recherche avancée dans la bibliothèque
- [ ] Filtres par catégorie et date
- [ ] Statistiques détaillées d'utilisation

### [1.3.0] - À venir
#### Prévu
- [ ] Upload de documents PDF pour analyse
- [ ] OCR pour extraction de texte
- [ ] Intégration avec logiciels métier (API)
- [ ] Multi-utilisateurs avec authentification
- [ ] Tableau de bord administrateur

### [2.0.0] - Long terme
#### Prévu
- [ ] Interface vocale (Whisper API)
- [ ] Application mobile (PWA)
- [ ] Mode hors-ligne avec synchronisation
- [ ] API REST publique
- [ ] Système de plugins/extensions
- [ ] IA fine-tunée sur corpus collectivités

---

## Notes de version

### Comment lire ce changelog

- **[Numéro]** : Numéro de version (X.Y.Z)
  - X = Version majeure (changements incompatibles)
  - Y = Version mineure (nouvelles fonctionnalités)
  - Z = Patch (corrections de bugs)

- **Ajouté** : Nouvelles fonctionnalités
- **Modifié** : Changements de fonctionnalités existantes
- **Déprécié** : Fonctionnalités qui seront supprimées
- **Supprimé** : Fonctionnalités supprimées
- **Corrigé** : Corrections de bugs
- **Sécurité** : Corrections de vulnérabilités

### Compatibilité

#### Version 1.0.0
- **PHP** : 7.4+ (recommandé : 8.0+)
- **MySQL** : 5.7+ (recommandé : 8.0+)
- **Navigateurs** : Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **Make.com** : Toutes versions
- **OpenAI API** : GPT-4-turbo, GPT-4o

---

## Contributeurs

### Créateur initial
- Conception et développement : Octobre 2024

### Remerciements
- Communauté OVH pour l'hébergement
- OpenAI pour l'API GPT-4
- Make.com pour l'orchestration
- Agents territoriaux pour les retours d'usage

---

## Support et mises à jour

Pour signaler un bug ou proposer une amélioration :
1. Consultez d'abord le README.md
2. Vérifiez les problèmes connus ci-dessous
3. Contactez le support technique

### Problèmes connus (v1.0.0)
- Aucun problème majeur identifié à ce jour
- Rate limiting peut être restrictif pour gros usages (ajustable)
- Pas de gestion multi-langues (français uniquement)

### Feuille de route
- T4 2024 : Version 1.1.0 (export, historique)
- T1 2025 : Version 1.2.0 (génération documents)
- T2 2025 : Version 1.3.0 (upload PDF, intégrations)
- 2026 : Version 2.0.0 (vocal, mobile, API)

---

## Licence

Usage interne collectivités territoriales françaises.

---

**Dernière mise à jour :** 25 octobre 2024
**Version actuelle :** 2.0.0
**Statut :** Stable - Production ready ✅
**Migration :** Make.com → OpenAI Direct
