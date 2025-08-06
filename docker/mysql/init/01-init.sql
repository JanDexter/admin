-- Initialize database with proper settings
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Create additional user for application
CREATE USER IF NOT EXISTS 'laravel'@'%' IDENTIFIED BY 'laravel_password';
GRANT ALL PRIVILEGES ON admin_dashboard.* TO 'laravel'@'%';

-- Optimize settings
SET GLOBAL innodb_buffer_pool_size = 268435456;
SET GLOBAL query_cache_size = 268435456;
SET GLOBAL max_connections = 200;

FLUSH PRIVILEGES;
