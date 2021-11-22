<?php

/**
 * @file
 * Contains election_candidate.page.inc.
 *
 * Page callback for Election candidate entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Election candidate templates.
 *
 * Default template: election_candidate.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_election_candidate(array &$variables) {
  // Fetch ElectionCandidate Entity Object.
  $election_candidate = $variables['elements']['#election_candidate'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
