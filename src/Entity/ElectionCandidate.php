<?php

namespace Drupal\election\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Election candidate entity.
 *
 * @ingroup election
 *
 * @ContentEntityType(
 *   id = "election_candidate",
 *   label = @Translation("Election candidate"),
 *   bundle_label = @Translation("Election candidate type"),
 *   handlers = {
 *     "storage" = "Drupal\election\ElectionCandidateStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\election\ElectionCandidateListBuilder",
 *     "views_data" = "Drupal\election\Entity\ElectionCandidateViewsData",
 *     "translation" = "Drupal\election\ElectionCandidateTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\election\Form\ElectionCandidateForm",
 *       "add" = "Drupal\election\Form\ElectionCandidateForm",
 *       "edit" = "Drupal\election\Form\ElectionCandidateForm",
 *       "delete" = "Drupal\election\Form\ElectionCandidateDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\election\ElectionCandidateHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\election\ElectionCandidateAccessControlHandler",
 *   },
 *   base_table = "election_candidate",
 *   bundle_entity_type = "election_candidate_type",
 *   data_table = "election_candidate_field_data",
 *   revision_table = "election_candidate_revision",
 *   revision_data_table = "election_candidate_field_revision",
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message",
 *   },
 *   translatable = TRUE,
 *   admin_permission = "administer election candidate entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/election/candidate/{election_candidate}",
 *     "add-page" = "/election/candidate/add",
 *     "add-form" = "/election/candidate/add/{election_candidate_type}",
 *     "edit-form" = "/election/candidate/{election_candidate}/edit",
 *     "delete-form" = "/election/candidate/{election_candidate}/delete",
 *     "version-history" = "/election/candidate/{election_candidate}/revisions",
 *     "revision" = "/election/candidate/{election_candidate}/revisions/{election_candidate_revision}/view",
 *     "revision_revert" = "/election/candidate/{election_candidate}/revisions/{election_candidate_revision}/revert",
 *     "revision_delete" = "/election/candidate/{election_candidate}/revisions/{election_candidate_revision}/delete",
 *     "translation_revert" = "/election/candidate/{election_candidate}/revisions/{election_candidate_revision}/revert/{langcode}",
 *     "collection" = "/admin/content/election/candidate",
 *   },
 *   field_ui_base_route = "entity.election_candidate_type.edit_form"
 * )
 */
class ElectionCandidate extends EditorialContentEntityBase implements ElectionCandidateInterface {

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
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    } elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly,
    // make the election_candidate owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
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

  /**
   * {@inheritdoc}
   */
  public function getElectionPost() {
    $id = $this->get('election_post')->first()->getValue()['target_id'];
    return \Drupal::entityTypeManager()->getStorage('election_post')->load($id);
  }

  public static function getPossibleStatuses() {
    return [
      'interest' => t('Expression of interest'),
      'hopeful' => t('Hopeful'),
      'withdrawn' => t('Withdrawn'),
      'rejected' => t('Rejected'),
      'defeated' => t('Defeated'),
      'elected' => t('Elected'),
    ];
  }

  /**
   * {@inheritdoc}
   */
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

    // $fields['type'] = BaseFieldDefinition::create('entity_reference')
    //   ->setLabel(t('Type'))
    //   ->setDescription(t('The election candidate type.'))
    //   ->setSetting('target_type', 'election_candidate_type')
    //   ->setReadOnly(TRUE);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'region' => 'hidden',
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

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status']
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    //Custom fields
    $fields['election_post'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Election post'))
      ->setSetting('target_type', 'election_post')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    $fields['candidate_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setSettings([
        'allowed_values' => static::getPossibleStatuses(),
      ])
      ->setDefaultValue('hopeful')
      ->setDisplayOptions('view', [
        'region' => 'hidden',
        'label' => 'above',
        'type' => 'string',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // https://api.drupal.org/api/drupal/core!modules!image!src!Plugin!Field!FieldType!ImageItem.php/function/ImageItem%3A%3AdefaultFieldSettings/8.2.x
    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Image'))
      ->setSettings([
        'alt_field_required' => FALSE,
        'file_extensions' => 'png jpg jpeg',
        'max_filesize' => '1MB',
        // 'max_resolution' => '',
        // 'min_resolution' => '',
      ])
      ->setDisplayOptions('view', [
        'type' => 'image',
        'weight' => 5,
        'label' => 'hidden',
        'settings' => [
          'image_style' => 'thumbnail',
        ],
      ])
      ->setDisplayOptions('form', array(
        'label' => 'hidden',
        'type' => 'image_image',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['statement'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Statement'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'text_default',
        'weight' => 6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 6,
        'rows' => 6,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  public function getElectionCandidateType() {
    return ElectionCandidateType::load($this->bundle());
  }

  public static function loadByUserAndPost(AccountInterface $account, ElectionPostInterface $election_post, $statuses = []) {
    $query = \Drupal::entityQuery('election_candidate')
      ->condition('user_id', $account->id())
      ->condition('election_post', $election_post->id());

    if (count($statuses) > 0) {
      $query->condition('candidate_status', $statuses, 'IN');
    }

    $ids = $query->execute();
    return ElectionCandidate::loadMultiple($ids);
  }

  public function getBallotVotes($confirmedOnly = FALSE) {
    $votes_query = \Drupal::database()->select('election_ballot_vote', 'ev');
    $votes_query->join('election_ballot', 'eb', 'eb.id = ev.ballot_id');
    $votes_query->fields('ev', ['id'])
      ->condition('eb.value', 0, '>')
      ->orderBy('eb.ballot_id')
      ->orderBy('ev.rank');

    if ($confirmedOnly) {
      $votes_query->condition('eb.confirmed', 1);
    }

    $votes = $votes_query->execute()->fetchCol();

    return ElectionBallotVote::loadMultiple($votes);
  }

  public function countBallotVotes($confirmedOnly = FALSE) {
    return count($this->getBallotVotes($confirmedOnly));
  }
}
