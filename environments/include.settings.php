<?php

$settings['config_sync_directory'] = '../sync';
$settings['hash_salt'] = $_ENV['HASH_SALT'];

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';

/**
 * Set environment indicator.
 */

if (isset($_SERVER['ENVIRONMENT_INDICATOR'])) {
  $environment = $_SERVER['ENVIRONMENT_INDICATOR'];
}

/**
 * Include settings.
 */
include __DIR__ . "/$environment/settings.php";
