<?php

namespace Drupal\election\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\election\Entity\ElectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ElectionController.
 *
 *  Returns responses for Election routes.
 */
class ElectionController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Displays a Election revision.
   *
   * @param int $election_revision
   *   The Election revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($election_revision) {
    $election = $this->entityTypeManager()->getStorage('election')
      ->loadRevision($election_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('election');

    return $view_builder->view($election);
  }

  /**
   * Page title callback for a Election revision.
   *
   * @param int $election_revision
   *   The Election revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($election_revision) {
    $election = $this->entityTypeManager()->getStorage('election')
      ->loadRevision($election_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $election->label(),
      '%date' => $this->dateFormatter->format($election->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Election.
   *
   * @param \Drupal\election\Entity\ElectionInterface $election
   *   A Election object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(ElectionInterface $election) {
    $account = $this->currentUser();
    $election_storage = $this->entityTypeManager()->getStorage('election');

    $langcode = $election->language()->getId();
    $langname = $election->language()->getName();
    $languages = $election->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $election->label()]) : $this->t('Revisions for %title', ['%title' => $election->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all election revisions") || $account->hasPermission('administer elections')));
    $delete_permission = (($account->hasPermission("delete all election revisions") || $account->hasPermission('administer elections')));

    $rows = [];

    $vids = $election_storage->revisionIds($election);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\election\ElectionInterface $revision */
      $revision = $election_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $election->getRevisionId()) {
          $link = $this->l($date, new Url('entity.election.revision', [
            'election' => $election->id(),
            'election_revision' => $vid,
          ]));
        } else {
          $link = $election->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        } else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
                Url::fromRoute('entity.election.translation_revert', [
                  'election' => $election->id(),
                  'election_revision' => $vid,
                  'langcode' => $langcode,
                ]) :
                Url::fromRoute('entity.election.revision_revert', [
                  'election' => $election->id(),
                  'election_revision' => $vid,
                ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.election.revision_delete', [
                'election' => $election->id(),
                'election_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['election_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

  /**
   * Go into ballot loop.
   *
   * @param ElectionInterface $election
   */
  public function startVoting(ElectionInterface $election) {
    $account = \Drupal::currentUser();
    $alreadyDoneOrSkippedIds = $_SESSION[$election->id() . '-done_or_skipped'] ?? [];
    $postID = $election->getNextPostId($account, NULL, $alreadyDoneOrSkippedIds);
    return $this->redirect('entity.election_post.voting', [
      'election_post' => $postID,
    ]);
  }
}
