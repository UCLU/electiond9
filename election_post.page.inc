<?php

/**
 * @file
 * Contains election_post.page.inc.
 *
 * Page callback for Election post entities.
 */

use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\Element;
use Drupal\election\Entity\Election;
use Drupal\user\Entity\User;

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

  // $variables['#cache']['max-age'] = 0;
  if (!isset($variables['#cache']['tags'])) {
    $variables['#cache']['tags'] = [];
  }
  $variables['#cache']['tags'] = array_merge($variables['#cache']['tags'], $election_post->getElection()->getCacheTags());

  $cache = [
    'max-age' => 100,
    'contexts' => ['user'],
    'tags' => User::load(\Drupal::currentUser()->id())->getCacheTags(),
  ];

  $variables['candidate_name'] = $election_post->getCandidateTypesAsLabel();

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
