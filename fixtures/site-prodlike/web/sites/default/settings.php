<?php

/**
 * @file
 * Fixture settings.php pointing at a shared, non-local database.
 */

$databases['default']['default'] = [
  'database' => 'acme_www',
  'username' => 'acme_app',
  'password' => getenv('DB_PASSWORD') ?: '',
  'host' => 'db-primary.internal.acme.example',
  'driver' => 'mysql',
  'prefix' => '',
];
$settings['hash_salt'] = getenv('HASH_SALT') ?: '';
$settings['config_sync_directory'] = '../config/sync';
$settings['trusted_host_patterns'] = ['^www\.acme\.example$', '^acme\.example$'];
if (file_exists($app_root . '/' . $site_path . '/settings.production.php')) {
  include $app_root . '/' . $site_path . '/settings.production.php';
}
