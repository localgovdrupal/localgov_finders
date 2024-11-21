<?php

namespace Drupal\finders_test\Plugin\FinderType;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\finders\Attribute\FinderType;
use Drupal\finders\Constants\FinderField;
use Drupal\finders\Field\BundleFieldDefinition;
use Drupal\finders\Plugin\FinderType\FinderTypeBase;

/**
 * Finder type for using in tests.
 */
#[FinderType(
  id: "test",
  label: new TranslatableMarkup("Test finder type"),
  description: new TranslatableMarkup("Provides dummy plugin for testing"),
)]
class Test extends FinderTypeBase {
/*
  protected function getChannelTypesFieldDefinition(ConfigEntityTypeInterface $entity_type): FieldDefinitionInterface {
    return BundleFieldDefinition::create('entity_reference')
      ->setName(FinderField::CHANNEL_TYPES_FIELD)
      ->setTargetEntityTypeId('entity_test_with_bundle')
      ->setLabel(t('Enabled Content types'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setCardinality(BundleFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSettings([
        // TODO! this needs to move into Finders
        'handler' => 'default',
        'target_type' => 'entity_test_with_bundle',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
      ]);
  }
 */
}
