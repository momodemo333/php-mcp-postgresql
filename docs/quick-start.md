# ⚡ Démarrage Rapide

Votre premier serveur MCP MySQL opérationnel en moins de 5 minutes !

## 🎯 Objectif

À la fin de ce guide, vous aurez :
- ✅ Un serveur MCP MySQL fonctionnel
- ✅ Une base de données avec des données de test
- ✅ Claude Code configuré pour utiliser le serveur
- ✅ Vos premières requêtes réussies

## ⏱️ 5 Minutes Chrono !

### Étape 1 : Installation Express (1 min)

```bash
# Aller dans le répertoire du serveur
cd /chemin/vers/customMcp/mysql

# Installer les dépendances (si pas déjà fait)
composer install

# Rendre les scripts exécutables
chmod +x *.php
```

### Étape 2 : Configuration Rapide (30 sec)

```bash
# Copier la configuration d'exemple
cp .env.example .env

# Éditer avec vos paramètres MySQL
nano .env
```

Configuration minimale :
```bash
MYSQL_HOST=127.0.0.1
MYSQL_PORT=3306
MYSQL_USER=your_user
MYSQL_PASS=your_password
MYSQL_DB=your_database

# Permissions pour les tests
ALLOW_INSERT_OPERATION=true
ALLOW_UPDATE_OPERATION=true
ALLOW_DELETE_OPERATION=false
```

### Étape 3 : Test de Connexion (30 sec)

```bash
php test_connection.php
```

**✅ Résultat attendu :**
```
🔍 Test de connexion MySQL...
✅ Connexion PDO réussie!
MySQL version: 8.0.x
Base de données courante: your_database
```

### Étape 4 : Données de Test (1 min)

```bash
php setup_test_data.php
```

**✅ Résultat attendu :**
```
🗄️ Configuration du serveur de test MySQL...
✅ Connexion établie au serveur MySQL
📋 Création des tables de test...
✅ Tables créées avec succès
📊 Insertion des données de test...
✅ Données de test insérées avec succès

📈 Statistiques des données de test :
   👥 Utilisateurs : 5
   📦 Commandes : 9
   🏷️ Catégories : 6
```

### Étape 5 : Configuration Claude Code (1 min)

Créez ou éditez votre configuration MCP :

**Fichier : `.cursor/mcp.json`** (ou équivalent selon votre client MCP)
```json
{
    "mcpServers": {
        "mysql": {
            "type": "stdio",
            "command": "php",
            "args": ["/chemin/absolu/vers/mysql/server.php"],
            "env": {
                "MYSQL_HOST": "127.0.0.1",
                "MYSQL_PORT": "3306",
                "MYSQL_USER": "your_user",
                "MYSQL_PASS": "your_password",
                "MYSQL_DB": "your_database"
            }
        }
    }
}
```

> **💡 Explication des paramètres MCP :**
> - **`type: "stdio"`** : Transport MCP via stdin/stdout (standard pour les serveurs locaux)
> - **`command`** : Commande pour lancer le serveur (ici PHP)
> - **`args`** : Arguments passés à la commande (chemin vers le script)
> - **`env`** : Variables d'environnement (credentials MySQL, permissions, etc.)

> **🔧 Astuce** : Remplacez `/chemin/absolu/vers/mysql/server.php` par le chemin complet vers votre serveur.

### Étape 6 : Démarrage et Test (1 min)

```bash
# Test final du serveur
php test_mcp_server.php
```

**✅ Résultat attendu :**
```
🧪 Test du serveur MCP MySQL...
✅ Serveur MCP MySQL initialisé
✅ Connexion MySQL validée
🎯 Le serveur est prêt à être utilisé !
```

## 🎉 Premier Test avec Claude Code

### Redémarrez Claude Code
Après avoir modifié la configuration MCP, redémarrez Claude Code pour charger le serveur.

### Vos Premières Requêtes

Testez ces commandes dans Claude Code :

#### 1. **Lister les Bases de Données**
```
Peux-tu me montrer toutes les bases de données disponibles ?
```
**Résultat attendu :** Claude utilisera `mysql_list_databases`

#### 2. **Explorer les Tables**
```
Quelles tables sont disponibles dans ma base de données ?
```
**Résultat attendu :** Claude utilisera `mysql_list_tables`

