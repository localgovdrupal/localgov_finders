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
 */
#[FindersFacetsType(
  id: 'events',
)]
class Events extends FindersFacetsTypeBase {

  /**
   * {@inheritdoc}
   */
  protected function loadViewContentFacetsFacet(string $facet_id, array $facet_template_config_values, FinderInterface $finder, IndexInterface $index, ViewEntityInterface $view): ?FacetInterface {
    // Don't provide a facet for the calendar view.
    if ($view->id() == FinderEvents::CALENDAR_VIEW_FIELD) {
      return NULL;
    }
  }

}
