<?php

namespace Drupal\election\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Election ballot vote entities.
 *
 * @ingroup election
 */
interface ElectionBallotVoteInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Election ballot vote name.
   *
   * @return string
   *   Name of the Election ballot vote.
   */
  public function getName();

  /**
   * Sets the Election ballot vote name.
   *
   * @param string $name
   *   The Election ballot vote name.
   *
   * @return \Drupal\election\Entity\ElectionBallotVoteInterface
   *   The called Election ballot vote entity.
   */
  public function setName($name);

  /**
   * Gets the Election ballot vote creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Election ballot vote.
   */
  public function getCreatedTime();

  /**
   * Sets the Election ballot vote creation timestamp.
   *
   * @param int $timestamp
   *   The Election ballot vote creation timestamp.
   *
   * @return \Drupal\election\Entity\ElectionBallotVoteInterface
   *   The called Election ballot vote entity.
   */
  public function setCreatedTime($timestamp);
}
