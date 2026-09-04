<?php

/**
 * @file
 * Fixture settings.php (local development).
 */

$databases['default']['default'] = [
  'database' => 'db',
  'username' => 'db',
  'password' => 'db',
  'host' => 'db',
  'driver' => 'mysql',
  'prefix' => '',
];
$settings['hash_salt'] = 'fixture-not-a-secret';
$settings['config_sync_directory'] = '../config/sync';
$settings['trusted_host_patterns'] = ['^localhost$', '^.+\.ddev\.site$'];
