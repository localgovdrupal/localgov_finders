<?php

namespace Drupal\finders_facets\Hook;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\finders\Entity\FinderInterface;
use Drupal\finders\Field\BundleFieldDefinition;

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
  public function findersChannelFieldsAlter(array &$channel_field_definitions, ConfigEntityInterface $bundle_entity, FinderInterface $finder) {
    $bundle_entity_type = $bundle_entity->getEntityType();
    $content_entity_type_id = $bundle_entity_type->getBundleOf();

    if (!$this->isFinderEnabledWithFacets($finder)) {
      return;
    }

    // Add the enabled facets field to a channel bundle.
    $channel_field_definitions['finders_facets_enable'] = BundleFieldDefinition::create('entity_reference')
      ->setName('finders_facets_enable')
      ->setTargetEntityTypeId($content_entity_type_id)
      ->setLabel(t('Enabled Facets'))
      ->setDescription(t('Which facets are enabled to be shown on this directory channel, and will be added when editing content to be added to this directory.'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setCardinality(BundleFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSettings([
        'target_type' => 'finders_facet_type',
        'handler' => 'default',
        'handler_settings' => [
          'target_bundles' => NULL,
          'auto_create' => FALSE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
      ]);
  }

  /**
   * Implements hook_finders_entry_fields_alter().
   */
  #[Hook('finders_entry_fields_alter')]
  public function findersEntryFieldsAlter(array &$entry_field_definitions, ConfigEntityInterface $bundle_entity, FinderInterface $finder) {
    $bundle_entity_type = $bundle_entity->getEntityType();
    $content_entity_type_id = $bundle_entity_type->getBundleOf();

    if (!$this->isFinderEnabledWithFacets($finder)) {
      return;
    }

    // Add the selected facets field to a channel bundle.
    $entry_field_definitions['finders_facets_select'] = BundleFieldDefinition::create('entity_reference')
      ->setName('finders_facets_select')
      ->setTargetEntityTypeId($content_entity_type_id)
      ->setLabel(t('Facets'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setCardinality(BundleFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSettings([
        'target_type' => 'finders_facet',
        // 'handler' => 'finders_facets_selection',
        'handler_settings' => [
          'target_bundles' => NULL, // REMOVE!
          'auto_create' => FALSE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
      ]);
  }

  /**
   * Determines whether a finder is configured to use facets.
   *
   * @param \Drupal\finders\Entity\FinderInterface $finder
   *   The finder entity.
   *
   * @return boolean
   *   TRUE if the finder is configured to use facets, FALSE if not.
   */
  protected function isFinderEnabledWithFacets(FinderInterface $finder): bool {
    return ($finder->getThirdPartySetting('finders_facets', 'facets', FALSE) == TRUE);
  }

}
