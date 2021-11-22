<?php

namespace Drupal\election;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\election\Entity\ElectionInterface;

/**
 * Defines the storage handler class for Election entities.
 *
 * This extends the base storage class, adding required special handling for
 * Election entities.
 *
 * @ingroup election
 */
class ElectionStorage extends SqlContentEntityStorage implements ElectionStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ElectionInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {election_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {election_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ElectionInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {election_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('election_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
