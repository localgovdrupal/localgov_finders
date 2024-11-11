<?php

/**
 * @file
 * Hooks provided by the Finders module.
 */

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\finders\Plugin\FinderType\FinderTypeInterface;

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
function hook_finders_channel_fields_alter(array &$channel_field_definitions, ConfigEntityInterface $bundle_entity, FinderTypeInterface $finder_type) {
  // TODO: write sample code.
}

/**
 * Perform alterations on a finder search index when it is being updated.
 *
 * This hook is called a bundle entity is being configured as either a channel
 * or an entry bundle.
 *
 * @param $index
 *   The search index. It has not yet been saved, and will be saved by the
 *   invoker of this hook.
 * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle_entity
 *   The bundle entity that is being configured as either a channel or an entry.
 * @param \Drupal\finders\Plugin\FinderType\FinderTypeInterface $finder_type
 *   The finder type used for the bundle entity.
 */
function hook_finders_index_alter($index, ConfigEntityInterface $bundle_entity, FinderTypeInterface $finder_type) {
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
function hook_finders_entry_fields_alter(array &$entry_field_definitions, ConfigEntityInterface $bundle_entity, FinderTypeInterface $finder_type) {
  // TODO: write sample code.
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
