<?php

namespace Drupal\finders_geo\Plugin\FinderType;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\finders\Attribute\FinderType;
use Drupal\finders\Field\BundleFieldDefinition;
use Drupal\finders\Plugin\FinderType\FinderTypeBase;
use Drupal\search_api\IndexInterface;

/**
 * Finder type for geo.
 *
 * Provides calendars and listings.
 *
 * Geo entries use the date_recur SearchAPI datasource, which adds an index
 * item for each occurrence of a recurring date rather than for each node.
 */
#[FinderType(
  id: "geo",
  label: new TranslatableMarkup("geo"),
  description: new TranslatableMarkup("Provides geo listings and maps"),
)]
class Geo extends FinderTypeBase {

  /**
   * The field name for the list view field.
   *
   * @see self::getListViewFieldDefinition()
   */
  const LIST_VIEW_FIELD = 'finders_geo_list_view';

  /**
   * The field name for the map view field.
   *
   * @see self::getListViewFieldDefinition()
   */
  const MAP_VIEW_FIELD = 'finders_geo_map_view';

  /**
   * The field name for the geo location field.
   *
   * @see self::getGeoLocationFieldDefinition()
   */
  const GEO_FIELD = 'location';

  /**
   * {@inheritdoc}
   */
  public function getIndexIds(): array {
    return [
      'finders_index_geo',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getChannelFieldDefinitions(ConfigEntityInterface $bundle): array {
    $field_definitions = parent::getChannelFieldDefinitions($bundle);

    $list_view_field = $this->getListViewFieldDefinition($bundle);
    $list_view_field->setTargetBundle($bundle->id());
    $field_definitions[$list_view_field->getName()] = $list_view_field;

    $map_view_field = $this->getMapViewFieldDefinition($bundle);
    $map_view_field->setTargetBundle($bundle->id());
    $field_definitions[$map_view_field->getName()] = $map_view_field;

    return $field_definitions;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterIndexFields(IndexInterface $index, ConfigEntityInterface $entry_bundle_entity): void {
    parent::alterIndexFields($index, $entry_bundle_entity);

    // Get the entity type ID of the entry entities that the entry bundle entity
    // defines.
    $entry_entity_type_id = $entry_bundle_entity->getEntityType()->getBundleOf();
    $datasource_id = $this->getIndexDatasourceId($index, $entry_entity_type_id);
    $datasource = $index->getDatasource($datasource_id);

    // @todo add location search fields.
  }

  /**
   * Gets the definition for the list view selection field.
   *
   * This field on channels controls allows selecting the view to show a listing
   * of geo entries.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle
   *   The bundle entity.
   *
   * @return \Drupal\finders\Field\BundleFieldDefinition
   *   The bundle field definition.
   */
  protected function getListViewFieldDefinition(ConfigEntityInterface $bundle): BundleFieldDefinition {
    $bundle_entity_type = $bundle->getEntityType();
    $content_entity_type_id = $bundle_entity_type->getBundleOf();

    return BundleFieldDefinition::create('viewsreference')
      ->setName(static::LIST_VIEW_FIELD)
      ->setTargetEntityTypeId($content_entity_type_id)
      ->setLabel(t('Geo list view'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setCardinality(1)
      ->setSettings([
        'target_type' => 'view',
        'handler' => 'finders_channel_views',
        'handler_settings' => [
          'target_bundles' => NULL,
          'auto_create' => FALSE,
        ],
        'plugin_types' => [
          'embed' => 'embed',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'viewsreference_select',
      ]);
  }

  /**
   * Gets the definition for the list view selection field.
   *
   * This field on channels controls allows selecting the view to show a listing
   * of geo entries.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle
   *   The bundle entity.
   *
   * @return \Drupal\finders\Field\BundleFieldDefinition
   *   The bundle field definition.
   */
  protected function getMapViewFieldDefinition(ConfigEntityInterface $bundle): BundleFieldDefinition {
    $bundle_entity_type = $bundle->getEntityType();
    $content_entity_type_id = $bundle_entity_type->getBundleOf();

    return BundleFieldDefinition::create('viewsreference')
      ->setName(static::MAP_VIEW_FIELD)
      ->setTargetEntityTypeId($content_entity_type_id)
      ->setLabel(t('Geo map view'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setCardinality(1)
      ->setSettings([
        'target_type' => 'view',
        'handler' => 'finders_channel_views',
        'handler_settings' => [
          'target_bundles' => NULL,
          'auto_create' => FALSE,
        ],
        'plugin_types' => [
          'embed' => 'embed',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'viewsreference_select',
      ]);
  }

}
