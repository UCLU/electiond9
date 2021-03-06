<?php

namespace Drupal\election_login_links\Entity;

use DateTime;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\election_conditions\ElectionConditionsTrait;
use Drupal\election\ElectionStatusesTrait;
use Drupal\election\Entity\Election;
use Drupal\election\Entity\ElectionInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Election Login Hash entity.
 *
 * @ingroup election
 *
 * @ContentEntityType(
 *   id = "election_login_hash",
 *   label = @Translation("Election login hash"),
 *   label_collection = @Translation("Election login hashes"),
 *   label_singular = @Translation("election login hash"),
 *   label_plural = @Translation("election login hashes"),
 *   label_count = @PluralTranslation(
 *     singular = "@count election login hash",
 *     plural = "@count election login hashes"
 *   ),
 *   base_table = "election_login_hash",
 *   entity_keys = {
 *     "id" = "id",
 *     "hash" = "hash",
 *     "uid" = "user_id",
 *     "election_id" = "election_id",
 *   },
 * )
 */
class ElectionLoginHash extends ContentEntityBase implements ElectionLoginHashInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getElection() {
    return $this->get('election_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getElectionId() {
    return $this->get('election_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setElectionId($election_id) {
    $this->set('election_id', $election_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setElection(ElectionInterface $election) {
    $this->set('election_id', $election->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User to log in'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 50,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['election_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Target election'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'election')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 50,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['used'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Used time'));

    $fields['expiry'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expiry time'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Secure hash'));

    return $fields;
  }

  public function setHash() {
    $hashKey = $this->getElectionId() . '-' . $this->getOwnerId() . '-' . $this->expires->value;
    $this->hash->value = Crypt::hmacBase64($hashKey, \Drupal::config('system.site')->get('uuid'));
  }

  public function getHash() {
    return $this->hash->value;
  }

  public function getUrl() {
    return Url::fromRoute('election_login_links.login', ['hash' => $this->getHash()]);
  }

  public function getLink() {
    return $this->getUrl->toString();
  }
}
