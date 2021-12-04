<?php

namespace Drupal\election\EventSubscriber;

use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\election\Entity\Election;
use Drupal\election\Entity\ElectionPost;
use Drupal\election\Service\ElectionPostEligibilityChecker;

class RedirectOn403Subscriber extends HttpExceptionSubscriberBase {

  protected $currentUser;

  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  protected function getHandledFormats() {
    return ['html'];
  }

  public function on403(GetResponseForExceptionEvent $event) {
    $request = $event->getRequest();
    $route_name = $request->attributes->get('_route');

    // Redirect to election post with eligibility explanation
    $routesToControl = [
      'entity.election_ballot.add_form',
    ];
    $phases = Election::ELECTION_PHASES;
    foreach ($phases as $phase) {
      $routesToControl[] = "entity.election_post." . $phase;
    }

    $election_post_route = in_array($route_name, $routesToControl);
    if (!$election_post_route) {
      return;
    }

    $uri = FALSE;
    if ($this->currentUser->isAnonymous()) {
      $query = $request->query->all();
      $query['destination'] = Url::fromRoute('<current>')->toString();
      $uri = Url::fromRoute('user.login', [], ['query' => $query])->toString();
    } else {
      $routeMatch = RouteMatch::createFromRequest($request);
      $election_post_id = $routeMatch->getParameters()->get('election_post');
      $election_post = ElectionPost::load($election_post_id);
      if (!$election_post) {
        return;
      }

      if ($route_name == 'entity.election_ballot.add_form') {
        $phase = 'voting';
      } else {
        $phase = substr($route_name, strrpos($route_name, '.') + 1);
      }

      $requirements = ElectionPostEligibilityChecker::evaluateEligibilityRequirements($this->currentUser, $election_post, $phase, TRUE, TRUE);

      if (!ElectionPostEligibilityChecker::checkRequirementsForEligibility($requirements)) {
        $formattedFailedRequirements = $election_post->formatEligibilityRequirements($requirements, TRUE);

        $message = t('You cannot currently access this post due to not meeting the following requirements: @requirements', [
          '@requirements' => implode(', ', array_column($formattedFailedRequirements, 'title')),
        ]);
        \Drupal::messenger()->addError($message);
        $uri = Url::fromRoute(
          'entity.election_post.canonical',
          [
            'election_post' => $election_post->id(),
          ],
          [
            'query' => [
              'from_access_denied' => TRUE,
            ]
          ]
        )->toString();
      }
    }

    if ($uri) {
      $returnResponse = new TrustedRedirectResponse($uri);
      $event->setResponse($returnResponse);
    }
  }
}
