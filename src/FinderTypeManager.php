<?php

namespace Drupal\localgov_finders;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\localgov_finders\Attribute\FinderType;
use Drupal\localgov_finders\Plugin\FinderType\FinderTypeInterface;

/**
 * Manages discovery and instantiation of Finder Type plugins.
 */
class FinderTypeManager extends DefaultPluginManager {

  /**
   * Constructs a new FinderTypeManagerManager.
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
      'Plugin/FinderType',
      $namespaces,
      $module_handler,
      FinderTypeInterface::class,
      FinderType::class
    );

    $this->alterInfo('finder_type_info');
    $this->setCacheBackend($cache_backend, 'finder_type_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function getType() {
    return 'finder_type';
  }

}
