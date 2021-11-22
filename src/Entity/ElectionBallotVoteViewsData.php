<?php

namespace Drupal\election\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Election ballot vote entities.
 */
class ElectionBallotVoteViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
