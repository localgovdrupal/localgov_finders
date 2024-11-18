<?php

namespace Drupal\finders_events\Plugin\FinderType;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\date_recur_search_api\Plugin\ComputedField\DateOccurrence;
use Drupal\finders\Attribute\FinderType;
use Drupal\finders\Entity\FinderInterface;
use Drupal\finders\Field\BundleFieldDefinition;
use Drupal\finders\Plugin\FinderType\FinderTypeBase;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\Field as SearchIndexField;
use Drupal\views\ViewEntityInterface;

/**
 * Finder type for events.
 *
 * Provides calendars and listings.
 *
 * Event entries use the date_recur SearchAPI datasource, which adds an index
 * item for each occurrence of a recurring date rather than for each node.
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
  const LIST_VIEW_FIELD = 'finders_events_list_view';

  /**
   * The field name for the calendar view field.
   *
   * @see self::getListViewFieldDefinition()
   */
  const CALENDAR_VIEW_FIELD = 'finders_events_cal_view';

  /**
   * The field name for the event date field.
   *
   * @see self::getEventDateFieldDefinition()
   */
  const EVENT_DATE_FIELD = 'finders_events_date';

  /**
   * The field name of the computed field for date occurrences.
   *
   * This is defined by the date_recur_search_api module.
   */
  const EVENT_DATE_OCCURRENCE_FIELD = self::EVENT_DATE_FIELD .  '_occurrence';

  /**
   * {@inheritdoc}
   */
  public function getIndexIds(): array {
    return [
      'finders_index_events',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getViewIds(IndexInterface $search_index): array {
    return [
      'finders_events_channel_list',
      'finders_events_channel_calendar',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexDatasourceId(IndexInterface $index, string $entity_type_id): string {
    // See \Drupal\date_recur_search_api\Plugin\search_api\datasource\DateRecur
    return 'date_recur:' . $entity_type_id . '__' . static::EVENT_DATE_FIELD;
  }

  /**
   * {@inheritdoc}
   */
  public function getChannelFieldDefinitions(ConfigEntityInterface $bundle, FinderInterface $finder): array {
    $field_definitions = parent::getChannelFieldDefinitions($bundle, $finder);

    if ($list_view_field = $this->getListViewFieldDefinition($bundle)) {
      $field_definitions[$list_view_field->getName()] = $list_view_field;
    }

    if ($calendar_view_field = $this->getCalendarViewFieldDefinition($bundle)) {
      $field_definitions[$calendar_view_field->getName()] = $calendar_view_field;
    }

    return $field_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntryFieldDefinitions(ConfigEntityInterface $bundle, FinderInterface $finder): array {
    $field_definitions = parent::getEntryFieldDefinitions($bundle, $finder);

    $event_date_field = $this->getEventDateFieldDefinition($bundle);
    $field_definitions[$event_date_field->getName()] = $event_date_field;

    return $field_definitions;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterIndexFields(IndexInterface $index, FinderInterface $finder): void {
    parent::alterIndexFields($index, $finder);

    $entry_entity_type_id = $finder->getEntryEntityTypeId();

    $datasource_id = $this->getIndexDatasourceId($index, $entry_entity_type_id);
    $datasource = $index->getDatasource($datasource_id);

    // TODO: make DateRecur::getComputedFieldName() public so we can use that
    // instead of accessing the DateOccurrence::COMPUTED_FIELD_SUFFIX constant
    // directly.
    $occurrence_field_name = static::EVENT_DATE_FIELD . DateOccurrence::COMPUTED_FIELD_SUFFIX;

    if (!$index->getField($occurrence_field_name)) {
      $date_field = new SearchIndexField($index, $occurrence_field_name);
      $date_field->setDatasourceId($datasource_id);
      $date_field->setType('date');
      $date_field->setPropertyPath($occurrence_field_name);
      $date_field->setLabel('Date occurrence');

      $index->addField($date_field);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterView(ViewEntityInterface $view, IndexInterface $index, FinderInterface $finder): void {
    parent::alterView($view, $index, $finder);

    $default_display_configuration =& $view->getDisplay('default');

    // Add the date occurrence sort, if our index has it, and avoiding to
    // clobber existing configuration.
    if (
      $index->getField(static::EVENT_DATE_OCCURRENCE_FIELD)
      && !isset($default_display_configuration['display_options']['sorts'][static::EVENT_DATE_OCCURRENCE_FIELD])
    ) {

      // Use Yaml to make it easier to sync config changes back into code.
      $yaml_template = <<<'EOT'
        id: EVENT_DATE_OCCURRENCE_FIELD
        field: EVENT_DATE_OCCURRENCE_FIELD
        relationship: none
        group_type: group
        admin_label: ''
        order: ASC
        exposed: false
        expose:
          label: ''
        plugin_id: search_api
        EOT;

      $date_sort = Yaml::decode($yaml_template);

      $date_sort['id'] = static::EVENT_DATE_OCCURRENCE_FIELD;
      $date_sort['field'] = static::EVENT_DATE_OCCURRENCE_FIELD;

      // Table name from search_api_views_data().
      $date_sort['table'] = 'search_api_index_' . $index->id();

      // Our date sort goes first. Take out any existing ones to put them back
      // after.
      $existing_sorts = $default_display_configuration['display_options']['sorts'];
      $default_display_configuration['display_options']['sorts'] = [];

      $default_display_configuration['display_options']['sorts'][static::EVENT_DATE_OCCURRENCE_FIELD] = $date_sort;

      foreach ($existing_sorts as $key => $sort) {
        $default_display_configuration['display_options']['sorts'][$key] = $sort;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getViewFieldDefinition(ConfigEntityInterface $bundle): ?BundleFieldDefinition {
    return NULL;
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
   * @return \Drupal\finders\Field\BundleFieldDefinition|null
   *   The bundle field definition, or NULL if no field should be defined.
   */
  protected function getListViewFieldDefinition(ConfigEntityInterface $bundle): ?BundleFieldDefinition {
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
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'finders_channel_view',
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
   * @return \Drupal\finders\Field\BundleFieldDefinition|null
   *   The bundle field definition, or NULL if no field should be defined.
   */
  protected function getCalendarViewFieldDefinition(ConfigEntityInterface $bundle): ?BundleFieldDefinition {
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
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'finders_channel_view',
      ]);
  }

  /**
   * Gets the definition for the event date field.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle
   *   The bundle entity.
   *
   * @return \Drupal\finders\Field\BundleFieldDefinition|null
   *   The bundle field definition, or NULL if no field should be defined.
   */
  protected function getEventDateFieldDefinition(ConfigEntityInterface $bundle): ?BundleFieldDefinition {
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
