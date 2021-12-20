<?php

namespace Drupal\election_login_links\Entity;

use DateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\election_conditions\ElectionConditionsTrait;
use Drupal\election\ElectionStatusesTrait;
use Drupal\election\Entity\ElectionInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface for defining Election entities.
 *
 * @ingroup election
 */
interface ElectionLoginHashInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {


  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values);

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime();

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp);

  /**
   * {@inheritdoc}
   */
  public function getOwner();

  /**
   * {@inheritdoc}
   */
  public function getOwnerId();

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid);

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account);

  /**
   * {@inheritdoc}
   */
  public function getElection();

  /**
   * {@inheritdoc}
   */
  public function getElectionId();

  /**
   * {@inheritdoc}
   */
  public function setElectionId($election_id);

  /**
   * {@inheritdoc}
   */
  public function setElection(ElectionInterface $account);

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type);
}
