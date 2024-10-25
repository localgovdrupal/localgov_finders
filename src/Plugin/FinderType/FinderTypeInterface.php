<?php

namespace Drupal\localgov_finders\Plugin\FinderType;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for Finder Type plugins.
 */
interface FinderTypeInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Gets the bundle field definitions for a finder channel node type.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type.
   *
   * @return array
   *   An array of field definitions, keyed by the field name.
   */
  public function getChannelFieldDefinitions(ConfigEntityInterface $bundle): array;

}
