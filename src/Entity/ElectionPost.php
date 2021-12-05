<?php

namespace Drupal\election\Entity;

use Drupal\Core\Cache\Cache;
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
 * Defines the Election post entity.
 *
 * @ingroup election
 *
 * @ContentEntityType(
 *   id = "election_post",
 *   label = @Translation("Election post"),
 *   label_collection = @Translation("Election posts"),
 *   label_singular = @Translation("election post"),
 *   label_plural = @Translation("election posts"),
 *   label_count = @PluralTranslation(
 *     singular = "@count election post",
 *     plural = "@count election posts"
 *   ),
 *   bundle_label = @Translation("Election post type"),
 *   handlers = {
 *     "storage" = "Drupal\election\ElectionPostStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\election\ElectionPostListBuilder",
 *     "views_data" = "Drupal\election\Entity\ElectionPostViewsData",
 *     "translation" = "Drupal\election\ElectionPostTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\election\Form\ElectionPostForm",
 *       "add" = "Drupal\election\Form\ElectionPostForm",
 *       "edit" = "Drupal\election\Form\ElectionPostForm",
 *       "delete" = "Drupal\election\Form\ElectionPostDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\election\ElectionPostHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\election\ElectionPostAccessControlHandler",
 *   },
 *   base_table = "election_post",
 *   bundle_entity_type = "election_post_type",
 *   data_table = "election_post_field_data",
 *   revision_table = "election_post_revision",
 *   revision_data_table = "election_post_field_revision",
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message",
 *   },
 *   translatable = TRUE,
 *   admin_permission = "administer election posts",
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
 *     "canonical" = "/election/post/{election_post}",
 *     "add-page" = "/election/post/add",
 *     "add-form" = "/election/post/add/{election_post_type}",
 *     "edit-form" = "/election/post/{election_post}/edit",
 *     "delete-form" = "/election/post/{election_post}/delete",
 *     "version-history" = "/election/post/{election_post}/revisions",
 *     "revision" = "/election/post/{election_post}/revisions/{election_post_revision}/view",
 *     "revision_revert" = "/election/post/{election_post}/revisions/{election_post_revision}/revert",
 *     "revision_delete" = "/election/post/{election_post}/revisions/{election_post_revision}/delete",
 *     "translation_revert" = "/election/post/{election_post}/revisions/{election_post_revision}/revert/{langcode}",
 *     "collection" = "/election",
 *   },
 *   field_ui_base_route = "entity.election_post_type.edit_form"
 * )
 */
