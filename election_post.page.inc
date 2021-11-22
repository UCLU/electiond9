<?php

/**
 * @file
 * Contains election_post.page.inc.
 *
 * Page callback for Election post entities.
 */

use Drupal\Core\Render\Element;
use Drupal\election\Entity\Election;

/**
 * Prepares variables for Election post templates.
 *
 * Default template: election_post.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_election_post(array &$variables) {

  // Fetch ElectionPost Entity Object.
  $election_post = $variables['elements']['#election_post'];

  $variables['status_summary'] = [
    '#theme' => 'election_status_summary',
    '#phases' => $election_post->getUserEligibility(\Drupal::currentUser()),
  ];

  $variables['actions'] = [
    '#theme' => 'election_post_actions',
    '#actions' => $election_post->getActionLinks(\Drupal::currentUser()),
  ];

  $variables['candidate_name'] = $election_post->getCandidateTypesAsLabel();

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
