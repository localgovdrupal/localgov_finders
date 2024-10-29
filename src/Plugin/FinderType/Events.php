<?php

namespace Drupal\localgov_finders\Plugin\FinderType;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\localgov_finders\Attribute\FinderType;
use Drupal\localgov_finders\Field\BundleFieldDefinition;
use Drupal\localgov_finders\Plugin\FinderType\FinderTypeBase;

/**
 * Finder type for events.
 *
 * Provides calendars and listings.
 *
 * POC -- will move to the LGD Directories module.
 */
#[FinderType(
  id: "events",
  label: new TranslatableMarkup("Events"),
  description: new TranslatableMarkup("Provides event calendars and listings"),
)]
class Events extends FinderTypeBase {

  /**
   * The field name for the list view field.
   *
   * @see self::getListViewFieldDefinition()
   */
  const LIST_VIEW_FIELD = 'localgov_events_list_view';

  /**
   * {@inheritdoc}
   */
  public function getChannelFieldDefinitions(ConfigEntityInterface $bundle): array {
    $field_definitions = parent::getChannelFieldDefinitions($bundle);

    $list_view_field = $this->getListViewFieldDefinition($bundle);
    $list_view_field->setTargetBundle($bundle->id());
    $field_definitions[$list_view_field->getName()] = $list_view_field;

    return $field_definitions;
  }

  /**
   * Gets the definition for the list view selection field.
   *
   * This field on channels controls allows selecting the view to show a listing
   * of event entries.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle
   *   The bundle entity.
   *
   * @return \Drupal\localgov_finders\Field\BundleFieldDefinition
   *   The bundle field definition.
   */
  protected function getListViewFieldDefinition(ConfigEntityInterface $bundle): BundleFieldDefinition {
    $bundle_entity_type = $bundle->getEntityType();
    $content_entity_type_id = $bundle_entity_type->getBundleOf();

    return BundleFieldDefinition::create('viewsreference')
      ->setName(static::LIST_VIEW_FIELD)
      ->setTargetEntityTypeId($content_entity_type_id)
      ->setLabel(t('Event list view'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setCardinality(1)
      ->setSettings([
        'target_type' => 'view',
        // @todo Custom selection handler which limits views to those whose base
        // is the entry entity type.
        'handler' => 'default:view',
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
