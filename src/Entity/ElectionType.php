<?php

namespace Drupal\election\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Election type entity.
 *
 * @ConfigEntityType(
 *   id = "election_type",
 *   label = @Translation("Election type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\election\ElectionTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\election\Form\ElectionTypeForm",
 *       "edit" = "Drupal\election\Form\ElectionTypeForm",
 *       "delete" = "Drupal\election\Form\ElectionTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\election\ElectionTypeHtmlRouteProvider",
 *     },
 *   },
 *   bundle_of = "election",
 *   config_prefix = "election_type",
 *   config_export = {
 *     "id",
 *     "label",
 *     "naming_singular",
 *     "naming_plural",
 *     "allowed_post_types",
 *   },
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/election/election_type/{election_type}",
 *     "add-form" = "/admin/election/election_type/add",
 *     "edit-form" = "/admin/election/election_type/{election_type}/edit",
 *     "delete-form" = "/admin/election/election_type/{election_type}/delete",
 *     "collection" = "/admin/election/election_type"
 *   },
 * )
 */
class ElectionType extends ConfigEntityBundleBase implements
    ElectionTypeInterface
{
    /**
     * The Election type ID.
     *
     * @var string
     */
    protected $id;

    /**
     * The Election type label.
     *
     * @var string
     */
    protected $label;

    /**
     * The Election type label.
     *
     * @var array
     */
    public $allowed_post_types;

    /**
     * The Election type label.
     *
     * @var string
     */
    public $naming_singular;

    /**
     * The Election type label.
     *
     * @var string
     */
    public $naming_plural;

    /**
     * Return allowed post types for this election type.
     *
     * @return array|ElectionPostType
     *   Array of election post types.
     */
    public function getAllowedPostTypes()
    {
        $types = $this->get('allowed_post_types');
        if ($types == null || count($types) == 0) {
            return ElectionPostType::loadMultiple();
        } else {
            return ElectionPostType::loadMultiple($types);
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
    public function getNaming($capital = false, $plural = false)
    {
        $text = $this->get('naming_' . ($plural ? 'plural' : 'singular'));
        if ($capital) {
            $text = ucfirst($text);
        }
        return $text;
    }
}
