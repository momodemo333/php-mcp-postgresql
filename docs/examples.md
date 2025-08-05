# 💡 Exemples d'Usage

Guide pratique avec des exemples concrets d'utilisation du serveur MCP MySQL dans Claude Code.

## 🎯 Scénarios par Rôle

### 👨‍💻 Développeur - Analyse de Base de Données

#### **Exploration Initiale**
```
Claude, peux-tu m'aider à explorer cette base de données ? 
Commence par me montrer toutes les tables disponibles.
```

**→ Claude utilisera :** `mysql_list_tables`

**Résultat attendu :**
```
Je vais explorer votre base de données. Voici les tables disponibles :

📊 Tables trouvées (5 tables) :
• users (1,250 lignes, 98 KB)
• orders (3,456 lignes, 256 KB) 
• products (89 lignes, 15 KB)
• categories (12 lignes, 2 KB)
• user_sessions (15,678 lignes, 1.2 MB)
```

#### **Analyse de Structure**
```
Montre-moi la structure détaillée de la table users, 
j'ai besoin de comprendre les relations et les index.
```

**→ Claude utilisera :** `mysql_describe_table`

**Résultat attendu :**
```
Structure de la table 'users' :

📋 Colonnes (6) :
• id (int, PK, auto_increment)
• name (varchar(100), NOT NULL)
• email (varchar(150), UNIQUE, NOT NULL)
• age (int, nullable)
• created_at (timestamp, défaut: CURRENT_TIMESTAMP)
• updated_at (timestamp, auto-update)

🔑 Index :
• PRIMARY: id (unique)
• idx_email: email (unique)
• idx_name: name

🔗 Relations : Aucune clé étrangère sortante
```

### 📊 Analyste Business - Reporting

#### **Analyse des Ventes**
```
J'aimerais analyser nos ventes. Montre-moi :
1. Le nombre total de commandes par statut
2. Le chiffre d'affaires des 30 derniers jours
3. Les 5 produits les plus vendus
```

**→ Claude utilisera :** `mysql_select` (plusieurs requêtes)

**Exemple de requêtes générées :**
```sql
-- 1. Commandes par statut
SELECT status, COUNT(*) as count, 
       ROUND(AVG(price), 2) as avg_price
FROM orders 
GROUP BY status
ORDER BY count DESC

-- 2. CA des 30 derniers jours
SELECT DATE(order_date) as date, 
       SUM(price * quantity) as daily_revenue
FROM orders 
WHERE order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(order_date)
ORDER BY date DESC

-- 3. Top 5 produits
SELECT product_name, 
       SUM(quantity) as total_sold,
       SUM(price * quantity) as revenue
FROM orders 
GROUP BY product_name 
ORDER BY total_sold DESC 
LIMIT 5
```

#### **Segmentation Clients**
```
Aide-moi à segmenter nos clients selon leur activité :
- Clients VIP (>5 commandes ou >1000€ dépensés)
- Clients réguliers (2-5 commandes)
- Nouveaux clients (1 commande)
- Clients inactifs (inscrits mais aucune commande)
```

**→ Claude utilisera :** `mysql_select` avec des jointures complexes

### 🏢 Administrateur - Maintenance

#### **Analyse de Performance**
```
J'ai des problèmes de performance. Peux-tu :
1. Vérifier l'état du serveur MySQL
2. Identifier les tables les plus volumineuses
3. Suggérer des optimisations
```

**→ Claude utilisera :** `mysql_server_status` + `mysql_list_tables`

#### **Nettoyage de Données**
```
J'ai besoin de nettoyer les données obsolètes :
- Supprimer les sessions expirées (older than 7 days)
- Nettoyer les commandes annulées de plus de 6 mois
```

**→ Claude utilisera :** `mysql_select` (vérification) puis `mysql_delete`

---

## 🚀 Cas d'Usage Avancés

### 🔍 Investigation de Bug

#### **Problème : Commandes Dupliquées**
```
J'ai un problème de commandes dupliquées. Peux-tu :
1. Identifier s'il y a des doublons dans la table orders
2. Analyser quand ces doublons sont apparus
3. Me montrer les utilisateurs affectés
```

