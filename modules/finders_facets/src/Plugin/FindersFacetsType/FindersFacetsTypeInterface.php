<?php

namespace Drupal\finders_facets\Plugin\FindersFacetsType;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\finders\Entity\FinderInterface;

/**
 * Interface for Finders Facets Type plugins.
 *
 * Finders facet type plugins are parallel to Finder type plugins. They are not
 * selected directly, but if a finder facet type plugin exists with the same ID
 * as a finder entity's finder type plugin ID, then it is used for facet
 * configuration for that finder.
 */
interface FindersFacetsTypeInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Performs configuration for a finder's facets.
   *
   * @param \Drupal\finders\Entity\FinderInterface $finder
   *   The finder entity.
   *
   * @see \Drupal\finders\FinderConfigManage::ensureFinderConfig()
   * @see \Drupal\finders_facets\Hook\FindersHooks::findersPostConfigure()
   */
  public function findersPostConfigure(FinderInterface $finder): void;

}
