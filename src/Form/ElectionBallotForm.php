<?php

namespace Drupal\election\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\election\Entity\ElectionBallotInterface;
use Drupal\election\Entity\ElectionBallotVote;
use Drupal\election\Entity\ElectionPost;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Election ballot edit forms.
 *
 * @ingroup election
 */
class ElectionBallotForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $election_post = NULL) {

    $form = parent::buildForm($form, $form_state);

    if (is_numeric($election_post)) {
      $election_post = \Drupal::entityTypeManager()->getStorage('election_post')->load($election_post);
      $form_state->set('election_post', $election_post->id());
    }

    if (!$election_post) {
      $form['#title'] = 'No position found';
      $form['info']['#markup'] = 'No election post found.';
      return $form;
    }

    $form['info']['#markup'] = $election_post->description->value;

    $form['#attached']['library'][] = 'election/ballot';

    $form['election_post']['widget'][0]['target_id']['#default_value'] = $election_post;
    $form['election_post']['#disabled'] = TRUE;

    $election = $election_post->getElection();

    if ($election_post->allow_equal_ranking->value) {
      $form['#attributes']['class'][] = 'allow-equal';
    }

    $form['#title'] = t('Vote for @post', ['@post' => $election_post->label()]);

    // Allow voting methods to modify

    // Allow plugins to modify
    // TODO

    $voteLabel = 'Ranking';

    // List voting inputs
    // $votingMethod = $election->get('voting_method')->value;
    $ballotDisplay = 'table';
    $ballotCandidateSort = $election->get('ballot_candidate_sort')->value;
    $candidates = $election_post->getCandidatesForVoting($ballotCandidateSort);

    $candidateLabel = 'Option';
    $candidateLabelPlural = 'Options';

    // @todo load these
    $rankedVoting = TRUE;
    $form_state->set('ranked_voting', $rankedVoting);

    $exhaustive = FALSE;
    $form_state->set('exhaustive_voting', $exhaustive);
    $maximumChoices = 1;

    $include_reopen_nominations = $election_post->include_reopen_nominations->value == TRUE;

    $count = count($candidates);

    $rankingOptions = [];
    if ($rankedVoting) {
      // Add a 'No preference' option which means the candidate would not be ranked.
      if (!$exhaustive) {
        $rankingOptions['NONE'] = t('No preference');
      }
      $pref_limit = $count;
      if ($include_reopen_nominations) {
        $pref_limit++;
      }
      for ($i = 1; $i <= $pref_limit; $i++) {
        $ordinal = static::getOrdinal($i);
        $rankingOptions[$i] = t($ordinal);
      }
    } else {
      $rankingOptions['NONE'] = t('Not selected');
      $rankingOptions['1'] = t('Selected');
    }

    if ($ballotDisplay == 'table') {
      $rows = [];

      $form['rankings'] =
        [
          '#type' => 'table',
          '#header' => [
            t($candidateLabelPlural),
            t($voteLabel),
          ],
          '#rows' => $rows,
          '#empty' => t('No candidates.'),
        ];

      if ($include_reopen_nominations) {
        $candidates[] = [
          'id' => 'ron',
          'name' => t('Re-open nominations'),
        ];
      }

      $i = 0;
      foreach ($candidates as $candidate) {
        if (is_array($candidate) && isset($candidate['name'])) {
          $form['rankings'][$i]['candidate'] = ['#markup' => $candidate['name']];
          $id = $candidate['id'];
        } else {
          $view_builder = \Drupal::entityTypeManager()->getViewBuilder('election_candidate');
          $pre_render = $view_builder->view($candidate, 'ballot_table');
          $form['rankings'][$i]['candidate'] = ['#markup' => render($pre_render)];
          $id = $candidate->id();
        }

        $form['rankings'][$i][$id] = [
          '#type' => 'select',
          '#title' => 'Ranking',
          '#title_display' => 'invisible',
          '#options' => $rankingOptions,
          '#chosen' => FALSE,
          '#attributes' => [
            'class' => ['election-candidate-preference-select'],
          ],
        ];

        $i++;
      }
    }

    // Buttons
    $form['actions'] = [
      '#weight' => 20,
      '#type' => 'actions',
    ];

