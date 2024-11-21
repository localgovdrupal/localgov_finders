<?php

namespace Drupal\finders_facets\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Provides the Finders Facet entity.
 *
 * @ContentEntityType(
 *   id = "finders_facet",
 *   label = @Translation("Finders Facet"),
 *   label_collection = @Translation("Finders Facets"),
 *   label_singular = @Translation("finders facet"),
 *   label_plural = @Translation("finders facets"),
 *   label_count = @PluralTranslation(
 *     singular = "@count finders facet",
 *     plural = "@count finders facets",
 *   ),
 *   bundle_label = @Translation("Finders Facet Type"),
 *   base_table = "finders_facet",
 *   data_table = "finders_facet_field_data",
 *   translatable = "TRUE",
 *   handlers = {
 *     "access" = "Drupal\finders_facets\Entity\Handler\FindersFacetAccess",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\finders_facets\Form\FindersFacetForm",
 *       "add" = "Drupal\finders_facets\Form\FindersFacetForm",
 *       "edit" = "Drupal\finders_facets\Form\FindersFacetForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\finders_facets\Entity\Handler\FindersFacetListBuilder",
 *   },
 *   admin_permission = "administer finders_facet entities",
 *   entity_keys = {
 *     "id" = "finders_facet_id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "langcode" = "langcode",
 *     "owner" = "uid",
 *     "uid" = "uid",
 *   },
 *   bundle_entity_type = "finders_facet_type",
 *   field_ui_base_route = "entity.finders_facet_type.edit_form",
 *   links = {
 *     "add-page" = "/admin/content/finders/facets/add",
 *     "add-form" = "/admin/content/finders/facets/add/{finders_facet_type}",
 *     "canonical" = "/admin/content/finders/facets/{finders_facet}",
 *     "collection" = "/admin/content/finders_facet",
 *     "delete-form" = "/admin/content/finders/facets/{finders_facet}/delete",
 *     "edit-form" = "/admin/content/finders/facets/{finders_facet}/edit",
 *   },
 * )
 */
class FindersFacet extends ContentEntityBase implements FindersFacetInterface {

  use EntityChangedTrait;

  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t("Title"))
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the finder facets was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t("Changed"))
      ->setDescription(t("The time that the entity was last edited."))
      ->setTranslatable(TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The weight of this finder facet in relation to other facets.'))
      ->setTranslatable(TRUE)
      ->setDefaultValue(0)
      ->setInitialValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 50,
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
