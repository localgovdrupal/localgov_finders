<?php

namespace Drupal\finders\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\finders\Plugin\FinderType\FinderTypeInterface;

/**
 * Interface for Finder entities.
 */
interface FinderInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Gets the finder type plugin for this finder.
   *
   * @return \Drupal\finders\Plugin\FinderType\FinderTypeInterface
   *   A finder type plugin instance.
   */
  public function getFinderTypePlugin(): FinderTypeInterface;

}
