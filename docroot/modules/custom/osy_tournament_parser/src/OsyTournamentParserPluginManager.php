<?php

declare(strict_types=1);

namespace Drupal\osy_tournament_parser;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\osy_tournament_parser\Annotation\OsyTournamentParser;

/**
 * OsyTournamentParser plugin manager.
 */
final class OsyTournamentParserPluginManager extends DefaultPluginManager {

  /**
   * Constructs the object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/OsyTournamentParser', $namespaces, $module_handler, OsyTournamentParserPluginInterface::class, OsyTournamentParser::class);
    $this->alterInfo('osy_tournament_parser_info');
    $this->setCacheBackend($cache_backend, 'osy_tournament_parser_plugins');
  }

}