**Workflow Claude :**
```sql
-- 1. Détection des doublons
SELECT user_id, product_name, order_date, COUNT(*) as duplicates
FROM orders 
GROUP BY user_id, product_name, DATE(order_date)
HAVING COUNT(*) > 1

-- 2. Analyse temporelle
SELECT DATE(order_date) as date, COUNT(*) as total_orders,
       COUNT(DISTINCT CONCAT(user_id, product_name)) as unique_orders
FROM orders 
GROUP BY DATE(order_date)
HAVING total_orders > unique_orders

-- 3. Utilisateurs affectés
SELECT DISTINCT u.name, u.email, COUNT(o.id) as duplicate_orders
FROM users u
JOIN orders o ON u.id = o.user_id
WHERE (u.id, o.product_name, DATE(o.order_date)) IN (
    SELECT user_id, product_name, DATE(order_date)
    FROM orders 
    GROUP BY user_id, product_name, DATE(order_date)
    HAVING COUNT(*) > 1
)
GROUP BY u.id
```

### 📈 Analyse de Croissance

#### **Tracking de Métriques Business**
```
Prépare-moi un dashboard de métriques pour la présentation :
- Croissance mensuelle des inscriptions
- Évolution du panier moyen
- Taux de conversion (inscriptions → première commande)
- Analyse de rétention par cohorte
```

**Requêtes Claude générées :**
```sql
-- Croissance mensuelle
SELECT YEAR(created_at) as year, MONTH(created_at) as month,
       COUNT(*) as new_users,
       LAG(COUNT(*)) OVER (ORDER BY YEAR(created_at), MONTH(created_at)) as prev_month,
       ROUND((COUNT(*) - LAG(COUNT(*)) OVER (ORDER BY YEAR(created_at), MONTH(created_at))) * 100.0 / LAG(COUNT(*)) OVER (ORDER BY YEAR(created_at), MONTH(created_at)), 2) as growth_rate
FROM users 
GROUP BY YEAR(created_at), MONTH(created_at)
ORDER BY year DESC, month DESC

-- Panier moyen mensuel
SELECT YEAR(order_date) as year, MONTH(order_date) as month,
       ROUND(AVG(price * quantity), 2) as avg_order_value,
       COUNT(*) as total_orders
FROM orders 
GROUP BY YEAR(order_date), MONTH(order_date)
ORDER BY year DESC, month DESC

-- Taux de conversion
SELECT 
    COUNT(DISTINCT u.id) as total_users,
    COUNT(DISTINCT o.user_id) as users_with_orders,
    ROUND(COUNT(DISTINCT o.user_id) * 100.0 / COUNT(DISTINCT u.id), 2) as conversion_rate
FROM users u
LEFT JOIN orders o ON u.id = o.user_id
```

### 🛠️ Migration de Données

#### **Préparation de Migration**
```
Je dois migrer vers une nouvelle structure. Aide-moi à :
1. Analyser l'intégrité des données actuelles
2. Identifier les incohérences
3. Préparer les données pour la migration
```

**Workflow d'audit :**
```sql
-- Intégrité référentielle
SELECT 'Orphaned Orders' as issue, COUNT(*) as count
FROM orders o
LEFT JOIN users u ON o.user_id = u.id
WHERE u.id IS NULL

UNION ALL

SELECT 'Invalid Emails' as issue, COUNT(*) as count
FROM users 
WHERE email NOT REGEXP '^[^@]+@[^@]+\\.[^@]+$'

UNION ALL

SELECT 'Negative Quantities' as issue, COUNT(*) as count
FROM orders 
WHERE quantity <= 0

UNION ALL

SELECT 'Future Dates' as issue, COUNT(*) as count
FROM orders 
WHERE order_date > NOW()
```

---

## 🔧 Workflows de Développement

### 🧪 Tests et Validation

#### **Création de Données de Test**
```
J'ai besoin de créer des données de test pour mon environnement de dev. 
Peux-tu m'aider à créer :
- 10 utilisateurs avec des profils variés
- 50 commandes réparties sur les 3 derniers mois
- Assurer la cohérence des relations
```

**→ Claude utilisera :** `mysql_insert` de manière répétée

#### **Validation après Déploiement**
```
Je viens de déployer une nouvelle version. Peux-tu vérifier que :
1. Toutes les tables sont accessibles
2. Les données critiques sont cohérentes
3. Les performances sont dans les normes
```

### 🐛 Debug en Production

#### **Investigation d'Erreurs**
```
J'ai des erreurs 500 sur mon API users. Peux-tu m'aider à investiguer :
- Y a-t-il des utilisateurs avec des données corrompues ?
- Quels sont les derniers utilisateurs créés ?
- Y a-t-il des patterns dans les erreurs ?
```

**Stratégie d'investigation :**
```sql
-- Validation des données utilisateurs
SELECT id, name, email, age, created_at
FROM users 
WHERE email IS NULL 
   OR email = '' 
   OR name = '' 
   OR LENGTH(name) > 100
   OR age < 0 
   OR age > 150

-- Derniers utilisateurs (possibles problèmes récents)
SELECT * FROM users 
ORDER BY created_at DESC 
LIMIT 20

-- Analyse des patterns temporels
SELECT DATE(created_at) as date, COUNT(*) as registrations
FROM users 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC
```

