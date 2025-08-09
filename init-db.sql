-- Script d'initialisation de la base de données StacGateLMS
-- Exécuté automatiquement lors de la création du conteneur PostgreSQL

-- Création de la base de données (déjà fait par POSTGRES_DB)
-- CREATE DATABASE stacgatelms;

-- Configuration des extensions utiles
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- Configuration des paramètres de performance
ALTER SYSTEM SET shared_preload_libraries = 'pg_stat_statements';
ALTER SYSTEM SET max_connections = 200;
ALTER SYSTEM SET shared_buffers = '256MB';
ALTER SYSTEM SET effective_cache_size = '1GB';
ALTER SYSTEM SET maintenance_work_mem = '64MB';
ALTER SYSTEM SET checkpoint_completion_target = 0.9;
ALTER SYSTEM SET wal_buffers = '16MB';
ALTER SYSTEM SET default_statistics_target = 100;
ALTER SYSTEM SET random_page_cost = 1.1;
ALTER SYSTEM SET effective_io_concurrency = 200;

-- Création d'un utilisateur avec privilèges limités pour l'application
-- CREATE USER stacgate_app WITH PASSWORD 'app_password';
-- GRANT CONNECT ON DATABASE stacgatelms TO stacgate_app;
-- GRANT USAGE ON SCHEMA public TO stacgate_app;
-- GRANT CREATE ON SCHEMA public TO stacgate_app;

-- Message de confirmation
SELECT 'Base de données StacGateLMS initialisée avec succès!' AS message;