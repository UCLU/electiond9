<?php

namespace Drupal\election\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Election candidate revision.
 *
 * @ingroup election
 */
class ElectionCandidateRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The Election candidate revision.
   *
   * @var \Drupal\election\Entity\ElectionCandidateInterface
   */
  protected $revision;

  /**
   * The Election candidate storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $electionCandidateStorage;

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
    $instance->electionCandidateStorage = $container->get('entity_type.manager')->getStorage('election_candidate');
    $instance->connection = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'election_candidate_revision_delete_confirm';
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
    return new Url('entity.election_candidate.version_history', ['election_candidate' => $this->revision->id()]);
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
  public function buildForm(array $form, FormStateInterface $form_state, $election_candidate_revision = NULL) {
    $this->revision = $this->ElectionCandidateStorage->loadRevision($election_candidate_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->ElectionCandidateStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Election candidate: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger()->addMessage(t('Revision from %revision-date of Election candidate %title has been deleted.', ['%revision-date' => format_date($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.election_candidate.canonical',
       ['election_candidate' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {election_candidate_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.election_candidate.version_history',
         ['election_candidate' => $this->revision->id()]
      );
    }
  }

}
