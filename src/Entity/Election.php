<?php

namespace Drupal\election\Entity;

use DateTime;
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
use Drupal\user\UserInterface;

/**
 * Defines the Election entity.
 *
 * @ingroup election
 *
 * @ContentEntityType(
 *   id = "election",
 *   label = @Translation("Election"),
 *   label_collection = @Translation("Elections"),
 *   label_singular = @Translation("election"),
 *   label_plural = @Translation("elections"),
 *   label_count = @PluralTranslation(
 *     singular = "@count election",
 *     plural = "@count elections"
 *   ),
 *   bundle_label = @Translation("Election type"),
 *   handlers = {
 *     "storage" = "Drupal\election\ElectionStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\election\ElectionListBuilder",
 *     "views_data" = "Drupal\election\Entity\ElectionViewsData",
 *     "translation" = "Drupal\election\ElectionTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\election\Form\ElectionForm",
 *       "add" = "Drupal\election\Form\ElectionForm",
 *       "edit" = "Drupal\election\Form\ElectionForm",
 *       "delete" = "Drupal\election\Form\ElectionDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\election\ElectionHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\election\ElectionAccessControlHandler",
 *   },
 *   base_table = "election",
 *   bundle_entity_type = "election_type",
 *   data_table = "election_field_data",
 *   revision_table = "election_revision",
 *   revision_data_table = "election_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer elections",
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
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message",
 *   },
 *   links = {
 *     "canonical" = "/election/{election}",
 *     "add-page" = "/election/add",
 *     "add-form" = "/election/add/{election_type}",
 *     "edit-form" = "/election/{election}/edit",
 *     "delete-form" = "/election/{election}/delete",
 *     "version-history" = "/election/{election}/revisions",
 *     "revision" = "/election/{election}/revisions/{election_revision}/view",
 *     "revision_revert" = "/election/{election}/revisions/{election_revision}/revert",
 *     "revision_delete" = "/election/{election}/revisions/{election_revision}/delete",
 *     "translation_revert" = "/election/{election}/revisions/{election_revision}/revert/{langcode}",
 *     "collection" = "/election/list",
 *   },
 *   field_ui_base_route = "entity.election_type.edit_form",
 *   permission_granularity = "bundle",
 * )
 */
