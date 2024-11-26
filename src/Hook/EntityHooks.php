<?php

namespace Drupal\finders\Hook;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\finders\FinderConfigManager;

/**
 * Contains entity hook implementations for the Finders module.
 */
class EntityHooks {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The finder config manager.
   *
   * @var \Drupal\finders\FinderConfigManager
   */
  protected $finderConfigManager;

  /**
   * Creates an EntityHooks instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\finders\FinderConfigManager $finder_config_manager
   *   The finder config manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FinderConfigManager $finder_config_manager,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->finderConfigManager = $finder_config_manager;
  }

  /**
   * Implements hook_entity_bundle_field_info().
   */
  #[Hook('entity_bundle_field_info')]
  public function entityBundleFieldInfo(EntityTypeInterface $entity_type, $bundle_id, array $base_field_definitions) {
    // Only act on content entity types.
    if (!$entity_type instanceof ContentEntityTypeInterface) {
      return [];
    }

    // Only act on entity types whose bundles are provided by a bundle entity
    // type.
    $bundle_type_id = $entity_type->getBundleEntityType();
    if (empty($bundle_type_id)) {
      return [];
    }

    // On a bundle creation form, there is no bundle entity yet.
    $bundle = $this->entityTypeManager->getStorage($bundle_type_id)->load($bundle_id);
    if (empty($bundle)) {
      return [];
    }

    return $this->finderConfigManager->getBundleFieldDefinitions($bundle);
  }

  /**
   * Implements hook_entity_field_storage_info().
   */
  #[Hook('entity_field_storage_info')]
  public function entityFieldStorageInfo(EntityTypeInterface $entity_type) {
    // Only act on content entity types.
    if (!$entity_type instanceof ContentEntityTypeInterface) {
      return [];
    }

    // Only act on entity types whose bundles are provided by a bundle entity
    // type.
    $bundle_entity_type_id = $entity_type->getBundleEntityType();
    if (empty($bundle_entity_type_id)) {
      return [];
    }

    /** @var \Drupal\finders\Field\BundleFieldDefinition[] $fields */
    $fields = [];

    $entity_type_manager = $this->entityTypeManager;
    $bundle_entities = $entity_type_manager->getStorage($bundle_entity_type_id)->loadMultiple();

    foreach ($bundle_entities as $bundle_entity) {
      $bundle_fields = $this->finderConfigManager->getBundleFieldDefinitions($bundle_entity);

      // Check that plugins don't change base field properties from other
      // plugins.
      foreach ($bundle_fields as $field_name => $bundle_field) {
        if (isset($fields[$field_name])) {
          assert($bundle_field->getType() == $fields[$field_name]->getType());
          assert($bundle_field->getTargetEntityTypeId() == $fields[$field_name]->getTargetEntityTypeId());
          assert($bundle_field->getCardinality() == $fields[$field_name]->getCardinality());
          // @todo Check field settings could cause problems if changed, such as
          // entity reference target type.
        }
      }

      $fields += $bundle_fields;
    }

    return $fields;
  }

}
