<?php

declare(strict_types=1);

namespace Drupal\osy_tournament_parser;

/**
 * Interface for osy_tournament_parser plugins.
 */
interface OsyTournamentParserPluginInterface {

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
  public function fetch($url, $sink, $cache_key,): mixed;
}
