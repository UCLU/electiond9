<?php

namespace Drupal\election\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Election post revision.
 *
 * @ingroup election
 */
class ElectionPostRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The Election post revision.
   *
   * @var \Drupal\election\Entity\ElectionPostInterface
   */
  protected $revision;

  /**
   * The Election post storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $electionPostStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->electionPostStorage = $container->get('entity_type.manager')->getStorage('election_post');
    $instance->connection = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'election_post_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => format_date($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.election_post.version_history', ['election_post' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $election_post_revision = NULL) {
    $this->revision = $this->ElectionPostStorage->loadRevision($election_post_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->ElectionPostStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Election post: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger()->addMessage(t('Revision from %revision-date of Election post %title has been deleted.', ['%revision-date' => format_date($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.election_post.canonical',
      [
        'election' => $this->revision->getElection()->id(),
        'election_post' => $this->revision->id(),
      ]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {election_post_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.election_post.version_history',
        [
          'election' => $this->revision->getElection()->id(),
          'election_post' => $this->revision->id(),
        ]
      );
    }
  }
}
