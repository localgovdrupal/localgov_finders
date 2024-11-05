<?php

/**
 * @file
 * Hooks provided by the Localgov Finders module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * TODO: write summary line.
 *
 * TODO: longer description.
 *
 * @param $channel_field_definitions
 *   TODO: document this parameter.
 * @param $bundle_entity
 *   TODO: document this parameter.
 * @param $finder_type
 *   TODO: document this parameter.
 *
 * @return
 *   TODO: Document return value if there is one.
 */
function hook_finders_channel_fields_alter($channel_field_definitions, $bundle_entity, $finder_type) {
  // TODO: write sample code.
}

/**
 * TODO: write summary line.
 *
 * TODO: longer description.
 *
 * @param $index
 *   TODO: document this parameter.
 * @param $bundle_entity
 *   TODO: document this parameter.
 * @param $finder_type
 *   TODO: document this parameter.
 *
 * @return
 *   TODO: Document return value if there is one.
 */
function hook_finders_index_alter($index, $bundle_entity, $finder_type) {
  // TODO: write sample code.
}

/**
 * TODO: write summary line.
 *
 * TODO: longer description.
 *
 * @param $channel_field_definitions
 *   TODO: document this parameter.
 * @param $bundle_entity
 *   TODO: document this parameter.
 * @param $finder_type
 *   TODO: document this parameter.
 *
 * @return
 *   TODO: Document return value if there is one.
 */
function hook_finders_entry_fields_alter($channel_field_definitions, $bundle_entity, $finder_type) {
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
