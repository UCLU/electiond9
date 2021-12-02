<?php

/**
 * Implements hook_views_data_alter().
 */
function election_views_data_alter(array &$data) {
  $data['election_post']['eligibility_for_post'] = [
    'title' => t("Current user's eligibility for election post"),
    'help' => t('Optionally show actions based on eligibility.'),
    'field' => [
      'id' => 'eligibility_for_post',
    ],
  ];
}
