<?php

/**
 * @file
 * Contains Views hook implementations.
 */

use Drupal\Core\Hook\LegacyHook;
use Drupal\finders_events\Hooks\FindersEventsHooks;

/**
 * Legacy hook implementation.
 *
 * @todo Remove this method when support for Drupal core < 11.1 is dropped.
 */
#[LegacyHook]
function finders_events_views_data() {
  return \Drupal::service(FindersEventsHooks::class)->viewsData();
}
