<?php

/**
 * @file
 * Contains election.page.inc.
 *
 * Page callback for Election entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Election templates.
 *
 * Default template: election.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_election(array &$variables) {
  // Fetch Election Entity Object.
  $election = $variables['elements']['#election'];

  $variables['election'] = $variables['elements']['#election'];

  $variables['actions'] = [
    '#theme' => 'election_actions',
    '#actions' => $election->getActionLinks(\Drupal::currentUser()),
  ];

  $variables['status_summary'] = [
    '#theme' => 'election_status_summary',
    '#phases' => $election->getUserEligibility(\Drupal::currentUser()),
  ];

  $variables['posts_name'] = $election->getPostTypesAsLabel();

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