#### 3. **Structure d'une Table**
```
Montre-moi la structure de la table users
```
**Résultat attendu :** Claude utilisera `mysql_describe_table`

#### 4. **Première Requête**
```
Récupère tous les utilisateurs qui ont plus de 30 ans
```
**Résultat attendu :** Claude utilisera `mysql_select` avec la requête appropriée

#### 5. **Statistiques**
```
Combien de commandes chaque utilisateur a-t-il passées ?
```

## 🔍 Validation du Fonctionnement

### Indicateurs de Succès

#### ✅ **Serveur Opérationnel**
- Les commandes de test réussissent
- Aucune erreur dans les logs
- Connexion MySQL stable

#### ✅ **Claude Code Intégré**
- Claude propose les outils MySQL automatiquement
- Les requêtes s'exécutent sans erreur
- Les résultats sont cohérents

#### ✅ **Données Disponibles**
- 5 utilisateurs test créés
- 9 commandes avec relations
- 6 catégories hiérarchiques

### Debug Rapide

#### Si ça ne marche pas :

**1. Problème de connexion :**
```bash
# Test manuel de MySQL
mysql -h 127.0.0.1 -P 3306 -u your_user -p your_database
```

**2. Serveur MCP ne démarre pas :**
```bash
# Logs détaillés
LOG_LEVEL=DEBUG php server.php
```

**3. Claude Code ne voit pas le serveur :**
- Vérifiez le chemin absolu dans la config
- Redémarrez Claude Code
- Consultez les logs de Claude Code

## 🚀 Prêt pour la Suite !

### Félicitations ! Vous avez maintenant :
- ✅ Un serveur MCP MySQL fonctionnel
- ✅ Des données de test pour expérimenter
- ✅ Claude Code configuré et opérationnel
- ✅ Les compétences pour déboguer les problèmes

### Prochaines Étapes Recommandées

#### 🔒 **Sécuriser votre Installation**
- Consultez le guide [Sécurité](./security.md)
- Ajustez les permissions selon vos besoins
- Créez un utilisateur MySQL dédié

#### 📚 **Approfondir vos Connaissances**
- Explorez tous les [Outils MCP](./mcp-tools.md)
- Lisez les [Exemples d'Usage](./examples.md)
- Découvrez les [Variables d'Environnement](./environment-variables.md)

#### 🏢 **Configuration Multi-Projets**
- Guide [Configuration Multi-Projets](./multi-project-setup.md)
- Organisez vos différents environnements
- Gérez les permissions par projet

## 💡 Exemples Concrets pour Commencer

### Requêtes Business Courantes

```sql
-- Utilisateurs actifs récents
SELECT * FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)

-- Commandes par statut
SELECT status, COUNT(*) as count FROM orders GROUP BY status

-- Top produits commandés
SELECT product_name, SUM(quantity) as total_sold 
FROM orders 
GROUP BY product_name 
ORDER BY total_sold DESC 
LIMIT 5

-- Revenus par utilisateur
SELECT u.name, SUM(o.price * o.quantity) as total_revenue
FROM users u
JOIN orders o ON u.id = o.user_id
GROUP BY u.id
```

### Cas d'Usage avec Claude

```
Analyse des ventes :
"Montre-moi les 5 produits les plus vendus et calcule le chiffre d'affaires total"

Gestion des utilisateurs :
"Trouve tous les utilisateurs qui n'ont jamais passé de commande"

Analyse des tendances :
"Quels sont les statuts de commandes les plus fréquents ?"
```

## 🎯 Check-list de Validation

- [ ] `php test_connection.php` réussit
- [ ] `php setup_test_data.php` crée les données
- [ ] `php test_mcp_server.php` valide le serveur
- [ ] Configuration MCP ajoutée et chemin correct
- [ ] Claude Code redémarré
- [ ] Première requête réussie dans Claude Code
- [ ] Données de test visibles et cohérentes

**Tout est coché ? Parfait ! Vous maîtrisez maintenant les bases du serveur MCP MySQL !** 🎊

---

**Prêt pour plus d'avanced ? Consultez [Exemples d'Usage](./examples.md) !** 🚀