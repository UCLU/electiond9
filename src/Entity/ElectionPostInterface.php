<?php

namespace Drupal\election\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Election post entities.
 *
 * @ingroup election
 */
interface ElectionPostInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Election post name.
   *
   * @return string
   *   Name of the Election post.
   */
  public function getName();

  /**
   * Sets the Election post name.
   *
   * @param string $name
   *   The Election post name.
   *
   * @return \Drupal\election\Entity\ElectionPostInterface
   *   The called Election post entity.
   */
  public function setName($name);

  /**
   * Gets the Election post creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Election post.
   */
  public function getCreatedTime();

  /**
   * Sets the Election post creation timestamp.
   *
   * @param int $timestamp
   *   The Election post creation timestamp.
   *
   * @return \Drupal\election\Entity\ElectionPostInterface
   *   The called Election post entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Election post revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Election post revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\election\Entity\ElectionPostInterface
   *   The called Election post entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Election post revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Election post revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\election\Entity\ElectionPostInterface
   *   The called Election post entity.
   */
  public function setRevisionUserId($uid);

}
