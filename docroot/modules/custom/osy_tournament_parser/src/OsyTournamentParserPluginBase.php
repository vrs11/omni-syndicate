<?php

declare(strict_types=1);

namespace Drupal\osy_tournament_parser;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for osy_tournament_parser plugins.
 */
abstract class OsyTournamentParserPluginBase extends PluginBase implements OsyTournamentParserPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

}
