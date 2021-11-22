<?php

namespace Drupal\election\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\election\Annotation\ElectionVotingMethodPlugin;

/**
 * Defines the Election post type entity.
 *
 * @ConfigEntityType(
 *   id = "election_post_type",
 *   label = @Translation("Election post type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\election\ElectionPostTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\election\Form\ElectionPostTypeForm",
 *       "edit" = "Drupal\election\Form\ElectionPostTypeForm",
 *       "delete" = "Drupal\election\Form\ElectionPostTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\election\ElectionPostTypeHtmlRouteProvider",
 *     },
 *   },
 *   bundle_of = "election_post",
 *   config_prefix = "election_post_type",
 *   config_export = {
 *     "id",
 *     "label",
 *     "naming_post_singular",
 *     "naming_post_plural",
 *     "naming_post_action",
 *     "allowed_candidate_types",
 *   },
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/election/election_post_type/{election_post_type}",
 *     "add-form" = "/admin/election/election_post_type/add",
 *     "edit-form" = "/admin/election/election_post_type/{election_post_type}/edit",
 *     "delete-form" = "/admin/election/election_post_type/{election_post_type}/delete",
 *     "collection" = "/admin/election/election_post_type"
 *   }
 * )
 */
class ElectionPostType extends ConfigEntityBundleBase implements ElectionPostTypeInterface {
    /**
     * The Election post type ID.
     *
     * @var string
     */
    protected $id;

    /**
     * The Election post type label.
     *
     * @var string
     */
    protected $label;

    /**
     * The Election type label.
     *
     * @var string
     */
    public $naming_post_singular;

    /**
     * The Election type label.
     *
     * @var string
     */
    public $naming_post_plural;

    /**
     * The Election type label.
     *
     * @var string
     */
    public $naming_post_action;

    /**
     * The Election type label.
     *
     * @var array
     */
    public $allowed_candidate_types;

    /**
     * Return allowed post types for this election type.
     *
     * @return array|ElectionCandidateType
     *   Array of election post types.
     */
    public function getAllowedCandidateTypes() {
        $types = $this->get('allowed_candidate_types');
        if ($types == null || count($types) == 0) {
            return ElectionCandidateType::loadMultiple();
        } else {
            return ElectionCandidateType::loadMultiple($types);
        }
    }

    /**
     * Get user-friendly name for type.
     *
     * @param bool $capital
     *   Start with a capital letter.
     * @param bool $plural
     *   PLuralise.
     *
     * @return string
     *   The user-friendly name.
     */
    public function getNaming($capital = FALSE, $plural = FALSE) {
        $text = $this->get('naming_post_' . ($plural ? 'plural' : 'singular'));
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
    public function getActionNaming() {
        $text = $this->get('naming_post_action') ?? 'Create @post_type';
        return t($text, [
            '@post_type' => $this->getNaming(FALSE, FALSE),
        ]);
    }
}
