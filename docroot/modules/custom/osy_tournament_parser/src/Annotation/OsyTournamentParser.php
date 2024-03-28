<?php

declare(strict_types=1);

namespace Drupal\osy_tournament_parser\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines osy_tournament_parser annotation object.
 *
 * @Annotation
 */
class OsyTournamentParser extends Plugin {

  /**
   * The plugin ID.
   */
  public readonly string $id;

  /**
   * The human-readable name of the plugin.
   *
   * @ingroup plugin_translatable
   */
  public readonly string $title;

  /**
   * The description of the plugin.
   *
   * @ingroup plugin_translatable
   */
  public readonly string $description;

}
