<?php

namespace Drupal\osy_tournament_parser\Feeds\Processor;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\feeds\Feeds\Processor\EntityProcessorBase;
use Drupal\osy_tournament_parser\OsyTournamentParserPluginManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;

/**
 * Defines a node processor.
 *
 * Creates nodes from feed items.
 *
 * @FeedsProcessor(
 *   id = "entity:node:tournament",
 *   title = @Translation("Tournament"),
 *   description = @Translation("Creates tournaments from feed items."),
 *   entity_type = "node",
 *   form = {
 *     "configuration" =
 *   "Drupal\feeds\Feeds\Processor\Form\DefaultEntityProcessorForm"
 *   },
 * )
 */
class TournamentProcessor extends EntityProcessorBase {

  /**
   * Loaded tournament parser plugins
   *
   * @var array
   */
  protected $plugins;

  /**
   * Constructs an EntityProcessorBase object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Component\Datetime\TimeInterface $date_time
   *   The datetime service for getting the system time.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $action_manager
   *   The action plugin manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger for the feeds channel.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   * @param \Drupal\osy_tournament_parser\OsyTournamentParserPluginManager $tournamentParserPluginManager
   *    The OsyTournamentParser plugin manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    LanguageManagerInterface $language_manager,
    TimeInterface $date_time,
    PluginManagerInterface $action_manager,
    RendererInterface $renderer,
    LoggerInterface $logger,
    Connection $database,
    protected OsyTournamentParserPluginManager $tournamentParserPluginManager
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager,
      $entity_type_bundle_info,
      $language_manager,
      $date_time,
      $action_manager,
      $renderer,
      $logger,
      $database
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = parent::defaultConfiguration();

    $defaults['values']['type'] = $this->bundle();

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('language_manager'),
      $container->get('datetime.time'),
      $container->get('plugin.manager.action'),
      $container->get('renderer'),
      $container->get('logger.factory')->get('feeds'),
      $container->get('database'),
      $container->get('osy_tournament_parser.plugin.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function entityLabel() {
    return $this->t('Tournament');
  }

  /**
   * {@inheritdoc}
   */
  public function entityLabelPlural() {
    return $this->t('Tournaments');
  }

  public function bundle() {
    return 'tournament_regular';
  }

  /**
   * Returns an existing entity id.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed being processed.
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to find existing ids for.
   *
   * @return int|null
   *   The ID of the entity, or null if not found.
   */
  protected function existingEntityId(FeedInterface $feed, ItemInterface $item) {
    $id = $item->get('tournament')['tournament']['id'];
    $key = $this->getPlugin($feed)->getKeyPrefix(":TOURNAMENT:{$id}");

    if (empty(
      $ids = $this->entityTypeManager->getStorage('node')
        ->getQuery()
        ->condition('type', 'tournament_regular')
        ->condition('field_original_reference_id', $key)
        ->accessCheck(FALSE)
        ->execute()
    )) {
      return NULL;
    }

    return reset($ids);
  }

  /**
   * Execute mapping on an item.
   *
   * This method encapsulates the central mapping functionality. When an item is
   * processed, it is passed through map() where the properties of $source_item
   * are mapped onto $target_item following the processor's mapping
   * configuration.
   */
  protected function map(FeedInterface $feed, EntityInterface $entity, ItemInterface $item) {
    return $this->getPlugin($feed)->map($feed, $entity, $item);
  }

  protected function getPlugin(FeedInterface $feed) {
    $feed_id = $feed->id();
    if (empty($this->plugins[$feed_id])) {
      [$plugin_id, $source] = explode('::', $feed->getSource());
      $this->plugins[$feed_id] = $this->tournamentParserPluginManager->createInstance($plugin_id);
    }

    return $this->plugins[$feed_id];
  }

}
