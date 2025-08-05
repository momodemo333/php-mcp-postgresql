# 🏷️ Guide de Versioning - Leçons Apprises

Guide pour éviter les erreurs de versioning avec Packagist et Semantic Versioning.

## 📋 **Processus de Release Correct**

### ✅ **Ordre OBLIGATOIRE pour créer une version :**

1. **Faire les modifications de code** (features, fixes, etc.)
2. **Mettre à jour `composer.json`** avec la nouvelle version
3. **Mettre à jour `CHANGELOG.md`** avec les release notes
4. **Commiter TOUT ensemble** dans un seul commit
5. **Créer le tag sur ce commit** (pas avant !)
6. **Pousser commits ET tags vers GitHub**

### ❌ **Erreur à éviter : Tag avant composer.json**

**Problème rencontré** : Tag v1.0.1 pointait vers un commit avec `"version": "1.0.0"` dans composer.json

**Erreur Packagist** :
```
Skipped tag v1.0.1, tag (1.0.1.0) does not match version (1.0.0.0) in composer.json
```

**Cause** : Tag créé avant le commit qui met à jour composer.json

## 🔧 **Commandes de Release**

### **Processus Complet :**

```bash
# 1. Modifications de code déjà faites...

# 2. Mettre à jour composer.json
# Changer "version": "1.0.0" vers "version": "1.0.1"

# 3. Mettre à jour CHANGELOG.md
# Ajouter section [1.0.1] avec release notes

# 4. Commiter TOUT ensemble
git add -A
git commit -m "🏷️ Bump version to v1.0.1

- Updated composer.json version to 1.0.1
- Added comprehensive v1.0.1 release notes to CHANGELOG.md
- [Description des changements]"

# 5. Créer le tag SUR CE COMMIT
git tag -a v1.0.1 -m "Version 1.0.1 - [Description]"

# 6. Pousser tout
git push origin main
git push origin v1.0.1
```

### **Si erreur commise (tag incorrect) :**

```bash
# Supprimer le tag local
git tag -d v1.0.1

# Supprimer le tag sur GitHub
git push origin :refs/tags/v1.0.1

# Recréer le tag sur le bon commit
git tag -a v1.0.1 -m "Version 1.0.1" HEAD

# Repousser le tag corrigé
git push origin v1.0.1
```

## 📊 **Types de Versions (Semantic Versioning)**

### **MAJOR.MINOR.PATCH** (ex: 2.1.3)

| Type | Quand utiliser | Exemple |
|------|----------------|---------|
| **MAJOR** (2.0.0) | Breaking changes | API changes, PHP 8.2+ required |
| **MINOR** (1.1.0) | New features (compatible) | Add PostgreSQL support, new tools |
| **PATCH** (1.0.1) | Bug fixes, improvements | Config cleanup, documentation fixes |

### **Exemples concrets pour ce projet :**

- **v1.0.1** ✅ : Suppression variables non utilisées (cleanup)
- **v1.1.0** 🔮 : Ajout cache système, transactions
- **v1.2.0** 🔮 : Support PostgreSQL, nouveaux outils MCP
- **v2.0.0** 🔮 : PHP 8.2+ requis, nouvelle architecture MCP

## ⚠️ **Erreurs Communes à Éviter**

### **1. Tag avant composer.json**
```bash
❌ git tag v1.0.1        # Avant mise à jour composer.json
❌ # Puis commit composer.json après
```

### **2. Oublier de pousser le tag**
```bash
✅ git push origin main   # Pousse les commits
❌ # Oublie git push origin v1.0.1
```

### **3. Version incohérente**
```bash
❌ composer.json: "version": "1.0.1"
❌ git tag v1.0.2  # Versions différentes !
```

### **4. CHANGELOG.md pas à jour**
```bash
❌ Tag créé sans documenter les changements
```

## 🎯 **Validation Pré-Release**

### **Checklist avant de créer un tag :**

- [ ] Code fonctionnel testé (`php tests/test_mcp_server.php`)
- [ ] `composer.json` version mise à jour
- [ ] `CHANGELOG.md` release notes ajoutées
- [ ] Commit créé avec tous les changements
- [ ] Tag créé SUR ce commit (pas avant)
- [ ] Tests passent après le tag
- [ ] Commits ET tag poussés vers GitHub

### **Vérification post-release :**

```bash
# Vérifier que le tag pointe vers le bon commit
git show v1.0.1:composer.json | head -5

# Doit afficher la bonne version
# "version": "1.0.1"
```

## 📚 **Ressources**

- **[Semantic Versioning](https://semver.org/)** - Spécification officielle
- **[Keep a Changelog](https://keepachangelog.com/)** - Format CHANGELOG.md
- **[Packagist](https://packagist.org/)** - Registry Composer

## 🎓 **Leçons Apprises**

### **v1.0.1 (2025-08-05)**
- **Erreur** : Tag créé avant mise à jour composer.json
- **Symptôme** : Packagist "version mismatch" error
- **Solution** : Supprimer et recréer tag sur bon commit
- **Prévention** : Toujours commiter version avant de créer tag

---

**💡 Conseil** : En cas de doute, mieux vaut créer le tag "trop tard" que "trop tôt" !