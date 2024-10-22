<?php

/**
 * @file
 * Hooks provided by the localgov_finders module.
 */

/**
 * @addtogroup hooks
 * @{
 */

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
