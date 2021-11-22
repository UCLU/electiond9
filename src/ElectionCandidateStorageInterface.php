<?php

namespace Drupal\election;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface ElectionCandidateStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Election candidate revision IDs for a specific Election candidate.
   *
   * @param \Drupal\election\Entity\ElectionCandidateInterface $entity
   *   The Election candidate entity.
   *
   * @return int[]
   *   Election candidate revision IDs (in ascending order).
   */
  public function revisionIds(ElectionCandidateInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Election candidate author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Election candidate revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\election\Entity\ElectionCandidateInterface $entity
   *   The Election candidate entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ElectionCandidateInterface $entity);

  /**
   * Unsets the language for all Election candidate with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
