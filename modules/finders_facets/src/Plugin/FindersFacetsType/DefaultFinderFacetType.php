<?php

namespace Drupal\finders_facets\Plugin\FindersFacetsType;

use Drupal\finders_facets\Attribute\FindersFacetsType;

/**
 * Default plugin for finder facets types.
 *
 * This is used if there is no finders facet plugin whose ID matches a finder
 * entity's finder type plugin.
 */
#[FindersFacetsType(
  id: '_default',
)]
class DefaultFinderFacetType extends FindersFacetsTypeBase {

}
