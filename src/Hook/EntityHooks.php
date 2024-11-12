<?php

declare(strict_types=1);

namespace Drupal\finders\Hook;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\finders\Enum\FinderRole;
use Drupal\finders\FinderConfigManager;
use Drupal\finders\FinderTypeManager;

/**
 * Entity hooks for Finders.
 */
final class EntityHooks {

  public function __construct(
    protected FinderTypeManager $finderTypeManager,
    protected FinderConfigManager $finderConfigManager
  ) {
  }

  #[Hook('entity_insert')]
  public function entityInsert(EntityInterface $entity): void {
    if ($entity instanceof ConfigEntityInterface && !$entity->isSyncing()) {
      $finder_type_id = $entity->getThirdPartySetting('finders', 'finder_type', '');
      if ($finder_type_id !== '') {
        $this->configureFinder($entity, $finder_type_id);
      }
    }
  }

  #[Hook('entity_update')]
  public function entityUpdate(EntityInterface $entity): void {
    if ($entity instanceof ConfigEntityInterface && !$entity->isSyncing()) {
      $finder_type_id = $entity->getThirdPartySetting('finders', 'finder_type', '');
      assert ($entity->original instanceof ConfigEntityInterface);
      $original_finder_type = $entity->original->getThirdPartySetting('finders', 'finder_type', '');
      // @todo Support removal, uninstall.
      if ($original_finder_type !== $finder_type_id && $finder_type_id !== '') {
        $this->configureFinder($entity, $finder_type_id);
      }
    }
  }

  /**
   * Call the appropriate plugin configuration management.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle_entity
   *   The entity bundle to configure.
   * @param string $finder_type_id
   *   The finder type plugin ID.
   */
  private function configureFinder(ConfigEntityInterface $bundle_entity, string $finder_type_id): void {
    return;
    $finder_type = $this->finderTypeManager->createInstance($finder_type_id);
    $finder_role_name = $bundle_entity->getThirdPartySetting('finders', 'finder_role', '');
    match ($finder_role_name) {
      FinderRole::Channel->value => $this->finderConfigManager->configureAsChannel(
        $bundle_entity,
        $finder_type
      ),
      FinderRole::Entries->value => $this->finderConfigManager->configureAsEntry(
        $bundle_entity,
        $finder_type
      ),
    };
  }

}
