<?php

namespace Drupal\election;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\election\Entity\Election;
use Drupal\election\Entity\ElectionPost;

trait ElectionStatusesTrait {

  // @todo validate scheduling

  public static function addElectionStatusesFields(&$fields, string $entity_type) {
    // Create nominations and voting status and opening and closing times
    $weightCounter = 0;
    foreach (Election::ELECTION_PHASES as $phase) {
      $name = Election::getPhaseName($phase);

      $weightCounter++;

      $defaultValue = 'closed';

      $allowedValues = [
        'closed' => 'Closed',
        'open' => 'Open',
        'scheduled' => 'Scheduled',
        'disabled' => 'Disabled for this post',
      ];

      if ($entity_type == 'election_post') {
        $allowedValues = [
          'inherit' => 'Inherit from election',
        ] + $allowedValues;
        $defaultValue = 'inherit';
      } else {
        if ($phase == 'interest') {
          $defaultValue = 'disabled';
        }
      }

      if ($phase == 'voting') {
        unset($allowedValues['disabled']);
      }

      $fields['status_' . $phase] = BaseFieldDefinition::create('list_string')
        ->setLabel(t($name . ' status'))
        ->setDescription(t('Whether @name is open, closed or scheduled.', ['@name' => strtolower($name)]))
        ->setSettings([
          'allowed_values' => $allowedValues,
        ])
        ->setDefaultValue($defaultValue)
        ->setDisplayOptions('view', [
          'region' => 'hidden',
          'label' => 'inline',
          'type' => 'string',
        ])
        ->setRequired(TRUE)
        ->setDisplayOptions('form', [
          'type' => 'options_select',
          'weight' => $weightCounter,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);

      $scheduling_statuses = Election::SCHEDULING_STATES;
      foreach ($scheduling_statuses as $scheduling_status) {
        $fields['status_' . $phase . '_' . $scheduling_status] = BaseFieldDefinition::create('datetime')
          ->setLabel(t($name . ' ' . $scheduling_status . ' time'))
          ->setDescription(t(
            'Date and time to @status @name (if scheduled)',
            [
              '@status' => $scheduling_status,
              '@name' => strtolower($name),
            ]
          ))
          ->setSettings([
            'datetime_type' => 'datetime',
          ])
          ->setDefaultValue('')
          ->setDisplayOptions('view', [
            'region' => 'hidden',
            'label' => 'inline',
            'type' => 'datetime_default',
            'settings' => [
              'format_type' => 'medium',
            ]
          ])
          ->setDisplayOptions('form', [
            'type' => 'datetime_default',
            'weight' => $weightCounter + 1,
          ])
          ->setDisplayConfigurable('form', TRUE)
          ->setDisplayConfigurable('view', TRUE);
      }
    }
  }

  public static function addStatusesStatesToForm(&$form) {
    $phases = Election::ELECTION_PHASES;
    $scheduling_statuses = Election::SCHEDULING_STATES;
    foreach ($phases as $phase) {
      foreach ($scheduling_statuses as $scheduling_status) {
        $form['status_' . $phase . '_' . $scheduling_status]['#states'] = [
          'visible' => [
            ':input[name="status_' . $phase . '"]' => ['value' => 'scheduled'],
          ],
        ];
      }
    }
  }

  public function checkStatusForPhase($phase, $checkStatus = 'open') {
    $status = $this->get('status_' . $phase)->value;

    if ($status == 'inherit') {
      $status = $this->getElection()->get('status_' . $phase)->value;
    }

    if ($status == $checkStatus) {
      return TRUE;
    } elseif ($status != $checkStatus) {
      return FALSE;
    } elseif ($checkStatus == 'open' && $status == 'scheduled') {
      $start = $this->get('status_' . $phase . '_open')->value;
      $end = $this->get('status_' . $phase . '_closed')->value;
      dd([$start, $end]);
      return $this->checkNowIsBetweenDates($start, $end);
    }
  }


  public function checkScheduledState($phase) {
    $status = $this->get('status_' . $phase)->value;

    if ($status == 'inherit') {
      $status = $this->getElection()->get('status_' . $phase)->value;
    }

    if ($status != 'scheduled') {
      return NULL;
    }

    $start = $this->get('status_' . $phase . '_open')->value;
    $end = $this->get('status_' . $phase . '_closed')->value;
    dd([$start, $end]);

    $now = new DateTime('now');

    if ($this->checkNowIsBetweenDates($start, $end)) {
      return 'open';
    }

    if ($end < $now) {
      return 'over';
    }

    if ($start >= $now) {
      return 'scheduled';
    }

    return 'scheduled';
  }

  public function checkNowIsBetweenDates($datetimeStart, $datetimeEnd) {
    // TODO
  }

  /**
   * @return array
   */
  public function getEnabledPhases() {
    $election = $this->getEntityTypeId() == 'election_post' ? $this->getElection() : $this;
    $finalPhases = [];
    foreach (Election::ELECTION_PHASES as $phase) {
      $field = 'status_' . $phase;
      if ($election->$field->value != 'disabled') {
        $finalPhases[] = $phase;
      }
    }
    return $finalPhases;
  }

  public function isOpenOrPartiallyOpen($phase, $inherit = TRUE) {
    $statuses = $this->getPhaseStatuses($inherit);
    return $statuses[$phase] == 'open' || $statuses[$phase] == 'closed but posts open';
  }

  public function getPhaseStatus($phase, $inherit = TRUE) {
    $statuses = $this->getPhaseStatuses($inherit);
    return $statuses[$phase];
  }

  public function getPhaseStatuses($inherit = TRUE) {
    $results = [];

    $phases = $this->getEnabledPhases();
    foreach ($phases as $phase_key) {
      if ($inherit && $this->getEntityTypeId() == 'election_post' && $this->get('status_' . $phase_key)->value == 'inherit') {
        $electionResults = $this->getElection()->getPhaseStatuses();
        $results[$phase_key] = $electionResults[$phase_key];
      } else {
        $status = $this->get('status_' . $phase_key)->value;
        $text = $status;

        $anyPostsOpen = FALSE;
        if ($this->getEntityTypeId() == 'election') {
          // Check if any posts are open
          $posts = $this->getPosts();
          foreach ($posts as $post) {
            $postPhaseStatuses = $post->getPhaseStatuses(FALSE);
            if ($postPhaseStatuses[$phase_key] == 'open') {
              $anyPostsOpen = TRUE;
              break;
            }
          }
        }

        if ($status == 'open') {
          $text = 'open';
        } elseif ($status == 'closed') {
          $text = 'closed';
          if ($anyPostsOpen) {
            $text .= ' but posts open';
          }
        } elseif ($status == 'scheduled') {
          $dateStatus = $this->checkScheduledState($phase_key);
          if ($dateStatus == 'open') {
            $text = 'open as scheduled'; // TODO
          } elseif ($dateStatus == 'over') {
            $text = ' finished '; // TODO
          } else {
            $text = ' scheduled to open in '; // TODO
          }
          if ($anyPostsOpen) {
            $text .= ' but posts open';
          }
        }


        $results[$phase_key] = $text;
      }
    }

    return $results;
  }

  public function getUserEligibilityInformation(AccountInterface $account, array $phases = NULL) {
    $result = [];

    $debug = TRUE;

    $eligibilityService = \Drupal::service('election.post_eligibility_checker');

    if ($this->getEntityTypeId() == 'election') {
      $election = $this;
    } else {
      $election = $this->getElection();
    }

    $phaseStatuses = $this->getPhaseStatuses();
    $electionPhases = $election->getEnabledPhases();

    if ($phases) {
      $phases = array_intersect($electionPhases, $phases);
    } else {
      $phases = $electionPhases;
    }

    $electionPhaseStatuses = [];
    if ($this->getEntityTypeId() == 'election_post') {
      $electionPhaseStatuses = $this->getElection()->getPhaseStatuses();
    }

    foreach ($phases as $phase) {
      $phase_label = Election::getPhaseName($phase);

      $result[$phase] = [
        'status' => $phaseStatuses[$phase],
        'name' => $phase_label,
      ];

      if ($phaseStatuses[$phase] == 'inherit') {
        $phaseStatuses[$phase] = $electionPhaseStatuses[$phase];
      }

      switch ($phaseStatuses[$phase]) {
        case 'open':
          $status_full = '@phase open';
          break;

        case 'closed':
          $status_full = '@phase closed';
          break;

        case 'closed but posts open':
          $status_full = '@phase closed for election overall but some @posts are open';
          break;

        case 'scheduled':
          $status_full = '@phase scheduled (opens ...)';
          break;

        case 'disabled':
          $status_full = '@phase disabled for this election';
          break;

        default:
          $status_full = '@phase not clear - ' . $phaseStatuses[$phase];
          break;
      }

      if ($this->getEntityTypeId() == 'election') {
        $posts = $this->getPosts();
      } else {
        $posts = [$this];
      }

      foreach ($posts as $post) {
        $requirements = $eligibilityService->evaluateEligibilityRequirements($account, $post, $phase, FALSE, $debug);
        $result[$phase]['ineligibility_reasons'] = [];
        $result[$phase]['already_' . $phase] = FALSE;

        if (!$eligibilityService->checkRequirementsForEligibility($requirements)) {
          $result[$phase]['eligible'] = FALSE;
          $result[$phase]['eligibility_label'] = t('Not eligible to @action', ['@action' => strtolower(Election::getPhaseAction($phase))]);

          $notAlreadyDone = $requirements['not_already_' . $phase];
          $result[$phase]['already_' . $phase] = isset($requirements['not_already_' . $phase]) && $notAlreadyDone->isFailed();
          if ($phase == 'voting') {
            // dd(isset($requirements['not_already_' . $phase]), $requirements['not_already_' . $phase]->isFailed(), $result[$phase]['already_' . $phase]);
          }

          $formattedFailedRequirements = $post->formatEligibilityRequirements($requirements, TRUE);
          $result[$phase]['ineligibility_reasons'] = array_column($formattedFailedRequirements, 'title');
        } else {
          $result[$phase]['eligible'] = TRUE;
          $result[$phase]['eligibility_link'] = Url::fromRoute('entity.election_post.' . $phase, ['election_post' => $this->id()])->toString();
          $result[$phase]['eligibility_label'] = t('@action', ['@action' => Election::getPhaseAction($phase)]);
        }
      }

      $eligibleText = '';
      if ($result[$phase]['already_' . $phase]) {
        $status_full = 'You have already ' . Election::getPhaseActionPastTense($phase, TRUE);
        $result[$phase]['ineligibility_reasons'] = [];
      } else if ($phaseStatuses[$phase] == 'open') {
        $eligibleText = $result[$phase]['eligible'] ? ' and you are eligible' : ' but you are not eligible';
      } else {
        // $eligibleText = isset($result[$phase]['eligible']) && $result[$phase]['eligible'] ? ' though you are eligible' : ' and you are not eligible';
        $eligibleText = '';
      }

      $result[$phase]['status_full'] = t($status_full . '@eligible', [
        '@phase' => $phase_label,
        '@eligible' => $eligibleText,
        '@posts' => 'posts'
      ]);
    }

    return $result;
  }

  /**
   * Produces a nicely formatted string explaining the user's eligibility.
   *
   * @param AccountInterface $account
   *
   * @return [type]
   */
  public function getUserEligibilityFormatted(AccountInterface $account, array $phases = NULL, $separator = ', ', $format = 'simple', $includeLinks = FALSE) {
    if (count($phases) == 0) {
      $phases = NULL;
    }

    $eligibility = $this->getUserEligibilityInformation($account, $phases);

    if ($format == 'links_only') {
      $includeLinks = TRUE;
    }

    $groupedPhases = [];
    $orderOfPhases = ['open', 'scheduled', 'closed'];

    $results = [];
    foreach ($eligibility as $phase => $data) {
      if ($phases && !in_array($phase, $phases)) {
        continue;
      }
      if (!isset($groupedPhases[$data['status']])) {
        $groupedPhases[$data['status']] = [];
      }
      $groupedPhases[$data['status']][$phase] = $data;
    }

    // For simple, only show the primary phase you care about:
    if ($format == 'simple') {
      foreach ($orderOfPhases as $phase) {
        if (!isset($groupedPhases[$phase])) {
          continue;
        }

        if (count($groupedPhases[$phase]) > 0) {
          $groupedPhases = [$phase => $groupedPhases[$phase]];
        }
      }
    }

    foreach ($orderOfPhases as $phase) {
      if (!isset($groupedPhases[$phase])) {
        continue;
      }

      foreach ($groupedPhases[$phase] as $groupedPhase) {
        $string = '';
        if ($format != 'links_only') {
          $string = $groupedPhase['status_full'];
        }
        if ($includeLinks && $groupedPhase['eligible'] && $groupedPhase['status'] == 'open' && isset($groupedPhase['eligibility_link'])) {
          $string .= ' <a class="btn button" href="' . $groupedPhase['eligibility_link'] . '">' . $groupedPhase['eligibility_label'] . '</a>';
        }
        $results[] = $string;
      }
    }
    $results = array_filter($results);
    return implode($separator, $results);
  }

  public function validateScheduling(&$form, &$form_state) {

    foreach (Election::ELECTION_PHASES as $phase) {
      if (isset($form['status_' . $phase]) && $form['status_' . $phase] == 'scheduled') {
        $phaseName = Election::getPhaseName($phase);

        $priorPhase = isset(Election::ELECTION_PHASES_DEPENDENT_PHASE[$phase]) ? static::ELECTION_PHASES_DEPENDENT_PHASE[$phase] : NULL;

        $opens = $form_state->getValue('status_' . $phase . '_open');
        $closes = $form_state->getValue('status_' . $phase . '_close');

        if (!$opens || !$closes) {
          $form_state->setErrorByName(
            'status_' . $phase,
            $this->t(
              'You must set the opening and closing times when the status is Scheduled for @phaseName.',
              [
                '@phaseName' => $phaseName,
              ]
            )
          );
        } elseif ($close <= $open) {
          $form_state->setErrorByName(
            'status_' . $phase . '_close',
            $this->t(
              'You must set the closing time to be after the opening time when the status is Scheduled for @phaseName.',
              [
                '@phaseName' => $phaseName,
              ]
            )
          );
        } elseif ($form_state->getValue('status_' . $priorPhase) == 'scheduled') {
          $priorCloses = $form_state->getValue('status_' . $phase . '_close');
          if ($priorCloses > $opens) {

            $form_state->setErrorByName(
              'status_' . $priorPhase . '_close',
              $this->t(
                '@priorPhaseName can only be scheduled so that they close before the start of @phaseName.',
                [
                  '@phaseName' => $phaseName,
                  '@priorPhaseName' => $priorPhaseName,
                ]
              )
            );
          }
        }
      }
    }
  }
}
