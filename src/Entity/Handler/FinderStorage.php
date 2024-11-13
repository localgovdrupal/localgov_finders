<?php

namespace Drupal\finders\Entity\Handler;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\finders\Entity\FinderInterface;

/**
 * Provides the storage handler for the Finder entity.
 */
class FinderStorage extends ConfigEntityStorage {

  /**
   * Gets the finder entity for a given bundle entity, if one exists.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle_entity
   *   A bundle entity.
   *
   * @return \Drupal\finders\Entity\FinderInterface|null
   *   The finder entity which uses the given bundle entity as either channels
   *   or entries, or NULL if the given bundle entity is not used in a finder.
   */
  public function getFinderForBundleEntity(ConfigEntityInterface $bundle_entity): ?FinderInterface {
    $entity_type_id = $bundle_entity->getEntityType()->getBundleOf();

    // @todo Consider caching a lookup of bundle names => finder types.
    foreach ($this->loadMultiple() as $finder) {
      $channels = $finder->get('channels');
      if (isset($channels[$entity_type_id]) && in_array($bundle_entity->id(), $channels[$entity_type_id])) {
        return $finder;
      }

      $entries = $finder->get('entries');
      if (isset($entries[$entity_type_id]) && in_array($bundle_entity->id(), $entries[$entity_type_id])) {
        return $finder;
      }
    }

    return NULL;
  }

}
