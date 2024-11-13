<?php

/**
 * @file
 * Hooks provided by the Finders module.
 */

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\finders\Entity\FinderInterface;
use Drupal\search_api\IndexInterface;
use Drupal\views\ViewEntityInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform alterations on the channel bundle fields before they are created.
 *
 * @param \Drupal\finders\Field\BundleFieldDefinition[] $channel_field_definitions
 *   An array of bundle field definitions to be added to the channel bundle.
 *   These have not yet been declared to the field system.
 * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle_entity
 *   The bundle entity that is being configured as a channel.
 * @param \Drupal\finders\Plugin\FinderType\FinderTypeInterface $finder_type
 *   The finder type used for the bundle entity.
 */
function hook_finders_channel_fields_alter(array &$channel_field_definitions, ConfigEntityInterface $bundle_entity, FinderInterface $finder) {
  // Change the label of the finder channel types field.
  $finder_type_plugin = $finder->getFinderTypePlugin();
  $channel_field_definitions[$finder_type_plugin::CHANNEL_TYPES_FIELD]->setLabel(t('Enabled items'));
}

/**
 * Perform alterations on a finder search index when it is being updated.
 *
 * This hook is invoked after the finder type plugin's alterSearch() has been
 * called.
 *
 * @param \Drupal\search_api\IndexInterface $index
 *   The search index. It has not yet been saved, and will be saved by the
 *   invoker of this hook.
 * @param \Drupal\finders\Entity\FinderInterface $finder
 *   The finder entity.
 */
function hook_finders_index_alter(IndexInterface $index, FinderInterface $finder): void {
  // Change the boost on the index title field.
  $index->getField('title')->setBoost(10.0);
}

/**
 * Perform alterations on a finder view index when it is being updated.
 *
 * This hook is invoked after the finder type plugin's alterView() has been
 * called.
 *
 * @param \Drupal\views\ViewEntityInterface $view
 *   The view. It has not yet been saved, and will be saved by the invoker of
 *   this hook.
 * @param $index
 *   The search index. It has already been updated with configuration for the
 *   finder.
 * @param \Drupal\finders\Plugin\FinderType\FinderTypeInterface $finder_type
 *   The finder type used for the bundle entity.
 */
function hook_finders_view_alter(ViewEntityInterface $view, IndexInterface $index, FinderInterface $finder): void {
  // TODO: write sample code.
}

/**
 * Perform alterations on the entry bundle fields before they are created.
 *
 * @param \Drupal\finders\Field\BundleFieldDefinition[] $entry_field_definitions
 *   An array of bundle field definitions to be added to the entry bundle.
 *   These have not yet been declared to the field system.
 * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle_entity
 *   The bundle entity that is being configured as an entry.
 * @param \Drupal\finders\Plugin\FinderType\FinderTypeInterface $finder_type
 *   The finder type used for the bundle entity.
 */
function hook_finders_entry_fields_alter(array &$entry_field_definitions, ConfigEntityInterface $bundle_entity, FinderInterface $finder) {
  // Change the label of the finder channel selection field.
  $finder_type_plugin = $finder->getFinderTypePlugin();
  $entry_field_definitions[$finder_type_plugin::CHANNEL_SELECTION_FIELD]->setLabel(t('Directories'));
}

/**
 * Perform alterations on Finder Type definitions.
 *
 * @param array &$info
 *   Array of information on Finder Type plugins.
 */
function hook_finder_type_info_alter(array &$info) {
  // Change the class of the 'foo' plugin.
  $info['foo']['class'] = SomeOtherClass::class;
}

/**
 * @} End of "addtogroup hooks".
 */
