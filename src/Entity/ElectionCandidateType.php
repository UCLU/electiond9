<?php

namespace Drupal\election\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Election candidate type entity.
 *
 * @ConfigEntityType(
 *   id = "election_candidate_type",
 *   label = @Translation("Election candidate type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\election\ElectionCandidateTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\election\Form\ElectionCandidateTypeForm",
 *       "edit" = "Drupal\election\Form\ElectionCandidateTypeForm",
 *       "delete" = "Drupal\election\Form\ElectionCandidateTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\election\ElectionCandidateTypeHtmlRouteProvider",
 *     },
 *   },
 *   bundle_of = "election_candidate",
 *   config_prefix = "election_candidate_type",
 *   config_export = {
 *     "id",
 *     "label",
 *     "naming_candidate_singular",
 *     "naming_candidate_plural",
 *     "naming_candidate_action",
 *   },
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/election/election_candidate_type/{election_candidate_type}",
 *     "add-form" = "/admin/election/election_candidate_type/add",
 *     "edit-form" = "/admin/election/election_candidate_type/{election_candidate_type}/edit",
 *     "delete-form" = "/admin/election/election_candidate_type/{election_candidate_type}/delete",
 *     "collection" = "/admin/election/election_candidate_type"
 *   }
 * )
 */
class ElectionCandidateType extends ConfigEntityBundleBase implements
    ElectionCandidateTypeInterface {
    /**
     * The Election candidate type ID.
     *
     * @var string
     */
    protected $id;

    /**
     * The Election candidate type label.
     *
     * @var string
     */
    protected $label;

    /**
     * The Election type label.
     *
     * @var string
     */
    public $naming_candidate_singular;

    /**
     * The Election type label.
     *
     * @var string
     */
    public $naming_candidate_plural;

    /**
     * The action, e.g. "Add candidate".
     *
     * @var string
     */
    public $naming_candidate_action;

    /**
     * Get user-friendly name for type.
     *
     * @param boolean $capital
     * @param boolean $plural
     * @return void
     */
    public function getNaming($capital = false, $plural = false) {
        $text = $this->get(
            'naming_candidate_' . ($plural ? 'plural' : 'singular')
        );
        if ($capital) {
            $text = ucfirst($text);
        }
        return $text;
    }

    /**
     * Get user-friendly name for action (e.g. "Nominate for role")
     *
     * @return void
     */
    public function getActionNaming(ElectionPostInterface $election_post = NULL) {
        $text = $this->get('naming_candidate_action') ?? 'Add @candidate_type';
        return t($text, [
            '@candidate_type' => $this->getNaming(FALSE, FALSE),
            '@post_type' => $election_post ? $election_post->getElectionPostType()->getNaming(FALSE, FALSE) : 'post',
        ]);
    }

    function postCreate(EntityStorageInterface $storage) {
        // @todo create two display modes, one for interest and one for nomination
    }
}
