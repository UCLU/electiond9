<?php

namespace Drupal\election;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\election\Entity\ElectionCandidateInterface;

/**
 * Defines the storage handler class for Election candidate entities.
 *
 * This extends the base storage class, adding required special handling for
 * Election candidate entities.
 *
 * @ingroup election
 */
class ElectionCandidateStorage extends SqlContentEntityStorage implements ElectionCandidateStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ElectionCandidateInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {election_candidate_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {election_candidate_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ElectionCandidateInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {election_candidate_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('election_candidate_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
