<?php

namespace Drupal\finders_facets\Plugin\FindersFacetsType;

use Drupal\Component\Plugin\PluginBase;
use Drupal\finders\Entity\FinderInterface;
use Drupal\search_api\IndexInterface;
use Drupal\views\ViewEntityInterface;

/**
 * Base class for Finders Facets Type plugins.
 */
abstract class FindersFacetsTypeBase extends PluginBase implements FindersFacetsTypeInterface {

  /**
   * Ensure additional facets for an index and view.
   *
   * @param \Drupal\finders\Entity\FinderInterface $finder
   *   The finder being configured.
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index to add facets for.
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view on the given search index to add facets for.
   */
  protected function ensureViewFacets(FinderInterface $finder, IndexInterface $index, ViewEntityInterface $view): void {
    // Do nothing in this plugin.
  }

}
