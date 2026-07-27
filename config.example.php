<?php return array (
  'debug' => false,
  'database' =>
  array (
    'driver' => 'mysql',
    'host' => 'flarum-db',
    'port' => 3306,
    'database' => 'kktc_meydan_db',
    'username' => 'kktc_user',
    'password' => 'CHANGE_ME_BEFORE_DEPLOY',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => 'InnoDB',
    'prefix_indexes' => true,
  ),
  'url' => 'http://localhost:8080',
  'paths' =>
  array (
    'api' => 'api',
    'admin' => 'admin',
  ),
  'flarum_announcements' =>
  array (
    'disabled' => true,
  ),
  'headers' =>
  array (
    'poweredByHeader' => false,
    'referrerPolicy' => 'same-origin',
  ),
);
