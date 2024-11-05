<?php

namespace Drupal\finders_facets\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Provides the Finders Facet Type entity.
 *
 * @ConfigEntityType(
 *   id = "finders_facet_type",
 *   label = @Translation("Finders Facet Type"),
 *   label_collection = @Translation("Finders Facet Types"),
 *   label_singular = @Translation("finders facet type"),
 *   label_plural = @Translation("finders facet types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count finders facet type",
 *     plural = "@count finders facet types",
 *   ),
 *   handlers = {
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\finders_facets\Form\FindersFacetTypeForm",
 *       "add" = "Drupal\finders_facets\Form\FindersFacetTypeForm",
 *       "edit" = "Drupal\finders_facets\Form\FindersFacetTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\finders_facets\Entity\Handler\FindersFacetTypeListBuilder",
 *   },
 *   admin_permission = "administer finders_facet_type entities",
 *   bundle_of = "finders_facet",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "weight",
 *     "description",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/finders_facet_type/add",
 *     "canonical" = "/admin/structure/finders_facet_type/{finders_facet_type}",
 *     "collection" = "/admin/structure/finders_facet_type",
 *     "edit-form" = "/admin/structure/finders_facet_type/{finders_facet_type}/edit",
 *     "delete-form" = "/admin/structure/finders_facet_type/{finders_facet_type}/delete",
 *   },
 * )
 */
class FindersFacetType extends ConfigEntityBundleBase implements FindersFacetTypeInterface {

  /**
   * Machine name.
   *
   * @var string
   */
  protected $id = '';

  /**
   * Name.
   *
   * @var string
   */
  protected $label = '';

  /**
   * Description of the facet type.
   *
   * @var string|null
   */
  protected $description = NULL;

  /**
   * Weight.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

}
