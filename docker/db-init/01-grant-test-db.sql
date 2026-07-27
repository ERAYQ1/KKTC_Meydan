-- `kktc_user` is only auto-granted access to MYSQL_DATABASE
-- (kktc_meydan_db) by MariaDB's own entrypoint. The separate
-- kktc_meydan_test database (created by `composer test:setup`, see
-- README's "Test ve doğrulama") needs its own explicit grant, or PHPUnit
-- fails with "Access denied for user 'kktc_user'@'%' to database
-- 'kktc_meydan_test'" on a fresh clone/CI run.
--
-- Runs once, only on first container start against an empty data
-- directory (docker-entrypoint-initdb.d convention) - an already
-- initialized volume won't re-run this.
CREATE DATABASE IF NOT EXISTS kktc_meydan_test;
GRANT ALL PRIVILEGES ON kktc_meydan_test.* TO 'kktc_user'@'%';
FLUSH PRIVILEGES;
