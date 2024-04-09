<?php

namespace Drupal\osy_tournament_parser\Feeds\Fetcher\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Fetcher\Form\HttpFetcherFeedForm;
use Drupal\osy_tournament_parser\OsyTournamentParserPluginManager;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form on the feed edit page for the HttpFetcher.
 */
class TournamentFetcherFeedForm extends HttpFetcherFeedForm {

  /**
   * Constructs an HttpFeedForm object.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client.
   */
  public function __construct(
    protected $client,
    protected OsyTournamentParserPluginManager $tournamentParserPluginManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('osy_tournament_parser.plugin.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    [$plugin, $source] = explode('::', $feed->getSource());

    $form['source'] = [
      '#title' => $this->t('Feed URL'),
      '#type' => 'url',
      '#default_value' => $source,
      '#maxlength' => 2048,
      '#required' => TRUE,
    ];

    $options = [];
    foreach ($this->tournamentParserPluginManager->getDefinitions() as $definition) {
      $options[$definition['id']] = $definition['label'];
    }

    $form['plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Fetch plugin'),
      '#options' => $options,
      '#default_value' => $plugin,
      '#required' => TRUE,
      '#multiple' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    $plugin = $form_state->getValue('plugin');
    $source = $form_state->getValue('source');
    $feed->setSource("{$plugin}::{$source}");
  }

}
