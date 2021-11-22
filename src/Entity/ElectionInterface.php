<?php

namespace Drupal\election\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Election entities.
 *
 * @ingroup election
 */
interface ElectionInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Election name.
   *
   * @return string
   *   Name of the Election.
   */
  public function getName();

  /**
   * Sets the Election name.
   *
   * @param string $name
   *   The Election name.
   *
   * @return \Drupal\election\Entity\ElectionInterface
   *   The called Election entity.
   */
  public function setName($name);

  /**
   * Gets the Election creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Election.
   */
  public function getCreatedTime();

  /**
   * Sets the Election creation timestamp.
   *
   * @param int $timestamp
   *   The Election creation timestamp.
   *
   * @return \Drupal\election\Entity\ElectionInterface
   *   The called Election entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Election revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Election revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\election\Entity\ElectionInterface
   *   The called Election entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Election revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Election revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\election\Entity\ElectionInterface
   *   The called Election entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Get the next votable position for the user.
   *
   * @param ElectionPostInterface $current
   * @param array $alreadyDoneOrSkippedIds
   *
   * @return [type]
   */
  public function getNextPostId(AccountInterface $account = NULL, ElectionPostInterface $current = NULL, array $alreadyDoneOrSkippedIds = NULL);
}
