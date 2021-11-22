<?php

namespace Drupal\election;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Election ballot vote entities.
 *
 * @ingroup election
 */
class ElectionBallotVoteListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Election ballot vote ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\election\Entity\ElectionBallotVote $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.election_ballot_vote.edit_form',
      ['election_ballot_vote' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
