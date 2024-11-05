<?php

namespace Drupal\finders_facets\Hooks;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\localgov_finders\Plugin\FinderType\FinderTypeInterface;

/**
 * Contains hook implementations for the Finders facets module.
 */
class FindersFacetsHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.finders_facets':
        return t("TODO: Create admin help text.");

      // OPTIONAL: Add additional cases for other paths that should display
      // help text.
    }
  }

  /**
   * Implements hook_finders_channel_fields_alter().
   */
  #[Hook('finders_channel_fields_alter')]
  public function findersChannelFieldsAlter(&array &$channel_field_definitions, ConfigEntityInterface $bundle_entity, FinderTypeInterface $finder_type) {
    // TODO: write sample code.
  }

  /**
   * Implements hook_finders_entry_fields_alter().
   */
  #[Hook('finders_entry_fields_alter')]
  public function findersEntryFieldsAlter(&array &$channel_field_definitions, ConfigEntityInterface $bundle_entity, FinderTypeInterface $finder_type) {
    // TODO: write sample code.
  }

}
