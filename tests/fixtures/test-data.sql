-- PostgreSQL test data insertion
-- Inserts sample data for testing

-- Insert test users
INSERT INTO users (username, email, full_name, is_active, metadata) VALUES
    ('john_doe', 'john@example.com', 'John Doe', true, '{"role": "admin", "preferences": {"theme": "dark"}}'),
    ('jane_smith', 'jane@example.com', 'Jane Smith', true, '{"role": "user", "preferences": {"theme": "light"}}'),
    ('bob_wilson', 'bob@example.com', 'Bob Wilson', false, '{"role": "user", "suspended": true}'),
    ('alice_jones', 'alice@example.com', 'Alice Jones', true, '{"role": "moderator"}'),
    ('charlie_brown', 'charlie@example.com', 'Charlie Brown', true, '{"role": "user", "verified": true}');

-- Insert test products with PostgreSQL-specific features
INSERT INTO products (name, description, price, stock_quantity, category, tags, specifications) VALUES
    ('Laptop Pro', 'High-performance laptop', 1299.99, 50, 'Electronics', 
     ARRAY['computers', 'portable', 'business'], 
     '{"cpu": "Intel i7", "ram": "16GB", "storage": "512GB SSD", "display": {"size": 15.6, "resolution": "1920x1080"}}'),
    
    ('Wireless Mouse', 'Ergonomic wireless mouse', 29.99, 200, 'Electronics',
     ARRAY['accessories', 'wireless', 'ergonomic'],
     '{"connectivity": "Bluetooth 5.0", "battery": "AA", "dpi": 1600}'),
    
    ('Office Chair', 'Comfortable office chair', 249.99, 30, 'Furniture',
     ARRAY['office', 'ergonomic', 'adjustable'],
     '{"material": "Mesh", "weight_capacity": "150kg", "adjustable": ["height", "armrest", "backrest"]}'),
    
    ('USB-C Cable', '2m USB-C charging cable', 19.99, 500, 'Electronics',
     ARRAY['accessories', 'cables', 'charging'],
     '{"length": "2m", "type": "USB-C to USB-C", "power_delivery": "100W"}'),
    
    ('Standing Desk', 'Electric height-adjustable desk', 599.99, 15, 'Furniture',
     ARRAY['office', 'ergonomic', 'electric'],
     '{"dimensions": {"width": 160, "depth": 80, "height_range": [70, 120]}, "motor": "dual", "memory_presets": 4}');

-- Insert test orders
INSERT INTO orders (user_id, product_id, quantity, total_price, status) VALUES
    (1, 1, 1, 1299.99, 'completed'),
    (1, 2, 2, 59.98, 'completed'),
    (2, 3, 1, 249.99, 'shipped'),
    (2, 4, 3, 59.97, 'pending'),
    (4, 1, 1, 1299.99, 'processing'),
    (5, 5, 1, 599.99, 'completed'),
    (1, 4, 5, 99.95, 'completed');

-- Insert test permissions
INSERT INTO test_permissions (resource, action, allowed) VALUES
    ('users', 'read', true),
    ('users', 'write', false),
    ('products', 'read', true),
    ('products', 'write', true),
    ('orders', 'read', true),
    ('orders', 'write', false),
    ('orders', 'delete', false);

-- Create some test sequences
CREATE SEQUENCE IF NOT EXISTS test_sequence START 1000;
CREATE SEQUENCE IF NOT EXISTS invoice_number START 2024001;

-- Insert data using PostgreSQL-specific features
-- Example of using RETURNING clause (will be tested in actual tests)
-- INSERT INTO users (username, email, full_name) 
-- VALUES ('test_return', 'return@example.com', 'Test Return')
-- RETURNING id, username, created_at;

-- Create a materialized view for testing
CREATE MATERIALIZED VIEW IF NOT EXISTS product_statistics AS
SELECT 
    p.category,
    COUNT(*) as product_count,
    AVG(p.price) as avg_price,
    MIN(p.price) as min_price,
    MAX(p.price) as max_price,
    SUM(p.stock_quantity) as total_stock
FROM products p
GROUP BY p.category
WITH DATA;

-- Refresh the materialized view
REFRESH MATERIALIZED VIEW product_statistics;

-- Add some test comments for documentation
COMMENT ON TABLE users IS 'Test users table for MCP PostgreSQL server';
COMMENT ON COLUMN users.metadata IS 'JSONB column storing user preferences and settings';
COMMENT ON TABLE products IS 'Test products table with PostgreSQL-specific types';
COMMENT ON COLUMN products.tags IS 'Array of text tags for categorization';
COMMENT ON COLUMN products.specifications IS 'JSONB column for flexible product specs';