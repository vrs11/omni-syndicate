<?php

declare(strict_types=1);

namespace Drupal\osy_tournament_parser\Plugin\OsyTournamentParser;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\feeds\Utility\Feed;
use Drupal\osy_tournament_parser\OsyTournamentParserPluginBase;
use Gt\Dom\HTMLDocument;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the osy_tournament_parser.
 *
 * @OsyTournamentParser(
 *   id = "fiim",
 *   label = @Translation("FIIM"),
 *   description = @Translation("FIIM Tournamets fetcher.")
 * )
 */
final class FIIM extends OsyTournamentParserPluginBase implements ContainerFactoryPluginInterface {

  static $LINK_PATTERN = '/https:\/\/mafiaworldtour\.com\/tournaments\/(?<tid>\d+)/';

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ClientInterface $client,
    protected CacheBackendInterface $cache,
    protected FileSystemInterface $fileSystem,
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
    );
  }

  /**
   * Performs a GET request.
   *
   * @param string $url
   *   The URL to GET.
   * @param string $sink
   *   The location where the downloaded content will be saved. This can be a
   *   resource, path or a StreamInterface object.
   * @param string $cache_key
   *   (optional) The cache key to find cached headers. Defaults to false.
   *
   * @return \Guzzle\Http\Message\Response
   *   A Guzzle response.
   *
   * @throws \RuntimeException
   *   Thrown if the GET request failed.
   *
   * @see \GuzzleHttp\RequestOptions
   */
  public function fetch(
    $url,
    $sink,
    $cache_key,
  ): mixed {
    $url = Feed::translateSchemes($url);
    $headers = [];

    $jar = new CookieJar();
    $response = $this->client->getAsync($url, [
      'cookies' => $jar,
    ])->wait();
    $dom = new HTMLDocument($response->getBody()->getContents());
    $csrf_token = $dom->querySelector('meta[name="csrf-token"]')->content;

    $page = 0;
    $tids = [];
    while (TRUE) {
      $page++;
      try {
        $response = $this->client->postAsync("{$url}/search?page={$page}", [
          'form_params' => [
            'year' => 'All',
            'country' => '',
            'city' => '',
            'name' => '',
            'schedule' => 'planned',
            'participants' => 'all',
            'serial' => 'all',
            'no_of_stars' => 'all',
            'master_id' => 'all',
          ],
          'cookies' => $jar,
          'headers' => [
            'X-Csrf-Token' => $csrf_token,
            'Content-Type' => 'application/x-www-form-urlencoded',
          ],
        ])->wait();
      } catch (\Exception $e) {
        break;
      }

      $matches = [];
      $data = Json::decode($response->getBody()->getContents());
      preg_match_all(static::$LINK_PATTERN, $data['success'], $matches);
      if (empty($matches["tid"])) {
        break;
      }
      $tids = array_merge($tids, $matches["tid"]);
    }

    if (empty($tids)) {
      $args = ['%site' => $url, '%error' => $this->t("Tournaments haven't been found")];
      throw new \RuntimeException($this->t('The feed from %site seems to be broken because of error "%error".', $args));
    }

    foreach ($tids as $tid) {
      $options = [RequestOptions::SINK => "{$sink}/{$tid}.html"];
      if ($cache_key && ($cache = $this->cache->get("{$cache_key}:{$tid}"))) {
        if (isset($cache->data['etag:' . $tid])) {
          $options[RequestOptions::HEADERS]['If-None-Match'] = $cache->data['etag'];
        }
        if (isset($cache->data['last-modified'])) {
          $options[RequestOptions::HEADERS]['If-Modified-Since'] = $cache->data['last-modified'];
        }
      }

      try {
        $response = $this->client->getAsync($url, $options)->wait();
      }
      catch (RequestException $e) {
        continue;
      }

      $headers[$tid] = array_change_key_case($response->getHeaders());

      if ($cache_key) {
        $this->cache->set("{$cache_key}:{$tid}", $headers[$tid]);
      }
    }

    return $headers;
  }

}
