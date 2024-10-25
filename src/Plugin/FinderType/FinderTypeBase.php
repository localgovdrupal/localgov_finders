<?php

namespace Drupal\localgov_finders\Plugin\FinderType;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\localgov_finders\Constants\FinderField;
use Drupal\localgov_finders\Field\BundleFieldDefinition;

/**
 * Base class for Finder Type plugins.
 */
abstract class FinderTypeBase extends PluginBase implements FinderTypeInterface {

  public function getChannelFieldDefinitions(ConfigEntityInterface $bundle): array {
    $field_definitions = [];

    $channel_types_field_definition = $this->getChannelTypesFieldDefinition($bundle);
    $field_definitions[$channel_types_field_definition->getName()] = $channel_types_field_definition;

    // TODO: further fields:
    // enabled facets
    // finder view.

    foreach ($field_definitions as $field_definition) {
      // Set the target bundle on all bundle fields.
      $field_definition->setTargetBundle($bundle->id());
    }

    return $field_definitions;
  }

  protected function getChannelTypesFieldDefinition(ConfigEntityInterface $bundle): FieldDefinitionInterface {
    $entity_type = $bundle->getEntityType();
    return BundleFieldDefinition::create('entity_reference')
      ->setName(FinderField::CHANNEL_TYPES_FIELD)
      ->setTargetEntityTypeId($entity_type->getBundleOf())
      ->setLabel(t('Enabled Content types'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setCardinality(BundleFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSettings([
        // TODO! this needs to move into Finders
        'handler' => 'default',
        'target_type' => $entity_type->id(),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
      ]);
  }

}