    $form['actions']['edit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit vote'),
      '#weight' => 20,
      '#button_type' => 'primary',
      '#submit' => ["::save"],
      '#id' => 'submit-vote',
    );

    if ($election_post->abstentions_allowed->value == 1) {
      $form['actions']['abstain'] = array(
        '#type' => 'submit',
        '#value' => t('Abstain from voting'),
        '#weight' => 21,
        '#button_type' => 'danger',
        '#submit' => ["::save"],
        '#id' => 'submit-abstain',
      );
    }

    if ($election_post->skip_allowed->value == 1) {
      $form['actions']['skip'] = array(
        '#type' => 'submit',
        '#value' => t('Skip @name for now', ['@name' => $election_post->getElectionPostType()->getNaming()]),
        '#weight' => 22,
        '#button_type' => 'secondary',
        '#submit' => ["::save"],
        '#id' => 'submit-skip',
      );
    }

    $candidatesList = views_embed_view('election_candidates_for_post', 'embed_ballot_full');
    $form['candidates_full'] = [
      '#weight' => 30,
      '#prefix' => '<h2>' . $election_post->getCandidateTypesAsLabel() . '</h2>',
      '#markup' => render($candidatesList),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $triggering_element = $form_state->getTriggeringElement();
    $action = $triggering_element['#id'];

    $election_post = ElectionPost::load($form_state->get('election_post'));

    $rankings = $form_state->getValue('rankings');
    $rankingsUsed = [];
    foreach ($rankings as $index => $pair) {
      foreach ($pair as $candidate_id => $ranking) {
        // Ensure no equal ranking if not allowed:
        if (
          $ranking != 'NONE' &&
          in_array($ranking, $rankingsUsed) &&
          !$election_post->allow_equal_ranking->value
        ) {
          $form_state->setErrorByName('rankings', $this->t('This post does not allow equal rankings.'));
        }


        $rankingsUsed[] = $ranking;

        if ($form_state->get('ranked_voting')) {
        } else {
        }
      }
    }
    // Ensure exhaustive ranking if required:
    if ($form_state->get('exhaustive_voting') && in_array('NONE', $rankingsUsed)) {
      $form_state->setErrorByName('rankings', $this->t('Must rank all options.'));
    }

    if (count($rankingsUsed) > 0 && $action == 'submit-abstain' && !in_array('NONE', $rankingsUsed)) {
      $form_state->setErrorByName('rankings', $this->t('You have abstained but there are preferences placed. Please remove preferences.'));
    }
  }

  public static function submitFormVote(ElectionBallotInterface $ballot, array $form, FormStateInterface $form_state) {
    $rankings = $form_state->getValue('rankings');

    // Save votes as election_ballot_vote entities
    foreach ($rankings as $index => $pair) {
      foreach ($pair as $candidate_id => $ranking) {
        $vote = ElectionBallotVote::create([
          'ballot_id' => $ballot->id(),
          'candidate_id' => $candidate_id == 'ron' ? NULL : $candidate_id,
          'ron' => $candidate_id == 'ron' ? 1 : 0,
          'ranking' => $ranking,
        ]);
        $vote->save();
      }
    }
  }

  /**
   * This is the default save for the entity form.
   *
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $action = $triggering_element['#id'];

    $entity = $this->entity;

    $election_post = ElectionPost::load($form_state->get('election_post'));
    $form_state->setValue('election_post_id', $election_post->id());

    $messageParams = [
      '%position_label' => $election_post->label(),
    ];

    switch ($action) {
      case 'submit-skip':
        $this->messenger()->addMessage(
          $this->t(
            'Skipped position %position_label - you can return to vote as long as voting is still open.',
            $messageParams
          )
        );
        static::addSkippedPost($election_post);
        break;

      case 'submit-abstain':
        $this->messenger()->addMessage(
          $this->t(
            'Abstention recorded for %position_label.',
            $messageParams
          )
        );
        $form_state->setValue('abstained', TRUE);
        $status = parent::save($form, $form_state);
        static::addDonePost($election_post);
        break;

      case 'submit-vote':
        $this->messenger()->addMessage(
          $this->t(
            'Vote recorded for %position_label.',
            $messageParams
          )
        );
        $status = parent::save($form, $form_state);
        static::submitFormVote($this->entity, $form, $form_state);
        static::addDonePost($election_post);
        break;
    }

    $this->goNext($election_post, $form_state);
  }

  public function goNext($election_post, &$form_state) {
    $election = $election_post->getElection();
    $ballotBehaviour = $election->ballot_behaviour->value;
    if ($ballotBehaviour == 'one_by_one') {
      $form_state->setRedirect('entity.election_post.canonical', ['election_post' => $election_post->id()]);
    } else {
      $nextPostId = $election->getNextPostId(\Drupal::currentUser(), $election_post, static::getDoneOrSkippedPosts($election));
      if ($nextPostId) {
        $form_state->setRedirect('entity.election_post.voting', ['election_post' => $nextPostId]);
      } else {
        $this->messenger()->addMessage(
          $this->t(
            'All positions voted, abstained or skipped.',
          )
        );
        $_SESSION[$election->id() . '_skipped'] = [];
        $form_state->setRedirect('entity.election.canonical', ['election' => $election->id()]);
      }
    }
  }

  public static function getDoneOrSkippedPosts($election) {
    return array_merge(
      $_SESSION[$election->id() . '_done'],
      $_SESSION[$election->id() . '_skipped']
    );
  }

  public static function addDonePost($election_post) {
    $election = $election_post->getElection();
    $key = $election->id() . '_done';
    if (!isset($_SESSION[$key])) {
      $_SESSION[$key] = [];
    }
    $_SESSION[$key][] = $election_post->id();
  }

  public static function addSkippedPost($election_post) {
    $election = $election_post->getElection();
    $key = $election->id() . '_skipped';
    if (!isset($_SESSION[$key])) {
      $_SESSION[$key] = [];
    }
    $_SESSION[$key][] = $election_post->id();
  }

  /**
   * Find the ordinal of a number.
   */
  public static function getOrdinal($num) {
    $ends = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
    if ($num % 100 >= 11 && $num % 100 <= 13) {
      $ord = $num . 'th';
    } else {
      $ord = $num . $ends[$num % 10];
    }
    return $ord;
  }
}
