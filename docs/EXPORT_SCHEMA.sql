-- ============================================================================
-- SQL скрипт для экспорта структуры базы данных WebAPILaborService
-- Используется для документирования и анализа структуры БД
-- ============================================================================

-- ============================================================================
-- 1. ТАБЛИЦА: users (Пользователи системы)
-- ============================================================================
SELECT 
    'users' as table_name,
    column_name,
    data_type,
    is_nullable,
    column_default,
    character_maximum_length
FROM information_schema.columns
WHERE table_name = 'users'
ORDER BY ordinal_position;

-- ============================================================================
-- 2. ТАБЛИЦА: sessions (Сессии пользователей)
-- ============================================================================
SELECT 
    'sessions' as table_name,
    column_name,
    data_type,
    is_nullable,
    column_default,
    character_maximum_length
FROM information_schema.columns
WHERE table_name = 'sessions'
ORDER BY ordinal_position;

-- ============================================================================
-- 3. ТАБЛИЦА: password_reset_tokens (Токены сброса пароля)
-- ============================================================================
SELECT 
    'password_reset_tokens' as table_name,
    column_name,
    data_type,
    is_nullable,
    column_default,
    character_maximum_length
FROM information_schema.columns
WHERE table_name = 'password_reset_tokens'
ORDER BY ordinal_position;

-- ============================================================================
-- 4. ТАБЛИЦА: people (Сотрудники)
-- ============================================================================
SELECT 
    'people' as table_name,
    column_name,
    data_type,
    is_nullable,
    column_default,
    character_maximum_length
FROM information_schema.columns
WHERE table_name = 'people'
ORDER BY ordinal_position;

-- ============================================================================
-- 5. ТАБЛИЦА: certificates (Типы удостоверений)
-- ============================================================================
SELECT 
    'certificates' as table_name,
    column_name,
    data_type,
    is_nullable,
    column_default,
    character_maximum_length
FROM information_schema.columns
WHERE table_name = 'certificates'
ORDER BY ordinal_position;

-- ============================================================================
-- 6. ТАБЛИЦА: people_certificates (Удостоверения сотрудников)
-- ============================================================================
SELECT 
    'people_certificates' as table_name,
    column_name,
    data_type,
    is_nullable,
    column_default,
    character_maximum_length
FROM information_schema.columns
WHERE table_name = 'people_certificates'
ORDER BY ordinal_position;

-- ============================================================================
-- 7. ТАБЛИЦА: certificate_orders (Порядок отображения сертификатов)
-- ============================================================================
SELECT 
    'certificate_orders' as table_name,
    column_name,
    data_type,
    is_nullable,
    column_default,
    character_maximum_length
FROM information_schema.columns
WHERE table_name = 'certificate_orders'
ORDER BY ordinal_position;

-- ============================================================================
-- 8. ТАБЛИЦА: api_tokens (API токены)
-- ============================================================================
SELECT 
    'api_tokens' as table_name,
    column_name,
    data_type,
    is_nullable,
    column_default,
    character_maximum_length
FROM information_schema.columns
WHERE table_name = 'api_tokens'
ORDER BY ordinal_position;

-- ============================================================================
-- ВНЕШНИЕ КЛЮЧИ (Foreign Keys)
-- ============================================================================
SELECT
    tc.table_name,
    kcu.column_name,
    ccu.table_name AS foreign_table_name,
    ccu.column_name AS foreign_column_name,
    tc.constraint_name
FROM information_schema.table_constraints AS tc
JOIN information_schema.key_column_usage AS kcu
    ON tc.constraint_name = kcu.constraint_name
    AND tc.table_schema = kcu.table_schema
JOIN information_schema.constraint_column_usage AS ccu
    ON ccu.constraint_name = tc.constraint_name
    AND ccu.table_schema = tc.table_schema
WHERE tc.constraint_type = 'FOREIGN KEY'
    AND tc.table_schema = 'public'
ORDER BY tc.table_name, kcu.column_name;

-- ============================================================================
-- УНИКАЛЬНЫЕ ОГРАНИЧЕНИЯ (Unique Constraints)
-- ============================================================================
SELECT
    tc.table_name,
    kcu.column_name,
    tc.constraint_name
FROM information_schema.table_constraints AS tc
JOIN information_schema.key_column_usage AS kcu
    ON tc.constraint_name = kcu.constraint_name
    AND tc.table_schema = kcu.table_schema
WHERE tc.constraint_type = 'UNIQUE'
    AND tc.table_schema = 'public'
ORDER BY tc.table_name, kcu.column_name;

-- ============================================================================
-- ИНДЕКСЫ
-- ============================================================================
SELECT
    tablename,
    indexname,
    indexdef
FROM pg_indexes
WHERE schemaname = 'public'
ORDER BY tablename, indexname;

-- ============================================================================
-- СТАТИСТИКА ПО ТАБЛИЦАМ
-- ============================================================================
SELECT
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size,
    pg_total_relation_size(schemaname||'.'||tablename) AS size_bytes
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;


