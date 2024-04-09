<?php

namespace Drupal\osy_tournament_parser\Feeds\Parser;

use Drupal\Component\Serialization\Json;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Feeds\Parser\ParserBase;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\StateInterface;
use Drupal\osy_tournament_parser\OsyTournamentParserPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a CSV feed parser.
 *
 * @FeedsParser(
 *   id = "osy_tournament_parser",
 *   title = "Tournament parser",
 *   description = @Translation("Parse tournaments."),
 * )
 */
class TournamentParser extends ParserBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    protected FileSystemInterface $fileSystem,
    protected OsyTournamentParserPluginManager $tournamentParserPluginManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system'),
      $container->get('osy_tournament_parser.plugin.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    $states = $fetcher_result->getStates();
    if (
      empty($states['tournaments'])
      || empty($sink = $fetcher_result->getFilePath())
    ) {
      throw new EmptyFeedException();
    }

    $total = count($states['tournaments']);
    $pointer =  $state->pointer ?? 0;
    $keys = array_keys($states['tournaments']);

    $result = new ParserResult();
    for (; $pointer < $total; $pointer++) {
      $tid = $keys[$pointer];
      if (
        empty($content = file_get_contents("{$sink}/tournament_{$tid}.json"))
        || empty($data = Json::decode($content))
      ) {
        continue;
      }

      $item = new DynamicItem();
      foreach ($data as $key => $value) {
        $item->set($key, $value);
      }
      $item->set('sink_path', $sink);

      $result->addItem($item);
    }

    // Report progress.
    $state->total = $total;
    $state->pointer = $pointer;
    $state->progress($state->total, $state->pointer);

    if (!$result->count()) {
      $state->setCompleted();
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedCustomSourcePlugins(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultFeedConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

}
