#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use MySqlMcp\Services\ConnectionService;

/**
 * Script de création de tables et données de test
 */

try {
    echo "🗄️ Configuration du serveur de test MySQL...\n";
    
    // Configuration pour le serveur de test
    $config = [
        'MYSQL_HOST' => '127.0.0.1',
        'MYSQL_PORT' => 33099,
        'MYSQL_USER' => 'mcpusertest',
        'MYSQL_PASS' => 'tototugoi',
        'MYSQL_DB' => 'mcptesttable',
        'CONNECTION_POOL_SIZE' => 1
    ];
    
    $connectionService = ConnectionService::getInstance($config);
    
    echo "✅ Connexion établie au serveur MySQL\n";
    
    $pdo = $connectionService->getConnection();
    
    // Création des tables de test
    echo "📋 Création des tables de test...\n";
    
    // Table des utilisateurs
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE NOT NULL,
            age INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Table des commandes
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_name VARCHAR(200) NOT NULL,
            quantity INT DEFAULT 1,
            price DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
            order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_order_date (order_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Table des catégories
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            parent_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
            INDEX idx_name (name),
            INDEX idx_parent_id (parent_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    echo "✅ Tables créées avec succès\n";
    
    // Insertion de données de test
    echo "📊 Insertion des données de test...\n";
    
    // Suppression des données existantes (dans l'ordre correct pour les FK)
    $pdo->exec("DELETE FROM orders");
    $pdo->exec("DELETE FROM users");
    $pdo->exec("DELETE FROM categories");
    
    // Reset auto_increment
    $pdo->exec("ALTER TABLE users AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE orders AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE categories AUTO_INCREMENT = 1");
    
    // Insertion des utilisateurs
    $users = [
        ['Alice Martin', 'alice@example.com', 28],
        ['Bob Dupont', 'bob@example.com', 35],
        ['Charlie Bernard', 'charlie@example.com', 42],
        ['Diana Rousseau', 'diana@example.com', 31],
        ['Eve Lambert', 'eve@example.com', 26]
    ];
    
    $userStmt = $pdo->prepare("INSERT INTO users (name, email, age) VALUES (?, ?, ?)");
    foreach ($users as $user) {
        $userStmt->execute($user);
    }
    
    // Insertion des catégories
    $categories = [
        ['Électronique', 'Appareils et gadgets électroniques', null],
        ['Vêtements', 'Articles d\'habillement', null],
        ['Livres', 'Livres et publications', null],
        ['Smartphones', 'Téléphones portables', 1], // Parent: Électronique
        ['Ordinateurs', 'PC et laptops', 1], // Parent: Électronique
        ['Romans', 'Livres de fiction', 3], // Parent: Livres
    ];
    
    $categoryStmt = $pdo->prepare("INSERT INTO categories (name, description, parent_id) VALUES (?, ?, ?)");
    foreach ($categories as $category) {
        $categoryStmt->execute($category);
    }
    
    // Insertion des commandes
    $orders = [
        [1, 'iPhone 15', 1, 999.99, 'completed'],
        [1, 'MacBook Pro', 1, 1999.00, 'processing'],
        [2, 'T-shirt Rouge', 2, 29.99, 'completed'],
        [2, 'Jean Bleu', 1, 79.50, 'pending'],
        [3, 'Le Petit Prince', 1, 15.99, 'completed'],
        [3, 'Smartphone Android', 1, 599.99, 'cancelled'],
        [4, 'Robe Noire', 1, 89.99, 'processing'],
        [5, 'Laptop Dell', 1, 1299.00, 'pending'],
        [5, 'Casque Audio', 1, 199.99, 'completed'],
    ];
    
    $orderStmt = $pdo->prepare("INSERT INTO orders (user_id, product_name, quantity, price, status) VALUES (?, ?, ?, ?, ?)");
    foreach ($orders as $order) {
        $orderStmt->execute($order);
    }
    
    echo "✅ Données de test insérées avec succès\n";
    
    // Affichage des statistiques
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $orderCount = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $categoryCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    
    echo "\n📈 Statistiques des données de test :\n";
    echo "   👥 Utilisateurs : {$userCount}\n";
    echo "   📦 Commandes : {$orderCount}\n";
    echo "   🏷️ Catégories : {$categoryCount}\n";
    
    // Exemple de requêtes possibles
    echo "\n💡 Exemples de requêtes à tester :\n";
    echo "   • SELECT * FROM users WHERE age > 30\n";
    echo "   • SELECT u.name, COUNT(o.id) as order_count FROM users u LEFT JOIN orders o ON u.id = o.user_id GROUP BY u.id\n";
    echo "   • SELECT * FROM orders WHERE status = 'pending' ORDER BY order_date DESC\n";
    echo "   • SELECT c1.name as category, c2.name as subcategory FROM categories c1 LEFT JOIN categories c2 ON c1.id = c2.parent_id WHERE c1.parent_id IS NULL\n";
    
    $connectionService->releaseConnection($pdo);
    
    echo "\n🎉 Configuration des données de test terminée !\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "Stack trace :\n" . $e->getTraceAsString() . "\n";
    exit(1);
}