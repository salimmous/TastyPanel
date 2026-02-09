-- ============================================
-- TastyPanel Platform - MySQL Database Setup
-- ============================================

-- 1. Create Platform Database
CREATE DATABASE IF NOT EXISTS tastybox_platform 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

-- 2. Create Platform Admin User
CREATE USER IF NOT EXISTS 'tastybox_admin'@'localhost' 
    IDENTIFIED BY 'CHANGE_THIS_PASSWORD';

-- 3. Grant Privileges
GRANT ALL PRIVILEGES ON tastybox_platform.* 
    TO 'tastybox_admin'@'localhost';

-- 4. Grant ability to create tenant databases
GRANT CREATE, DROP ON *.* 
    TO 'tastybox_admin'@'localhost';

-- 5. Apply privileges
FLUSH PRIVILEGES;

-- 6. Verify
USE tastybox_platform;
SELECT 'Platform database created successfully!' as status;
