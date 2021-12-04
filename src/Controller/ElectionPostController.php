<?php

namespace Drupal\election\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\election\Entity\Election;
use Drupal\election\Entity\ElectionPostInterface;
use Drupal\election\Service\ElectionPostEligibilityChecker;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ElectionPostController.
 *
 *  Returns responses for Election post routes.
 */
class ElectionPostController extends ControllerBase implements ContainerInjectionInterface {

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
   * Displays a Election post revision.
   *
   * @param int $election_post_revision
   *   The Election post revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($election_post_revision) {
    $election_post = $this->entityTypeManager()->getStorage('election_post')
      ->loadRevision($election_post_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('election_post');

    return $view_builder->view($election_post);
  }

  /**
   * Page title callback for a Election post revision.
   *
   * @param int $election_post_revision
   *   The Election post revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($election_post_revision) {
    $election_post = $this->entityTypeManager()->getStorage('election_post')
      ->loadRevision($election_post_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $election_post->label(),
      '%date' => $this->dateFormatter->format($election_post->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Election post.
   *
   * @param \Drupal\election\Entity\ElectionPostInterface $election_post
   *   A Election post object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(ElectionPostInterface $election_post) {
    $account = $this->currentUser();
    $election_post_storage = $this->entityTypeManager()->getStorage('election_post');

    $langcode = $election_post->language()->getId();
    $langname = $election_post->language()->getName();
    $languages = $election_post->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $election_post->label()]) : $this->t('Revisions for %title', ['%title' => $election_post->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all election post revisions") || $account->hasPermission('administer election posts')));
    $delete_permission = (($account->hasPermission("delete all election post revisions") || $account->hasPermission('administer election posts')));

    $rows = [];

    $vids = $election_post_storage->revisionIds($election_post);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\election\ElectionPostInterface $revision */
      $revision = $election_post_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $election_post->getRevisionId()) {
          $link = $this->l($date, new Url('entity.election_post.revision', [
            'election_post' => $election_post->id(),
            'election_post_revision' => $vid,
          ]));
        } else {
          $link = $election_post->link($date);
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
                Url::fromRoute('entity.election_post.translation_revert', [
                  'election_post' => $election_post->id(),
                  'election_post_revision' => $vid,
                  'langcode' => $langcode,
                ]) :
                Url::fromRoute('entity.election_post.revision_revert', [
                  'election_post' => $election_post->id(),
                  'election_post_revision' => $vid,
                ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.election_post.revision_delete', [
                'election_post' => $election_post->id(),
                'election_post_revision' => $vid,
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

    $build['election_post_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

  /**
   * Generates an overview table of older revisions of a Election post.
   *
   * @param \Drupal\election\Entity\ElectionPostInterface $election_post
   *   A Election post object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function getEligibilitySummary(ElectionPostInterface $election_post) {
    $account = $this->currentUser();

    $phases = $election_post->getElection()->getEnabledPhases();
    foreach ($phases as $phase) {
      $requirements = ElectionPostEligibilityChecker::evaluateEligibilityRequirements($account, $election_post, $phase, TRUE, TRUE);
      $build['requirements_table_' . $phase] = $election_post->formatEligibilityRequirementsTable($requirements, $phase);
    }

    return $build;
  }
}
