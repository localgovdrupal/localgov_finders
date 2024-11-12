<?php

namespace Drupal\finders\Entity\Handler;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\finders\Entity\FinderInterface;

/**
 * Provides the storage handler for the Finder entity.
 */
class FinderStorage extends ConfigEntityStorage {

  public function getFinderForBundleEntity(ConfigEntityInterface $bundle_entity): ?FinderInterface {
    $entity_type_id = $bundle_entity->getEntityType()->getBundleOf();

    // @todo Consider caching a lookup of bundle names => finder types.
    foreach ($this->loadMultiple() as $finder) {
      if (in_array($bundle_entity->id(), $finder->get('channels')[$entity_type_id])) {
        return $finder;
      }
      if (in_array($bundle_entity->id(), $finder->get('entries')[$entity_type_id])) {
        return $finder;
      }
    }

    return NULL;
  }

}
