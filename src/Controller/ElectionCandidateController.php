<?php

namespace Drupal\election\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\election\Entity\ElectionCandidateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ElectionCandidateController.
 *
 *  Returns responses for Election candidate routes.
 */
class ElectionCandidateController extends ControllerBase implements ContainerInjectionInterface {

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
   * Displays a Election candidate revision.
   *
   * @param int $election_candidate_revision
   *   The Election candidate revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($election_candidate_revision) {
    $election_candidate = $this->entityTypeManager()->getStorage('election_candidate')
      ->loadRevision($election_candidate_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('election_candidate');

    return $view_builder->view($election_candidate);
  }

  /**
   * Page title callback for a Election candidate revision.
   *
   * @param int $election_candidate_revision
   *   The Election candidate revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($election_candidate_revision) {
    $election_candidate = $this->entityTypeManager()->getStorage('election_candidate')
      ->loadRevision($election_candidate_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $election_candidate->label(),
      '%date' => $this->dateFormatter->format($election_candidate->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Election candidate.
   *
   * @param \Drupal\election\Entity\ElectionCandidateInterface $election_candidate
   *   A Election candidate object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(ElectionCandidateInterface $election_candidate) {
    $account = $this->currentUser();
    $election_candidate_storage = $this->entityTypeManager()->getStorage('election_candidate');

    $langcode = $election_candidate->language()->getId();
    $langname = $election_candidate->language()->getName();
    $languages = $election_candidate->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $election_candidate->label()]) : $this->t('Revisions for %title', ['%title' => $election_candidate->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all election candidate revisions") || $account->hasPermission('administer election candidate entities')));
    $delete_permission = (($account->hasPermission("delete all election candidate revisions") || $account->hasPermission('administer election candidate entities')));

    $rows = [];

    $vids = $election_candidate_storage->revisionIds($election_candidate);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\election\ElectionCandidateInterface $revision */
      $revision = $election_candidate_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $election_candidate->getRevisionId()) {
          $link = $this->l($date, new Url('entity.election_candidate.revision', [
            'election_candidate' => $election_candidate->id(),
            'election_candidate_revision' => $vid,
          ]));
        }
        else {
          $link = $election_candidate->link($date);
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
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.election_candidate.translation_revert', [
                'election_candidate' => $election_candidate->id(),
                'election_candidate_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.election_candidate.revision_revert', [
                'election_candidate' => $election_candidate->id(),
                'election_candidate_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.election_candidate.revision_delete', [
                'election_candidate' => $election_candidate->id(),
                'election_candidate_revision' => $vid,
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

    $build['election_candidate_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