class Election extends EditorialContentEntityBase implements ElectionInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  use ElectionStatusesTrait;
  use ElectionConditionsTrait;

  // @todo move to YML files for configuration maybe
  const ELECTION_PHASES = [
    'interest',
    'nominations',
    'voting',
  ];

  const SCHEDULING_STATES = [
    'open',
    'close',
  ];

  public static function getPhaseName($phase) {
    switch ($phase) {
      case 'interest':
        return t('Expressions of interest');

      case 'nominations':
        return t('Nominations');

      case 'voting':
        return t('Voting');
    }
  }

  public static function getPhaseAction($phase) {
    switch ($phase) {
      case 'interest':
        return t('Express interest');

      case 'nominations':
        return t('Nominate');

      case 'voting':
        return t('Vote');
    }
  }

  public static function getPhaseActionPastTense($phase) {
    switch ($phase) {
      case 'interest':
        return t('Expressed interest');

      case 'nominations':
        return t('Nominated');

      case 'voting':
        return t('Voted');
    }
  }

  public static function getStatusName($status) {
    switch ($status) {
      case 'open':
        return t('Open');

      case 'close':
        return t('Closed');
    }
  }

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
    // make the election owner the revision author.
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
   * Get user-friendly name for type.
   *
   * @param bool $capital
   *   Start with a capital letter.
   * @param bool $plural
   *   PLuralise.
   *
   * @return string
   *   The user-friendly name.
   */
  public function getTypeNaming($capital = FALSE, $plural = FALSE) {
    return ElectionType::load($this->bundle())->getNaming($capital, $plural);
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
      ->setDescription(t('The user ID of author of the Election entity.'))
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

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Election entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -50,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -50,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description of election'))
      ->setDescription(t('Full text information about the election.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => -45,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -49,
        'rows' => 6,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['status']
      ->setDescription(t('A checkbox indicating whether the election is published. This does not affect nomination or voting status for published elections, but if the election is unpublished no voting or nomination functionality will be available.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -48,
      ]);

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

    static::addElectionStatusesFields($fields, 'election');

    $fields['ballot_behaviour'] = BaseFieldDefinition::create('list_string')
      ->setLabel('Ballot behaviour')
      ->setDescription(t('What happens after voting for an individual position.'))
      ->setSettings([
        'allowed_values' => [
          'one_by_one' => 'Return to position list',
          'next_eligible' => 'Jump to next eligible position in election',
        ],
      ])
      ->setRequired(TRUE)
      ->setDefaultValue('next_eligible')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // @todo make view respect this
    $fields['ballot_candidate_sort'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Ballot candidate sort'))
      ->setSettings([
        'allowed_values' => [
          'random' => 'Random',
          'alphabetical' => 'Alphabetical by name',
        ],
      ])
      ->setDefaultValue('table')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ballot_confirmation'] = BaseFieldDefinition::create('list_string')
      ->setLabel('Ballot confirmation')
      ->setDescription(t(''))
      ->setSettings([
        'allowed_values' => [
          'none' => 'None (ballot is confirmed by submitting)',
          'after_post' => 'Users must confirm when submitting ballot',
          'after_election' => 'Users must confirm all votes at end of voting process for them to count',
        ],
      ])
      ->setRequired(TRUE)
      ->setDefaultValue('none')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ballot_show_candidates_below'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Show candidates list at end of ballot'))
      ->setDescription(t('Displayed in the "Ballot - full" display mode for the election post.'))
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    static::addElectionConditionsFields($fields, 'election');

    $fields['thankyou_for_voting_message'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Thankyou for voting message'))
      ->setDescription(t('Thankyou for voting message (leave blank to not show).'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 0,
        'rows' => 6,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['thankyou_for_voting_email'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Thankyou for voting email'))
      ->setDescription(t('Thankyou for voting email (leave blank to not send). This will be sent once per user per election fifteen minutes after they have voted for the first position (not skipped or abstained).'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 0,
        'rows' => 6,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  public function getPosts($only_published = TRUE) {
    $ids = $this->getPostIds($only_published);
    return ElectionPost::loadMultiple($ids);
  }

  public function getPostIds($only_published = TRUE) {
    $query = \Drupal::entityQuery('election_post');
    $query->condition('election', $this->id());

    if ($only_published) {
      $query->condition('status', 1);
    }

    $query->sort('name', 'ASC');

    $ids = $query->execute();
    return $ids;
  }

  /**
   * @return ElectionType
   */
  public function getElectionType() {
    $type = $this->bundle();
    return ElectionType::load($type);
  }

  public function getPostTypesAsLabel($capital = TRUE, $plural = TRUE) {
    $types = [];
    $posts = $this->getPosts();
    if (count($posts) > 0) {
      foreach ($posts as $post) {
        if (!isset($types[$post->bundle()])) {
          $types[$post->bundle()] = ElectionPostType::load($post->bundle())->getNaming($capital, $plural);
        }
      }
    } else {
      $election_type = ElectionType::load($this->bundle());
      foreach ($election_type->getAllowedPostTypes() as $post_type) {
        $types[$post_type->id()] = $post_type->getNaming($capital, $plural);
      }
    }

    $result = implode('/', $types);
    return $result;
  }

  public function getAllPossiblePostIds(AccountInterface $account = NULL) {
    $postIds = $this->getPostIds();

    if ($account) {
      $eligibilityService = \Drupal::service('election.post_eligibility_checker');

      $eligiblePostIds = [];
      foreach ($postIds as $postId) {
        // @todo this should be cached within the service,
        // but we could also cache it in getNextPostId.
        if ($eligibilityService->evaluateEligibility($account, ElectionPost::load($postId), 'voting', TRUE)) {
          $eligiblePostIds[] = $postId;
        }
      }

      $postIds = $eligiblePostIds;
    }

    if ($this->ballot_candidate_sort->value == 'random') {
      shuffle($postIds);
    }

    return $postIds;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextPostId(AccountInterface $account = NULL, ElectionPostInterface $current = NULL, array $alreadyDoneOrSkippedIds = NULL) {
    $postIds = $this->getAllPossiblePostIds($account);

    $currentId = $current ? [$current->id()] : [];

    $alreadyDoneOrSkippedIds = $alreadyDoneOrSkippedIds ?? ($_SESSION[$this->id() . '_done'] ?? []);

    $postIds = array_diff($postIds, $alreadyDoneOrSkippedIds, $currentId);

    return reset($postIds);
  }

  public function getActionLinks(AccountInterface $account) {

    $actions = [];
    $startVoting = $this->checkStatusForPhase('voting', 'open') && $this->getNextPostId($account);

    if ($startVoting) {
      $actions[] = [
        'title' => t('Start voting'),
        'link' => Url::fromRoute('entity.election.voting', ['election' => $this->id()])->toString(),
        'button_type' => 'primary',
      ];
    }

    if ($account->hasPermission('add election post entities')) {
      foreach ($this->getElectionType()->getAllowedPostTypes() as $election_post_type) {
        $url = Url::fromRoute('entity.election_post.add_to_election', [
          'election' => $this->id(),
          'election_post_type' => $election_post_type->id(),
        ]);
        if ($url) {
          $actions[] = [
            'title' => t(
              '@label',
              [
                '@label' => $election_post_type->getActionNaming(),
              ]
            ),
            'link' => $url->toString(),
            'button_type' => 'secondary',
          ];
        }
      }
    }

    return $actions;
  }

  public function getRedirectToNextPost() {
    $account = \Drupal::currentUser();
    $alreadyDoneOrSkippedIds = $_SESSION[$this->id() . '_done'] ?? [];
    $_SESSION[$this->id() . '_skipped'] = [];
    $postID = $this->getNextPostId($account, NULL, $alreadyDoneOrSkippedIds);
    if ($postID) {
      return [
        'route_name' => 'entity.election_post.voting',
        'route_parameters' => [
          'election_post' => $postID,
        ],
        'message' => NULL,
      ];
    } else {
      return [
        'route_name' => 'entity.election.canonical',
        'route_parameters' => [
          'election' => $this->id(),
        ],

        // @todo more informative, i.e. say how many voted for, how many ineligible for, how many closed
        'message' =>  t(
          'No %positions available to vote for.',
          [
            '%positions' => 'posts',
          ]
        )
      ];
    }
  }
}
