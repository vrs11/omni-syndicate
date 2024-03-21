<?php

/**
 * @file
 * Contains user_ownership.page.inc.
 *
 * Page callback for User ownership entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for User ownership templates.
 *
 * Default template: user_ownership.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_user_ownership(array &$variables) {
  // Fetch UserOwnership Entity Object.
  $user_ownership = $variables['elements']['#user_ownership'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
