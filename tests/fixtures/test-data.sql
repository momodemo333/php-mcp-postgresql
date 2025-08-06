-- Test database initialization
-- This file is loaded when the test MySQL container starts

-- Create test database and tables
CREATE DATABASE IF NOT EXISTS testdb;
USE testdb;

-- Test table for basic CRUD operations
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Test table for complex queries and joins
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Test table for permissions and security testing
CREATE TABLE sensitive_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    secret_value VARCHAR(255) NOT NULL,
    access_level ENUM('public', 'private', 'confidential') DEFAULT 'private'
);

-- Insert test data
INSERT INTO users (name, email) VALUES 
    ('John Doe', 'john@example.com'),
    ('Jane Smith', 'jane@example.com'),
    ('Bob Wilson', 'bob@example.com');

INSERT INTO posts (user_id, title, content, status) VALUES 
    (1, 'First Post', 'This is the first post content', 'published'),
    (1, 'Draft Post', 'This is a draft', 'draft'),
    (2, 'Jane Post', 'Content by Jane', 'published'),
    (3, 'Bob Article', 'Article by Bob', 'archived');

INSERT INTO sensitive_data (secret_value, access_level) VALUES 
    ('public_info', 'public'),
    ('private_data', 'private'),
    ('top_secret', 'confidential');

-- Test schema for DDL operations testing
CREATE TABLE test_ddl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data VARCHAR(50)
);

-- Grant permissions to test user
GRANT SELECT, INSERT, UPDATE, DELETE ON testdb.* TO 'testuser'@'%';
GRANT CREATE, ALTER, DROP ON testdb.test_ddl TO 'testuser'@'%';
FLUSH PRIVILEGES;