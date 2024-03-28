<?php

declare(strict_types=1);

namespace Drupal\osy_tournament_parser;

/**
 * Interface for osy_tournament_parser plugins.
 */
interface OsyTournamentParserPluginInterface {

  /**
   * Returns the translated plugin label.
   */
  public function label(): string;

  /**
   * Checks if this plugin can be applied to the link.
   */
  public function isCompatible(string $url): bool;

  /**
   * Gets the data from the URL
   */
  public function process(string $url): mixed;
}
