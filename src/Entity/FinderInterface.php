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

  /**
   * Gets the channel bundle entities for this finder.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface[]
   *   A numeric array of bundle entities which are used as channels by this
   *   finder.
   */
  public function getChannelBundles(): array;

  /**
   * Gets the entry bundle entities for this finder.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface[]
   *   A numeric array of bundle entities which are used as entries by this
   *   finder.
   */
  public function getEntryBundles(): array;

}
