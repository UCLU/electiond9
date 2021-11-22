<?php

namespace Drupal\election;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\election\Entity\ElectionPostInterface;

/**
 * Defines the storage handler class for Election post entities.
 *
 * This extends the base storage class, adding required special handling for
 * Election post entities.
 *
 * @ingroup election
 */
interface ElectionPostStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Election post revision IDs for a specific Election post.
   *
   * @param \Drupal\election\Entity\ElectionPostInterface $entity
   *   The Election post entity.
   *
   * @return int[]
   *   Election post revision IDs (in ascending order).
   */
  public function revisionIds(ElectionPostInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Election post author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Election post revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\election\Entity\ElectionPostInterface $entity
   *   The Election post entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ElectionPostInterface $entity);

  /**
   * Unsets the language for all Election post with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
