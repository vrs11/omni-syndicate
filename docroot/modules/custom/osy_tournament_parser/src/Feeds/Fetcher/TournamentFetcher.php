<?php

namespace Drupal\osy_tournament_parser\Feeds\Fetcher;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\File\FeedsFileSystemInterface;
use Drupal\feeds\Plugin\Type\ClearableInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\StateInterface;
use Drupal\osy_tournament_parser\OsyTournamentParserPluginManager;
use Drupal\osy_tournament_parser\Feeds\Result\TournamentsFetcherResult;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an HTTP fetcher.
 *
 * @FeedsFetcher(
 *   id = "tournament_fetcher",
 *   title = @Translation("Fetch tournamets"),
 *   description = @Translation("Downloads data from a federation website."),
 *   form = {
 *     "configuration" = "Drupal\feeds\Feeds\Fetcher\Form\HttpFetcherForm",
 *     "feed" = "Drupal\osy_tournament_parser\Feeds\Fetcher\Form\TournamentFetcherFeedForm",
 *   }
 * )
 */
class TournamentFetcher extends PluginBase implements ClearableInterface, FetcherInterface, ContainerFactoryPluginInterface {

  /**
   * Constructs an UploadFetcher object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \GuzzleHttp\ClientInterface $client
   *   The Guzzle client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The Drupal file system helper.
   * @param \Drupal\Core\File\FeedsFileSystemInterface $feeds_file_system
   *   The Drupal file system helper for Feeds.
   */
  public function __construct(
    array $configuration, $plugin_id,
    array $plugin_definition,
    protected ClientInterface $client,
    protected CacheBackendInterface $cache,
    protected FileSystemInterface $fileSystem,
    protected FeedsFileSystemInterface $feedsFileSystem,
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
      $container->get('http_client'),
      $container->get('cache.feeds_download'),
      $container->get('file_system'),
      $container->get('feeds.file_system.in_progress'),
      $container->get('osy_tournament_parser.plugin.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed, StateInterface $state) {
    $sink = $this->feedsFileSystem->tempnam($feed, 'tournament_fetcher_');
    $cache_key = $this->useCache() ? $this->getCacheKey($feed) : FALSE;
    [$plugin_id, $source] = explode('::', $feed->getSource());

    $plugin = $this->tournamentParserPluginManager->createInstance($plugin_id);

    $this->fileSystem->unlink($sink);
    $this->fileSystem->prepareDirectory($sink, FileSystemInterface::CREATE_DIRECTORY);
    $states = $plugin->fetch($source, $sink, $cache_key);

    return new TournamentsFetcherResult($sink, $states, $this->fileSystem);
  }

  /**
   * Returns if the cache should be used.
   *
   * @return bool
   *   True if results should be cached. False otherwise.
   */
  protected function useCache() {
    return !$this->configuration['always_download'];
  }

  /**
   * Returns the download cache key for a given feed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to find the cache key for.
   *
   * @return string
   *   The cache key for the feed.
   */
  protected function getCacheKey(FeedInterface $feed) {
    return $feed->id() . ':' . hash('sha256', $feed->getSource());
  }

  /**
   * {@inheritdoc}
   */
  public function clear(FeedInterface $feed, StateInterface $state) {
    $this->onFeedDeleteMultiple([$feed]);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // @todo auto_detect_feeds causes issues with downloading files that are
      // not a RSS feed. Set the default to TRUE as soon as that issue is
      // resolved.
      'auto_detect_feeds' => FALSE,
      'use_pubsubhubbub' => FALSE,
      'always_download' => FALSE,
      'fallback_hub' => '',
      'request_timeout' => 30,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function onFeedDeleteMultiple(array $feeds) {
    foreach ($feeds as $feed) {
      $this->cache->delete($this->getCacheKey($feed));
    }
  }

}
