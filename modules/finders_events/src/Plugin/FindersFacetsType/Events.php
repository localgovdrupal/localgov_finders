<?php

namespace Drupal\finders_events\Plugin\FindersFacetsType;

use Drupal\facets\FacetInterface;
use Drupal\finders\Entity\FinderInterface;
use Drupal\finders_facets\Attribute\FindersFacetsType;
use Drupal\finders_facets\Plugin\FindersFacetsType\FindersFacetsTypeBase;
use Drupal\finders_events\Plugin\FinderType\Events as FinderEvents;
use Drupal\search_api\IndexInterface;
use Drupal\views\ViewEntityInterface;

/**
 * Finders facets type for events.
 *
 * Events use a facet for each view, calendar and list. These use the same facet
 * url_alias, which means that we can show a block just for one facet, and have
 * the facet links control both view simultaneously.
 */
#[FindersFacetsType(
  id: 'events',
)]
class Events extends FindersFacetsTypeBase {

}
