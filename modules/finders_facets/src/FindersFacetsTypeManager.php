<?php

namespace Drupal\finders_facets;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\finders_facets\Attribute\FindersFacetsType;
use Drupal\finders_facets\Plugin\FindersFacetsType\FindersFacetsTypeInterface;

/**
 * Manages discovery and instantiation of Finders Facets Type plugins.
 */
class FindersFacetsTypeManager extends DefaultPluginManager {

  /**
   * Constructs a new FindersFacetsTypeManagerManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
  ) {
    parent::__construct(
      'Plugin/FindersFacetsType',
      $namespaces,
      $module_handler,
      FindersFacetsTypeInterface::class,
      FindersFacetsType::class
    );

    $this->alterInfo('finders_facets_type_info');
    $this->setCacheBackend($cache_backend, 'finders_facets_type_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function getType() {
    return 'finders_facets_type';
  }

}
