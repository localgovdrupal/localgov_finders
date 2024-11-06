<?php

namespace Drupal\finders_events\Plugin\FinderType;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\localgov_finders\Attribute\FinderType;
use Drupal\localgov_finders\Field\BundleFieldDefinition;
use Drupal\localgov_finders\Plugin\FinderType\FinderTypeBase;

/**
 * Finder type for events.
 *
 * Provides calendars and listings.
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
   * The field name for the calendar view field.
   *
   * @see self::getListViewFieldDefinition()
   */
  const CALENDAR_VIEW_FIELD = 'localgov_events_cal_view';

  /**
   * The field name for the event date field.
   *
   * @see self::getEventDateFieldDefinition()
   */
  const EVENT_DATE_FIELD = 'localgov_events_date';

  /**
   * {@inheritdoc}
   */
  public function getIndexIds(): array {
    return [
      'localgov_finders_index_events',
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

    $calendar_view_field = $this->getCalendarViewFieldDefinition($bundle);
    $calendar_view_field->setTargetBundle($bundle->id());
    $field_definitions[$calendar_view_field->getName()] = $calendar_view_field;

    return $field_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntryFieldDefinitions(ConfigEntityInterface $bundle): array {
    $field_definitions = parent::getEntryFieldDefinitions($bundle);

    $event_date_field = $this->getEventDateFieldDefinition($bundle);
    $event_date_field->setTargetBundle($bundle->id());
    $field_definitions[$event_date_field->getName()] = $event_date_field;

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
        'handler' => 'localgov_finders_channel_views',
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
   * of event entries.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle
   *   The bundle entity.
   *
   * @return \Drupal\localgov_finders\Field\BundleFieldDefinition
   *   The bundle field definition.
   */
  protected function getCalendarViewFieldDefinition(ConfigEntityInterface $bundle): BundleFieldDefinition {
    $bundle_entity_type = $bundle->getEntityType();
    $content_entity_type_id = $bundle_entity_type->getBundleOf();

    return BundleFieldDefinition::create('viewsreference')
      ->setName(static::CALENDAR_VIEW_FIELD)
      ->setTargetEntityTypeId($content_entity_type_id)
      ->setLabel(t('Event calendar view'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setCardinality(1)
      ->setSettings([
        'target_type' => 'view',
        'handler' => 'localgov_finders_channel_views',
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
   * Gets the definition for the event date field.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle
   *   The bundle entity.
   *
   * @return \Drupal\localgov_finders\Field\BundleFieldDefinition
   *   The bundle field definition.
   */
  protected function getEventDateFieldDefinition(ConfigEntityInterface $bundle): BundleFieldDefinition {
    $bundle_entity_type = $bundle->getEntityType();
    $content_entity_type_id = $bundle_entity_type->getBundleOf();

    return BundleFieldDefinition::create('date_recur')
      ->setName(static::EVENT_DATE_FIELD)
      ->setTargetEntityTypeId($content_entity_type_id)
      ->setLabel(t('Date'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setCardinality(1)
      ->setSettings([
        'datetime_type' => 'datetime',
        'rrule_max_length' => 256,
        'precreate' => 'P2Y',
        'parts' => [
          'all' => TRUE,
          'frequencies' => [
            'SECONDLY' => [],
            'MINUTELY' => [],
            'HOURLY' => [],
            'DAILY' => [],
            'WEEKLY' => [],
            'MONTHLY' => [],
            'YEARLY' => [],
          ],
        ]
      ])
      ->setDefaultValue([
        'default_date_type' => 'now',
        'default_date' => 'now',
        'default_end_date_type' => 'now',
        'default_end_date' => 'now',
        'default_date_time_zone' => 'Europe/London',
        'default_time_zone' => 'Europe/London',
        'default_rrule' => '',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'date_recur_modular_alpha',
      ]);
  }

}