---

## 📊 Rapports Automatisés

### 📈 Dashboard Quotidien

#### **Métriques Journalières**
```
Génère-moi le rapport quotidien avec :
- Nouvelles inscriptions (aujourd'hui vs hier)
- Commandes du jour (nombre et CA)
- Top 3 des produits vendus
- Alertes (commandes échouées, utilisateurs bloqués)
```

### 📅 Rapport Hebdomadaire

#### **Analyse de Tendances**
```
Prépare le rapport hebdomadaire :
- Évolution des KPIs vs semaine précédente
- Analyse de cohorts (rétention)
- Performance par canal d'acquisition
- Recommandations d'actions
```

---

## 🎭 Conversations Naturelles

### Exemples de Requêtes Naturelles

```
"Montre-moi nos meilleurs clients"
→ SELECT avec agrégation sur les commandes

"Y a-t-il des commandes bizarres récemment ?"
→ Analyse des anomalies (prix, quantités, dates)

"Comment évoluent nos ventes ce mois-ci ?"
→ Comparaison temporelle avec calculs de croissance

"Trouve-moi les utilisateurs qui n'ont pas commandé depuis 6 mois"
→ Jointure avec condition temporelle

"Quel est notre produit le plus rentable ?"
→ Calcul de marge par produit

"Y a-t-il des problèmes dans nos données ?"
→ Audit automatique d'intégrité
```

### Réponses Contextuelles de Claude

Claude adapte ses réponses selon le contexte :

**Pour un développeur :**
```
J'ai analysé votre base de données et trouvé 3 problèmes d'intégrité :
1. 12 commandes orphelines (user_id inexistant)
2. 5 emails invalides dans la table users
3. 2 commandes avec des quantités négatives

Voici les requêtes pour corriger ces problèmes...
```

**Pour un business analyst :**
```
Voici l'analyse de vos ventes du mois :

📊 Résumé :
• +15% de CA vs mois dernier (€125,430)
• +8% de nouvelles commandes (1,245)
• Panier moyen stable : €100.75

🔝 Top produits :
1. iPhone Cases (+25%) - €15,670
2. Wireless Chargers (+12%) - €8,940
3. Screen Protectors (-5%) - €7,230

⚠️ Points d'attention :
• Baisse des accessoires traditionnels
• Forte croissance mobile à maintenir
```

---

## 🔐 Exemples de Sécurité

### Configuration Restrictive (Production)

#### **Lecture Seule**
```bash
# .env production
ALLOW_INSERT_OPERATION=false
ALLOW_UPDATE_OPERATION=false
ALLOW_DELETE_OPERATION=false
MAX_RESULTS=50
QUERY_TIMEOUT=10
```

**Usage autorisé :**
```
"Montre-moi les ventes du jour"
→ ✅ SELECT autorisé

"Ajoute un nouveau client"
→ ❌ INSERT refusé par la configuration
```

### Configuration Développement

#### **Accès Complet**
```bash
# .env développement
ALLOW_INSERT_OPERATION=true
ALLOW_UPDATE_OPERATION=true
ALLOW_DELETE_OPERATION=true
MAX_RESULTS=1000
LOG_LEVEL=DEBUG
```

**Usage autorisé :**
```
"Crée quelques utilisateurs de test"
→ ✅ INSERT autorisé

"Corrige l'email de cet utilisateur"
→ ✅ UPDATE autorisé

"Supprime les données de test"
→ ✅ DELETE autorisé (avec conditions)
```

---

## 🚀 Pro Tips

### Optimisation des Requêtes

1. **Toujours spécifier des LIMIT** pour les gros datasets
2. **Utiliser des paramètres** pour les requêtes répétitives
3. **Créer des index** sur les colonnes fréquemment filtrées
4. **Surveiller execution_time_ms** pour identifier les requêtes lentes

### Bonnes Pratiques avec Claude

1. **Soyez spécifique** dans vos demandes
2. **Mentionnez les contraintes** (dates, limites, conditions)
3. **Demandez des explications** si les résultats semblent incorrects
4. **Utilisez le context** - référez-vous aux résultats précédents

### Debug et Monitoring

1. **Activez LOG_LEVEL=DEBUG** pendant le développement
2. **Surveillez mysql_server_status** régulièrement
3. **Documentez vos requêtes** complexes pour l'équipe
4. **Testez avec des données réalistes**

---

**Prêt à explorer plus en profondeur ? Consultez la [Référence Complète des Outils](./mcp-tools.md) !** 🎯