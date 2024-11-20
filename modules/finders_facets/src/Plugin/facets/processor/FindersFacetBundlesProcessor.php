<?php

namespace Drupal\finders_facets\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\PreQueryProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Drupal\finders_facets\Hook\FacetsHooks;

/**
 * ANDs LocalGov Directories Facet Groups while keeping OR within each group.
 *
 * Switches the query type for finders facets entities.
 *
 * @see hook_facets_search_api_query_type_mapping_alter()
 *
 * @FacetsProcessor(
 *   id = "finders_facets_bundle",
 *   label = @Translation("LocalGov Directories - AND Facet Groups"),
 *   description = @Translation("ANDs LocalGov Directories Facet Groups while keeping OR within each group."),
 *   stages = {
 *     "pre_query" = 35
 *   }
 * )
 */
class FindersFacetBundlesProcessor extends ProcessorPluginBase implements PreQueryProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function preQuery(FacetInterface $facet) {
    $active_items = $facet->getActiveItems();
    $facet->setActiveItems($active_items);
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryType() {
    // String corresponds to a key on the $query_types array as defined in
    // hook_facets_search_api_query_type_mapping_alter().
    return FacetsHooks::QUERY_TYPE;
  }

}
