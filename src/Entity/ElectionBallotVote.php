<?php

namespace Drupal\election\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Election ballot vote entity.
 *
 * @ingroup election
 *
 * @ContentEntityType(
 *   id = "election_ballot_vote",
 *   label = @Translation("Election ballot vote"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\election\ElectionBallotVoteListBuilder",
 *     "views_data" = "Drupal\election\Entity\ElectionBallotVoteViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\election\Form\ElectionBallotVoteForm",
 *       "add" = "Drupal\election\Form\ElectionBallotVoteForm",
 *       "edit" = "Drupal\election\Form\ElectionBallotVoteForm",
 *       "delete" = "Drupal\election\Form\ElectionBallotVoteDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\election\ElectionBallotVoteHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\election\ElectionBallotVoteAccessControlHandler",
 *   },
 *   base_table = "election_ballot_vote",
 *   translatable = FALSE,
 *   admin_permission = "administer election ballot vote entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/election_ballot_vote/{election_ballot_vote}",
 *     "add-form" = "/admin/content/election_ballot_vote/add",
 *     "edit-form" = "/admin/content/election_ballot_vote/{election_ballot_vote}/edit",
 *     "delete-form" = "/admin/content/election_ballot_vote/{election_ballot_vote}/delete",
 *     "collection" = "/admin/content/election_ballot_vote",
 *   },
 *   field_ui_base_route = "election_ballot_vote.settings"
 * )
 */
class ElectionBallotVote extends ContentEntityBase implements ElectionBallotVoteInterface {

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

  public function getElectionBallot() {
    $id = $this->get('election_ballot')->first()->getValue()['target_id'];
    return \Drupal::entityTypeManager()->getStorage('election_ballot')->load($id);
  }

  public function getElectionPost() {
    $electionPost = $this->getElectionBallot();
    $id = $electionPost->get('election_post')->first()->getValue()['target_id'];
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
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Election ballot vote entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    // Custom fields
    $fields['ballot_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Ballot ID'))
      ->setSetting('target_type', 'election_ballot');

    $fields['candidate_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Candidate ID'))
      ->setSetting('target_type', 'election_candidate');

    $fields['ron'] = BaseFieldDefinition::create('boolean')
      ->setLabel((t('Vote for re-open nominations')));

    $fields['abstention'] = BaseFieldDefinition::create('boolean')
      ->setLabel((t('Abstention')));

    $fields['ranking'] = BaseFieldDefinition::create('integer')
      ->setLabel((t('Ranking of candidate')));

    $fields['timestamp'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Time when cast'));

    return $fields;
  }
}
