<?php

namespace Drupal\finders_facets\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Contains facets hook implementations for the Finders facets module.
 */
class FacetsHooks {

  /**
   * The data type name used to switch the query for facets.
   */
  public const QUERY_TYPE = 'finders_facets';

  /**
   * Implements hook_facets_search_api_query_type_mapping_alter().
   */
  #[Hook('facets_search_api_query_type_mapping_alter')]
  public function facetsSearchApiQueryTypeMappingAlter($backend_plugin_id, array &$query_types) {
    // Register a query type mapping so that our facets can use a custom query.
    // @see \Drupal\finders_facets\Plugin\facets\processor\FindersFacetBundlesProcessor
    // @see Drupal\finders_facets\Plugin\facets\query_type\FindersFacetsQueryType
    $query_types[static::QUERY_TYPE] = 'finders_facets_query_type';
  }

}
