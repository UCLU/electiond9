<?php

namespace Drupal\election;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface ElectionStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Election revision IDs for a specific Election.
   *
   * @param \Drupal\election\Entity\ElectionInterface $entity
   *   The Election entity.
   *
   * @return int[]
   *   Election revision IDs (in ascending order).
   */
  public function revisionIds(ElectionInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Election author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Election revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\election\Entity\ElectionInterface $entity
   *   The Election entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ElectionInterface $entity);

  /**
   * Unsets the language for all Election with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
