<?php return array (
  'debug' => true,
  'url' => 'http://localhost:8080',
  'paths' => 
  array (
    'api' => 'api',
    'admin' => 'admin',
  ),
  'database' => 
  array (
    'driver' => 'mysql',
    'host' => 'flarum-db',
    'port' => 3306,
    'database' => 'kktc_meydan_db',
    'username' => 'kktc_user',
    'password' => 'kktc_user_secret',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => false,
    'engine' => 'InnoDB',
    'sslmode' => 'prefer',
  ),
  'headers' => 
  array (
    'poweredByHeader' => false,
    'referrerPolicy' => 'same-origin',
  ),
);
