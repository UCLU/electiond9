<?php

namespace Drupal\election_login_links\EventSubscriber;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LoginSubscriber implements EventSubscriberInterface {
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('checkForRedirection');
    return $events;
  }

  /**
   * @param GetResponseEvent $event
   */
  public function checkForRedirection(GetResponseEvent $event) {

    if (!isset($_SESSION['election_only']) || !$_SESSION['election_only']) {
      return $event;
    }

    // Ignore for anonymous and administrator
    $user = \Drupal::currentUser();
    $user = User::load($user->id());
    if ($user->isAnonymous() || $user->id() === '1') {
      return $event;
    }

    // If we're in an election context, we can stay.
    $context_provider = \Drupal::service('election.election_route_context');
    $contexts = $context_provider->getRuntimeContexts([
      'election',
      'election_post',
    ]);
    if (isset($contexts['election']) || isset($contexts['election_post'])) {
      return $event;
    }

    // Otherwise, log out.
    user_logout();

    \Drupal::messenger()->addMessage(t('You were logged in via an election one-time login link, but have visited a non-election page. Please log in again to access other pages.'));

    // Redirect to login.
    $loginlink = Url::fromRoute('user.login', [], [
      'absolute' => TRUE,
      'query' => ['destination' => \Drupal::request()->getRequestUri()],
    ])->toString();
    $event->setResponse(new TrustedRedirectResponse($loginlink));

    return $event;
  }
}
