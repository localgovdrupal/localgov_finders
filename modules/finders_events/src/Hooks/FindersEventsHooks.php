<?php

namespace Drupal\finders_events\Hooks;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\finders_events\Plugin\FinderType\Events;

/**
 * Contains hook implementations for the Finders events module.
 */
class FindersEventsHooks {

  /**
   * Implements hook_views_data().
   */
  #[Hook('views_data')]
  public function viewsData() {
    // Declare our event bundle field to Views ourselves, as core doesn't
    // declare bundle fields to Views, and Date Recur module's Views support
    // complains if it's not there.
    // @see https://www.drupal.org/project/drupal/issues/2898635.
    $field_storage_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('node');
    if (!isset($field_storage_definitions[Events::EVENT_DATE_FIELD])) {
      return [];
    }

    $field_storage = $field_storage_definitions[Events::EVENT_DATE_FIELD];

    // Make a dummy field_storage_config entity that looks the same as our
    // bundle field's storage, as hook_field_views_data() expects that.
    $dummy_field_storage_config = \Drupal::service('entity_type.manager')->getStorage('field_storage_config')->create([
      'id' => 'node.' . Events::EVENT_DATE_FIELD,
      'field_name' => Events::EVENT_DATE_FIELD,
      'entity_type' => 'node',
      'type' => 'date_recur',
      'cardinality' => 1,
    ]);
    $dummy_field_storage_config->setSettings($field_storage->getSettings());

    $module_handler = \Drupal::moduleHandler();

    // Invoke hook_field_views_data() directly on date_recur module with our
    // dummy field storage config entity, so we can use that as the views data
    // for our bundle field.
    $data = $module_handler->invoke('date_recur', 'field_views_data', [$dummy_field_storage_config]);

    return $data;
  }

}
