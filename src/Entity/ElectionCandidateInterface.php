<?php

namespace Drupal\election\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Election candidate entities.
 *
 * @ingroup election
 */
interface ElectionCandidateInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Election candidate name.
   *
   * @return string
   *   Name of the Election candidate.
   */
  public function getName();

  /**
   * Sets the Election candidate name.
   *
   * @param string $name
   *   The Election candidate name.
   *
   * @return \Drupal\election\Entity\ElectionCandidateInterface
   *   The called Election candidate entity.
   */
  public function setName($name);

  /**
   * Gets the Election candidate creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Election candidate.
   */
  public function getCreatedTime();

  /**
   * Sets the Election candidate creation timestamp.
   *
   * @param int $timestamp
   *   The Election candidate creation timestamp.
   *
   * @return \Drupal\election\Entity\ElectionCandidateInterface
   *   The called Election candidate entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Election candidate revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Election candidate revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\election\Entity\ElectionCandidateInterface
   *   The called Election candidate entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Election candidate revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Election candidate revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\election\Entity\ElectionCandidateInterface
   *   The called Election candidate entity.
   */
  public function setRevisionUserId($uid);

}
