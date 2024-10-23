<?php

namespace Drupal\localgov_finders\Plugin\FinderType;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\localgov_finders\Constants\FinderField;
use Drupal\localgov_finders\Field\BundleFieldDefinition;
use Drupal\node\NodeTypeInterface;

/**
 * Base class for Finder Type plugins.
 */
abstract class FinderTypeBase extends PluginBase implements FinderTypeInterface {

  public function getChannelFieldDefinitions(NodeTypeInterface $node_type): array {
    $field_definitions = [
      $this->getChannelTypesFieldDefinition(),
    ];
    // enabled facets
    // finder view.
    //

    foreach ($field_definitions as $field_definition) {
      $field_definition->setTargetBundle($node_type->id());
    }

    return $field_definitions;
  }

  protected function getChannelTypesFieldDefinition(): FieldDefinitionInterface {
    return BundleFieldDefinition::create('entity_reference')
      ->setName(FinderField::CHANNEL_TYPES_FIELD)
      ->setTargetEntityTypeId('node')
      ->setLabel(t('Enabled Content types'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setCardinality(BundleFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSettings([
        // TODO! this needs to move into Finders
        'handler' => 'localgov_directories_entry_types',
        'target_type' => 'node_type',
      ]);
  }

}