class ElectionPost extends EditorialContentEntityBase implements ElectionPostInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;
  use ElectionStatusesTrait;
  use ElectionConditionsTrait;

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
    // make the election_post owner the revision author.
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
  public function getElection() {
    $id = $this->get('election')->first()->getValue()['target_id'];
    return Election::load($id);
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
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
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
        'text_processing' => 0
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -50,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status']
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
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
    $fields['election'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Election'))
      ->setSetting('target_type', 'election')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('Full text information about the post.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'rows' => 6,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    static::addElectionConditionsFields($fields, 'election_post');

    $fields['candidate_type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Election candidate type'))
      ->setSetting('target_type', 'election_candidate_type')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['limit_to_one_nomination_per_user'] = BaseFieldDefinition::create('boolean')
      ->setLabel('Limit candidates to one nomination per user')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
      ])
      ->setDefaultValue(TRUE);

    $fields['vacancies'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Vacancies'))
      ->setDescription(t('Number of available vacancies in the post.'))
      ->setDefaultValue(1)
      ->setSetting('min', 1)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['skip_allowed'] = BaseFieldDefinition::create('boolean')
      ->setLabel((t('Skipping permitted (can return to vote later)')))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
      ])
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['abstentions_allowed'] = BaseFieldDefinition::create('boolean')
      ->setLabel((t('Abstentions permitted')))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
      ])
      ->setDefaultValue(true)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['include_reopen_nominations'] = BaseFieldDefinition::create('boolean')
      ->setLabel((t('Include "Reopen nominations" (RON) as a candidate')))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
      ])
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['exclusive'] = BaseFieldDefinition::create('boolean')
      ->setLabel((t('Exclusive nominations')))
      ->setDescription(t('A candidate can only stand for one exclusive position per election.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
      ])
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['publish_candidates_automatically'] = BaseFieldDefinition::create('boolean')
      ->setLabel((t('Publish nominations when created')))
      ->setDescription(t('If checked, newly submitted nominations will be published automatically. Otherwise, the approval of an administrator will be expected.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
      ])
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Count
    $fields['count_timestamp'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Time when run'));

    $fields['count_method'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Count method'));

    $fields['count_results_text'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Results in text format'))
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'text_default',
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 40,
        'rows' => 6,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['count_results_html'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Results in HTML format'))
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'text_default',
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 40,
        'rows' => 6,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $totals = ['ballots', 'votes', 'abstentions'];
    foreach ($totals as $total) {
      $fields['count_total_' . $total] = BaseFieldDefinition::create('integer')
        ->setLabel(t('Total ' . $total));
    }

    // We want a historical record of these categories of candidate
    // Because as part of a count we may remove some
    // And users could run counts with different combinations of candidates for fun or profit
    // As posts are revisionable, this history will always be available
    $candidate_groups = ['all', 'published', 'included', 'winning', 'losing'];
    foreach ($candidate_groups as $candidate_group) {
      $fields['count_candidates_' . $candidate_group] = BaseFieldDefinition::create('entity_reference')
        ->setLabel(t('Candidates - ' . $candidate_group))
        ->setSetting('target_type', 'election_candidate');
    }

    $fields['allow_candidate_editing'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Allow candidates to edit their nomination'))
      ->setDescription(t('The candidate user must have the "edit own" permission for the candidate type, regardless of this setting.'))
      ->setSettings([
        'allowed_values' => [
          'false' => t('Never'),
          'true' => t('Always'),
          'nominations' => t('Only while nominations are open'),
          'no_votes' => t('Only while no votes have been cast'),
        ],
      ])
      ->setDefaultValue('false')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['voting_method'] = BaseFieldDefinition::create('plugin_reference')
      ->setLabel(t('Voting method'))
      ->setSettings([
        'target_type' => 'election_voting_method_plugin',
      ])
      ->setDisplayOptions('form', [
        'type' => 'plugin_reference_select',
        'configuration_form' => 'full',
        'provider_grouping' => FALSE,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    static::addElectionStatusesFields($fields, 'election_post');

    return $fields;
  }

  public function getElectionPostType() {
    return ElectionPostType::load($this->bundle());
  }


  public function getCandidates(array $statuses = NULL, $only_published = TRUE) {
    $query = \Drupal::entityQuery('election_candidate');
    $query->condition('election_post', $this->id());

    if ($statuses) {
      $query->condition('candidate_status', $statuses, 'IN');
    }

    if ($only_published) {
      $query->condition('status', 1);
    }

    return ElectionCandidate::loadMultiple($query->execute());
  }

  public function getCandidatesForVoting($ballotCandidateSort = 'random') {
    $candidates = $this->getCandidates(['hopeful'], TRUE);

    if ($ballotCandidateSort == 'random') {
      shuffle($candidates);
    } elseif ($ballotCandidateSort == 'alhpabetical') {
      // @todo
    }
    return $candidates;
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
    return ElectionPostType::load($this->bundle())->getNaming($capital, $plural);
  }

  /**
   */
  public function getCandidateTypesAsLabel() {
    $types = [];
    $candidates = $this->getCandidatesForVoting();
    if (count($candidates) > 0) {
      foreach ($candidates as $candidate) {
        if (!isset($types[$candidate->bundle()])) {
          $types[$candidate->bundle()] = ElectionCandidateType::load($candidate->bundle())->getNaming(TRUE, TRUE);
        }
      }
    } else {
      $post_type = ElectionPostType::load($this->bundle());
      foreach ($post_type->getAllowedCandidateTypes() as $candidate_type) {
        $types[$candidate_type->id()] = $candidate_type->getNaming(TRUE, TRUE);
      }
    }

    $result = implode('/', $types);
    return $result;
  }

  public function getActionLinks(AccountInterface $account = NULL, $phasesToInclude = []) {
    $eligibilityService = \Drupal::service('election.post_eligibility_checker');

    $actions = [];

    $phases = $this->getElection()->getEnabledPhases();
    if (isset($phasesToInclude) && count($phasesToInclude) > 0) {
      $phases = array_intersect($phases, $phasesToInclude);
    }
    foreach ($phases as $phase) {
      $eligibleForPhase = $eligibilityService->evaluateEligibility($account, $this, $phase, TRUE);
      if ($eligibleForPhase) {
        $url = Url::fromRoute('entity.election_post.' . $phase, ['election_post' => $this->id()]);
        if ($url) {
          $actions[] = [
            'title' => t('@label', ['@label' => Election::getPhaseAction($phase)]),
            'link' => $url->toString(),
            'button_type' => 'primary',
          ];
        }
      }

      if ($phase == 'nominations' && ($eligibleForPhase || $account->hasPermission('add election candidate entities without eligibility'))) {
        foreach ($this->getElectionPostType()->getAllowedCandidateTypes() as $election_candidate_type) {
          $url = Url::fromRoute('entity.election_candidate.add_to_election_post', [
            'election_post' => $this->id(),
            'election_candidate_type' => $election_candidate_type->id(),
          ]);
          if ($url) {
            $label = $eligibleForPhase ? $election_candidate_type->getActionNaming() : 'Create ' . $election_candidate_type->getNaming();
            $actions[] = [
              'title' => t(
                '@label',
                [
                  '@label' => $label,
                ]
              ),
              'link' => $url->toString(),
              'button_type' => 'secondary',
            ];
          }
        }
      }
    }

    // Eligibility button:
    $url = Url::fromRoute('entity.election_post.eligibility', [
      'election_post' => $this->id(),
    ]);
    $actions[] = [
      'title' => t('Your eligibility'),
      'link' => $url->toString(),
      'button_type' => 'secondary',
    ];

    return $actions;
  }


  public function getUserEligibilityCacheTag(AccountInterface $account, string $phase) {
    return implode(':', [
      'election',
      'eligibility',
      $this->getEntityTypeId(),
      $this->id(),
      $account->id(),
      $phase,
    ]);
  }

  public function getUserEligibilityCacheTags(AccountInterface $account, string $phase) {
    $eligibilityCacheTag = [$this->getUserEligibilityCacheTag($account, $phase)];

    $otherTags = [];

    // Future plan is to have one tag, and manually invalidate it
    // By detecting changes to the election, post, candidates etc that might affect it
    // But otherwise not invalidating it
    // Which is excessive but will alow for much fewer eligibility checks
    // On large elections.

    // Entity tags
    $otherTags = array_merge($this->getElection()->getCacheTags(), $this->getCacheTags());

    // Get conditions and add relevant tags from there
    $conditions = $this->getConditions($phase);
    foreach ($conditions as $condition) {
      $otherTags = array_merge($otherTags, $condition->getCacheTags($this, $account));
    }

    return Cache::mergeTags($eligibilityCacheTag, $otherTags);
  }

  public function getConditions(string $phase) {
    if (!\Drupal::moduleHandler()->moduleExists('election_conditions')) {
      return [];
    }

    $postConditions = [];

    // Get election conditions if we're inheriting
    if ($this->conditions_inherit_election->value == 'inherit') {
      $election = $this->getElection();
      $phaseToGet = $election->get('conditions_' . $phase . '_same_as')->value;
      if ($phaseToGet != 'none') {
        $postConditions = $election->getConditionsForField('conditions_' . $phaseToGet);
      }
    }

    $phaseToGet = $this->get('conditions_' . $phase . '_same_as')->value;
    if ($phaseToGet != 'none') {
      $postConditions = array_merge($postConditions, $this->getConditionsForField('conditions_' . $phaseToGet));
    }

    return $postConditions;
  }

  public function formatEligibilityRequirements(array $requirements, $onlyFailedRequirements = FALSE) {
    $formattedRequirements = [];

    foreach ($requirements as $requirement) {
      if (!$onlyFailedRequirements || $requirement->isFailed()) {
        $formattedRequirements[] = $requirement;
      }
    }

    // sort by pass
    usort($formattedRequirements, function ($a, $b) {
      return ($a->isPassed() ? 1 : -1) - ($b->isPassed() ? 1 : -1);
    });

    return $formattedRequirements;
  }

  public function formatEligibilityRequirementsTable(array $requirements, $phase) {
    $formattedRequirements = $this->formatEligibilityRequirements($requirements);
    $render = \Drupal::service('conditions_plugin_reference.conditions_renderer')->requirementsTable($requirements);
    $render['#caption'] = Election::getPhaseName($phase);
    return $render;
  }

  public function getBallots($confirmedOnly = FALSE) {
    $query = \Drupal::entityQuery('election_ballot');
    $query->condition('election_post', $this->id());

    if ($confirmedOnly) {
      $query->condition('confirmed', TRUE);
    }

    return ElectionBallot::load($query->execute());
  }

  public function countBallots($confirmedOnly = FALSE) {
    return count($this->getBallots($confirmedOnly));
  }
}
