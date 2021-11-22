<?php

/**
 * @file
 * Contains election_ballot_vote.page.inc.
 *
 * Page callback for Election ballot vote entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Election ballot vote templates.
 *
 * Default template: election_ballot_vote.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_election_ballot_vote(array &$variables) {
  // Fetch ElectionBallotVote Entity Object.
  $election_ballot_vote = $variables['elements']['#election_ballot_vote'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
