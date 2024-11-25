<?php

/**
 * @file
 * Hooks provided by the Finders facets module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform alterations on Finders Facets Type definitions.
 *
 * @param array &$info
 *   Array of information on Finders Facets Type plugins.
 */
function hook_finders_facets_type_info_alter(array &$info) {
  // Change the class of the 'foo' plugin.
  $info['foo']['class'] = SomeOtherClass::class;
}

/**
 * @} End of "addtogroup hooks".
 */
