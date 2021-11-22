<?php

/**
 * @file
 * Contains election_ballot.page.inc.
 *
 * Page callback for Election ballot entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Election ballot templates.
 *
 * Default template: election_ballot.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_election_ballot(array &$variables) {
  // Fetch ElectionBallot Entity Object.
  $election_ballot = $variables['elements']['#election_ballot'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
