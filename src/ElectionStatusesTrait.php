<?php

namespace Drupal\election;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\election\Entity\Election;

trait ElectionStatusesTrait {

  public static function addElectionStatusesFields(&$fields, string $entity_type) {
    // Create nominations and voting status and opening and closing times
    $statuses = Election::ELECTION_PHASES;
    $weightCounter = 0;
    foreach ($statuses as $key => $name) {
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
      }

      if ($key == 'voting') {
        unset($allowedValues['disabled']);
      }

      $fields['status_' . $key] = BaseFieldDefinition::create('list_string')
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
      foreach ($scheduling_statuses as $scheduling_status => $scheduling_status_name) {
        $fields['status_' . $key . '_' . $scheduling_status] = BaseFieldDefinition::create('datetime')
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
    $statuses = Election::ELECTION_PHASES;
    $scheduling_statuses = Election::SCHEDULING_STATES;
    foreach ($statuses as $key => $name) {
      foreach ($scheduling_statuses as $scheduling_status => $status_name) {
        $form['status_' . $key . '_' . $scheduling_status]['#states'] = [
          'visible' => [
            ':input[name="status_' . $key . '"]' => ['value' => 'scheduled'],
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
    foreach (Election::ELECTION_PHASES as $phase => $full) {
      $field = 'status_' . $phase;
      if ($election->$field != 'disabled') {
        $finalPhases[$phase] = $full;
      }
    }
    return $finalPhases;
  }

  public function getPhaseStatuses() {
    $results = [];

    $phases = $this->getEnabledPhases();
    foreach ($phases as $phase_key => $phase_name) {
      if ($this->getEntityTypeId() == 'election_post' && $this->get('status_' . $phase_key) == 'inherit') {
        $electionResults = $this->getElection->getPhaseStatuses();
        $results[$phase_key] = $electionResults[$phase_key];
      } else {
        $status = $this->get('status_' . $phase_key)->value;
        $text = $status;
        if ($status == 'open') {
          $text = 'open';
        } elseif ($status == 'closed') {
          $text = 'closed';
        } elseif ($status == 'scheduled') {
          $dateStatus = $this->checkScheduledState($phase_key);
          if ($dateStatus == 'open') {
            $text = 'open as scheduled'; // TODO
          } elseif ($dateStatus == 'over') {
            $text = ' finished '; // TODO
          } else {
            $text = ' scheduled to open in '; // TODO
          }
        }
        $results[$phase_key] = $text;
      }
    }

    return $results;
  }

  public function getUserEligibility(AccountInterface $account, array $phases = NULL) {
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

    foreach ($phases as $phase => $full) {
      $result[$phase] = [
        'status' => $phaseStatuses[$phase],
        'name' => $full,
      ];

      if ($phaseStatuses[$phase] == 'inherit') {
        $phaseStatuses[$phase] = $this->getElection()->getPhaseStatuses()[$phase];
      }
      switch ($phaseStatuses[$phase]) {
        case 'open':
          $status_full = '@phase open';
          break;

        case 'closed':
          $status_full = '@phase closed';
          break;

        case 'scheduled':
          $status_full = '@phase scheduled (opens ...)';
          break;
      }

      if ($this->getEntityTypeId() == 'election') {
        $posts = $this->getPosts();
      } else {
        $posts = [$this];
      }

      foreach ($posts as $post) {
        $ineligibility_reasons = $eligibilityService->checkEligibility($account, $post, $phase, FALSE, TRUE, $debug);
        if (count($ineligibility_reasons) > 0) {
          $result[$phase]['eligible'] = FALSE;
          $result[$phase]['eligibility_label'] = t('Not eligible to @action', ['@action' => $full]);
          $result[$phase]['ineligibility_reasons'] = $ineligibility_reasons;
        } else {
          $result[$phase]['eligible'] = TRUE;
          $result[$phase]['eligibility_link'] = Url::fromRoute('entity.election_post.' . $phase, ['election_post' => $this->id()])->toString();
          $result[$phase]['eligibility_label'] = t('Click here to @action', ['@action' => $full]);
        }
      }

      if ($phaseStatuses[$phase] == 'open') {
        $eligibleText = $result[$phase]['eligible'] ? ' and you are eligible' : ' but you are not eligible';
      } else {
        // $eligibleText = isset($result[$phase]['eligible']) && $result[$phase]['eligible'] ? ' though you are eligible' : ' and you are not eligible';
        $eligibleText = '';
      }

      $result[$phase]['status_full'] = t($status_full . '@eligible', [
        '@phase' => $full,
        '@eligible' => $eligibleText,
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
    $eligibility = $this->getUserEligibility($account, $phases);

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
    if ($format = 'simple') {
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
          $string .= ' <a href="' . $groupedPhase['eligibility_link'] . '">' . $groupedPhase['eligibility_label'] . '</a>';
        }
        $results[] = $string;
      }
    }
    return implode($separator, $results);
  }
}
