<?php

namespace Drupal\election\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Election ballot entities.
 *
 * @ingroup election
 */
interface ElectionBallotInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Election ballot name.
   *
   * @return string
   *   Name of the Election ballot.
   */
  public function getName();

  /**
   * Sets the Election ballot name.
   *
   * @param string $name
   *   The Election ballot name.
   *
   * @return \Drupal\election\Entity\ElectionBallotInterface
   *   The called Election ballot entity.
   */
  public function setName($name);

  /**
   * Gets the Election ballot creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Election ballot.
   */
  public function getCreatedTime();

  /**
   * Sets the Election ballot creation timestamp.
   *
   * @param int $timestamp
   *   The Election ballot creation timestamp.
   *
   * @return \Drupal\election\Entity\ElectionBallotInterface
   *   The called Election ballot entity.
   */
  public function setCreatedTime($timestamp);

}
