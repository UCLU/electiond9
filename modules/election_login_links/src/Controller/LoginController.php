<?php

namespace Drupal\election_login_links\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Controller for login.
 */
class LoginController extends ControllerBase implements ContainerInjectionInterface {

  public function login(string $hash_string) {
    // See if there's a hash to use:
    $hashes = \Drupal::entityQuery('election_login_hash')
      ->condition('hash', $hash_string)
      ->execute();

    foreach ($hashes as $hash) {
      // If it's used...
      if ($hash->used->value == 1) {
        \Drupal::messenger()->addError($this->t('This one-time login link has already been used.'));
        return $this->redirectToLoginOrToVoting($hash);
      } elseif ($hash->expiry->value < strtotime('now')) {
        \Drupal::messenger()->addError($this->t('This one-time login link has expired.'));
        return $this->redirectToLoginOrToVoting($hash);
      } else {;
        $hash->used->value = 1;
        $hash->save();

        if (\Drupal::currentUser()->isAnonymous()) {
          // Log in
          user_login_finalize($hash->getOwner());
          $_SESSION['election_only'] = $hash->getElectionId();

          \Drupal::messenger()->addError($this->t('Logged in via one-time login link for elections only.'));
        } else {
          \Drupal::messenger()->addError($this->t('You were already logged in - the one-time login link used has been disabled.'));
        }
        return $this->redirectToLoginOrToVoting($hash);
      }
    }

    // No hash? Just go to elections...
    return $this->redirect('entity.election.collection');
  }

  public function redirectToLoginOrToVoting($hash) {
    $loggedIn = \Drupal::currentUser()->isAuthenticated();
    if ($loggedIn) {
      return $this->redirect('user.login', [], [
        'absolute' => TRUE,
        'query' => [
          'destination' => Url::fromRoute($this->getDestinationRoute($hash), [
            'election' => $hash->getElectionId(),
          ])->toString(),
        ],
      ]);
    } else {
      return $this->redirect($this->getDestinationRoute($hash), ['election' => $hash->getElectionId()]);
    }
  }

  /**
   *
   * @todo make configurable.
   *
   * @return string
   */
  public function getDestinationRoute($hash) {
    $election = $hash->getElection();
    $account = User::load(\Drupal::currentUser()->id());
    if ($election->canVote($account)) {
      return 'entity.election.voting';
    } else {
      return 'entity.election.canonical';
    }
  }
}
