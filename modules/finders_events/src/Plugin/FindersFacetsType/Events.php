<?php

namespace Drupal\finders_events\Plugin\FindersFacetsType;

use Drupal\facets\FacetInterface;
use Drupal\finders\Entity\FinderInterface;
use Drupal\finders_facets\Attribute\FindersFacetsType;
use Drupal\finders_facets\Plugin\FindersFacetsType\FindersFacetsTypeBase;
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

  /**
   * {@inheritdoc}
   */
  protected function ensureViewFacets(FinderInterface $finder, IndexInterface $index, ViewEntityInterface $view): array {
    // @todo Add a facet for the date occurrence field.
    // $facet_storage = $this->entityTypeManager->getStorage('facets_facet');

    // $facet_id = $index->id() . '_' . $view->id() . '_date';

    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function ensureFacetBlock(FacetInterface $facet, FinderInterface $finder, IndexInterface $index, ViewEntityInterface $view): void {
    // Don't create a facet block for the calendar view. The facet block for the
    // list view will control both views.
    if ($view->id() == 'finders_events_channel_calendar') {
      return;
    }

    parent::ensureFacetBlock($facet, $finder, $index, $view);
  }

}
