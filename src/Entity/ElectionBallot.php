<?php

namespace Drupal\election\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Election ballot entity.
 *
 * @ingroup election
 *
 * @ContentEntityType(
 *   id = "election_ballot",
 *   label = @Translation("Election ballot"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\election\ElectionBallotListBuilder",
 *     "views_data" = "Drupal\election\Entity\ElectionBallotViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\election\Form\ElectionBallotForm",
 *       "add" = "Drupal\election\Form\ElectionBallotForm",
 *       "edit" = "Drupal\election\Form\ElectionBallotForm",
 *       "delete" = "Drupal\election\Form\ElectionBallotDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\election\ElectionBallotHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\election\ElectionBallotAccessControlHandler",
 *   },
 *   base_table = "election_ballot",
 *   translatable = FALSE,
 *   admin_permission = "administer election ballot entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/election/ballot/{election_ballot}",
 *     "add-form" = "/election/post/{election_post}/vote",
 *     "delete-form" = "/election/ballot/{election_ballot}/delete",
 *     "collection" = "/election/ballot",
 *   },
 *   field_ui_base_route = "election_ballot.settings"
 * )
 */
class ElectionBallot extends ContentEntityBase implements ElectionBallotInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

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

  public function getElectionPost() {
    if (!$this->get('election_post')->first()) {
      return NULL;
    }
    $id = $this->get('election_post')->first()->getValue()['target_id'];
    return \Drupal::entityTypeManager()->getStorage('election_post')->load($id);
  }

  public function getElection() {
    $electionPost = $this->getElectionPost();
    $id = $electionPost->get('election')->first()->getValue()['target_id'];
    return \Drupal::entityTypeManager()->getStorage('election')->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Voter'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Election ballot entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue(strtotime('now'))
      ->setRequired(TRUE);

    $fields['status']->setDescription(t('A boolean indicating whether the ballot is enabled.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    // Custom:

    $fields['election_post'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Election post'))
      ->setSetting('target_type', 'election_post')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'hidden',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ]);

    $fields['timestamp'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Time when cast'));

    $fields['abstained'] = BaseFieldDefinition::create('boolean')
      ->setLabel((t('Abstained')))
      ->setDescription(t('A boolean indicating whether the user abstained for this post.'))
      ->setDisplayOptions('form', [
        'type' => 'hidden',
      ]);

    $fields['user_agent'] = BaseFieldDefinition::create('string')
      ->setLabel(t('User agent'));

    $fields['ip'] = BaseFieldDefinition::create('string')
      ->setLabel(t('IP address'));

    return $fields;
  }

  public static function loadByUserAndPost(AccountInterface $account, ElectionPostInterface $election_post) {
    $ids = \Drupal::entityQuery('election_ballot')
      ->condition('user_id', $account->id())
      ->condition('election_post', $election_post->id())
      ->execute();
    return ElectionBallot::loadMultiple($ids);
  }
}
