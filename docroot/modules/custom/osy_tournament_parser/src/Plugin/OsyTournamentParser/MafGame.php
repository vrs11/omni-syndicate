<?php

declare(strict_types=1);

namespace Drupal\osy_tournament_parser\Plugin\OsyTournamentParser;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Locale\CountryManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Utility\Feed;
use Drupal\node\Entity\Node;
use Drupal\osy_tournament_parser\EntityDataWrapper;
use Drupal\osy_tournament_parser\OsyTournamentParserPluginBase;
use Drupal\stated_entity_reference\Entity\StatedEntityReference;
use Drupal\user\Entity\User;
use Gt\Dom\HTMLDocument;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the osy_tournament_parser.
 *
 * @OsyTournamentParser(
 *   id = "mafgame",
 *   label = @Translation("MAFGAME"),
 *   description = @Translation("MAFGAME Tournamets fetcher.")
 * )
 */
final class MafGame extends OsyTournamentParserPluginBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  const SOURCE_PREFIX = 'MAFGAME';

  const EXTRA = [
    'errors',
    'auth',
    'env',
    'ziggy',
    'flash',
    'menu',
    'user_props',
  ];

  const LIST = 'https://mafgame.org/tournaments';
  const STRUCT_TOURNAMENT = [
    'tournament' => '@source/tournaments/@id/view',
    'points' => '@source/tournaments/@id/points',
    'participants' => '@source/tournaments/@id/participants',
    'terms' => '@source/tournaments/@id/terms',
  ];

  const USER = '@source/user/@id/view';
  const CLUB = '@source/clubs/@id/view';


  protected $version;
  protected $cookie;

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
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cookie = new CookieJar();
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
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Execute mapping on an item.
   *
   * This method encapsulates the central mapping functionality. When an item is
   * processed, it is passed through map() where the properties of $source_item
   * are mapped onto $target_item following the processor's mapping
   * configuration.
   */
  public function map(FeedInterface $feed, EntityInterface $entity, ItemInterface $item) {
    $data = $item->toArray();
    $tournament = &$data['tournament']['tournament'];
    $points = &$data['points']['points'];
    $sink = $data['sink_path'];
    $date = new \DateTime($tournament['start_date']);

    $DW = EntityDataWrapper::wrap($tournament, $entity)
      ->set('field_original_reference_id', static::SOURCE_PREFIX . $tournament['id'], TRUE)
      ->set('title', 'name')
      ->set('body', 'description')
      ->set('field_tournament_duration', 'days')
      ->set('field_tournament_fee', 'participation_fee')
      ->set('field_tournament_duration', 'days')
      ->set('field_tournament_players_number', 'expected_participants')
      ->set('field_start_date', $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), TRUE);

    if ($CC = array_search($tournament['city']['country'], CountryManager::getStandardList())) {
      $DW->set('field_address', [
        'country_code' => $CC,
        'locality' => $tournament['city']['city'] ?? 'In description',
        'address_line1' => 'In description',
      ], TRUE);
    }

    if (!empty($regulations = $data["terms"]["regulations"])) {
      $DW->set('field_tournament_terms', $regulations, TRUE);
    }

    if (!empty($points)) {
      $users = [];
      foreach ($points as $user) {
        if (empty($user = $this->getUser($user['user_id'], $sink))) {
          continue;
        }

        $users[] = ['target_id' => $user->id()];
      }

      $DW->set('field_tournament_participants', $users, TRUE);
    }

    if (
      !empty($entity->id())
      && !empty($club = $this->getClub($data['club_id'], $sink))
    ) {
      $this->checkRelation('tournament_club', $entity, $club);
    }

    return $entity;
  }

  protected function getUser($id, $sink) {
    $key = $this->getKeyPrefix(":USER:{$id}");

    if (!empty(
      $users = $this->entityTypeManager->getStorage('user')->loadByProperties([
        'field_parser_source' => $key,
      ])
    )) {
      return reset($users);
    }

    if (
      !empty($content = file_get_contents("{$sink}/user_{$id}.json"))
      && !empty($data = Json::decode($content)['user_data'])
    ) {
      $CC = array_search($data['user_city']['country'], CountryManager::getStandardList());
      $users = $this->entityTypeManager->getStorage('user')->loadByProperties([
        'field_nickname' => $data['nickname'],
        'field_firstname' => $data['display_name'],
        'field_home_city.country_code' => $CC,
        'field_home_city.locality' => $data['user_city']['city'],
      ]);

      if (!empty($users)) {
        //TODO check if the user belongs to a club
        return reset($users);
      }

      $mail = $this->random_email();
      $user = User::create([
        'name' => $mail,
        'mail' => $mail,
        'field_nickname' => $data['nickname'],
        'field_firstname' => $data['display_name'],
        'field_parser_source' => $key,
        'field_home_city' => [
          'country_code' => $CC,
          'locality' => $data['user_city']['city'],
        ],
      ]);

      try {
        $user->save();
      } catch (\Exception $e) {
        return NULL;
      }

      if ($club = $this->getClub($data['club_id'], $sink)) {
        $this->checkRelation('club_member', $user, $club);
      }

      return $user;
    }

    return NULL;
  }

  protected function checkRelation($type, EntityInterface $source, EntityInterface $target) {
    $rels = $this->entityTypeManager->getStorage('stated_entity_reference')->loadByProperties([
      'type' => $type,
      'source_entity_id__target_id' => $source->id(),
      'source_entity_id__target_type' => $source->getEntityTypeId(),
      'target_entity_id__target_id' => $target->id(),
      'target_entity_id__target_type' => $target->getEntityTypeId(),
    ]);

    if (empty($rels)) {
      $rel = StatedEntityReference::create([
        'type' => $type,
        'field_parser_source' => $this->getKeyPrefix(),
        'source_entity_id' => $source,
        'target_entity_id' => $target,
        'state' => 'active',
      ]);

      try {
        $rel->save();
      } catch (\Exception $e) {
        return NULL;
      }

      return $rel;
    }

    $rel = reset($rels);

    if (!empty(
      $remove_rels_ids = $this->entityTypeManager->getStorage('stated_entity_reference')->getQuery()
        ->condition('type', $type)
        ->condition('id', $rel->id(), '!=')
        ->condition('source_entity_id__target_id', $source->id())
        ->condition('source_entity_id__target_type', $source->getEntityTypeId())
        ->condition('field_parser_source', $this->getKeyPrefix())
        ->accessCheck(FALSE)
        ->execute()
    )) {
      $remove_rels = $this->entityTypeManager->getStorage('stated_entity_reference')->loadMultiple($remove_rels_ids);
      $this->entityTypeManager->getStorage('stated_entity_reference')->delete($remove_rels);
    }

    return $rel;
  }

  protected function getClub($id, $sink) {
    $key = $this->getKeyPrefix(":CLUB:{$id}");

    if (!empty(
      $clubs = $this->entityTypeManager->getStorage('node')->loadByProperties([
        'type' => 'club',
        'field_parser_source' => $key,
      ])
    )) {
      return reset($clubs);
    }

    if (
      !empty($content = file_get_contents("{$sink}/club_{$id}.json"))
      && !empty($data = Json::decode($content)['club'])
    ) {
      $CC = array_search($data['city']['country'], CountryManager::getStandardList());
      $clubs = $this->entityTypeManager->getStorage('node')->loadByProperties([
        'title' => $data['name'],
        'field_address.country_code' => $CC,
        'field_address.locality' => $data['city']['city'],
      ]);

      if (!empty($clubs)) {
        return reset($clubs);
      }

      $club = Node::create([
        'type' => 'club',
        'title' => $data['name'],
        'field_email' => $data['email'],
        'field_phone' => $data['phones'],
        'field_parser_source' => $key,
        'field_address' => [
          'country_code' => $CC,
          'locality' => $data['city']['city'],
        ],
      ]);

      try {
        $club->save();
      } catch (\Exception $e) {
        return NULL;
      }

      return $club;
    }

    return NULL;
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
    $host = Feed::translateSchemes($url);
    $this->init($host);

    $tids = [];
    $uids = [];
    $cids = [];
    $states = [];

    $page = 0;
    do {
      $page++;
      $endpoint = static::LIST . "?" . http_build_query([
        's' => 'all',
        't' => 'all',
        'g' => 'all',
        'page' => $page,
      ]);
      $data = $this->getJson($endpoint, ['props', 'search_results']);
      $tids = array_merge($tids, array_column($data['data'], 'id'));
    } while (!empty($data['next_page_url']));

    foreach ($tids as $tid) {
      $data = [];
      foreach (static::STRUCT_TOURNAMENT as $key => $url) {
        $data[$key] = array_diff_key(
          $this->getJson(strtr($url, ['@source' => $host, '@id' => $tid]), ['props']),
          array_flip(static::EXTRA)
        );
      }

      $uids = array_merge($uids, array_column($data["participants"]["players"], 'user_id'));
      $uids = array_merge($uids, array_column($data["points"]["points"], 'user_id'));
      $uids[] = $data["tournament"]["tournament"]["organizer"]["id"] ?? NULL;
      $cids[] = $data["tournament"]["tournament"]["club_id"] ?? NULL;

      $content = Json::encode($data);
      $this->fileSystem->saveData($content, "{$sink}/tournament_{$tid}.json");
      $states['tournaments'][$tid] = md5($content);
      break;
    }

    $states['users'] = array_unique(array_filter($uids));
    // TODO: Load existing users by source origin ID and skip it from loading
    $i = 0;
    foreach ($states['users'] as $uid) {
      $data = array_diff_key(
        $this->getJson(strtr(static::USER, ['@source' => $host, '@id' => $uid]), ['props']),
        array_flip(static::EXTRA)
      );

      $cids[] = $data['user_data']['club']['id'] ?? NULL;
      $this->fileSystem->saveData(Json::encode($data), "{$sink}/user_{$uid}.json");
      if (++$i > 3) {
        break;
      }
    }

    $i = 0;
    $states['clubs'] = array_unique(array_filter($cids));
    // TODO: Load existing clubs by source origin ID and skip it from loading
    foreach ($states['clubs'] as $cid) {
      $data = array_diff_key(
        $this->getJson(strtr(static::CLUB, ['@source' => $host, '@id' => $cid]), ['props']),
        array_flip(static::EXTRA)
      );

      $this->fileSystem->saveData(Json::encode($data), "{$sink}/club_{$cid}.json");
      if (++$i > 3) {
        break;
      }
    }

    return $states;
  }

  protected function get($endpoint) {
    try {
      $response = $this->client->getAsync($endpoint, [
        'cookies' => $this->cookie,
        'headers' => [
          'X-Inertia' => 'true',
          'X-Inertia-Version' => $this->version,
        ],
      ])->wait();
    } catch (\Exception $e) {
      $args = ['%site' => $endpoint, '%error' => $e->getMessage()];
      throw new \RuntimeException($this->t('The feed from %site seems to be broken because of error "%error".', $args)->render());
    }

    return $response;
  }

  protected function getJson($endpoint, ?array $path = NULL): mixed {
    $response = $this->get($endpoint);
    $data = Json::decode($response->getBody()->getContents());

    if (empty($path)) {
      return $data;
    }

    return NestedArray::getValue($data, $path);
  }

  protected function init($endpoint) {
    $response = $this->client->getAsync($endpoint, [
      'cookies' => $this->cookie,
    ])->wait();
    $dom = new HTMLDocument($response->getBody()->getContents());
    $data = Json::decode($dom->querySelector('#app')->getAttribute('data-page'));
    $this->version = $data['version'];
  }

  public static function getKeyPrefix($value = '') {
    return static::SOURCE_PREFIX . $value;
  }

  protected static function random_email() {
    $name = substr(md5((string)mt_rand()), 0, 24);
    $domain = substr(md5((string)mt_rand()), 0, 5);
    return "{$name}@mafgame.{$domain}";
  }

}
