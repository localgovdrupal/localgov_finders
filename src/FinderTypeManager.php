<?php

namespace Drupal\finders;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\finders\Attribute\FinderType;
use Drupal\finders\Plugin\FinderType\FinderTypeInterface;

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

  /**
   * Gets all the active finder type plugins for a content entity type.
   *
   * @return \Drupal\finders\Plugin\FinderType\FinderTypeInterface[]
   *   An array of finder type plugins, keyed by the plugin ID.
   */
  public function getActiveFinderTypes(): array {
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $finder_entities = $entity_type_manager->getStorage('finder')->loadMultiple();

    $finder_type_plugins = array_map(fn ($finder) => $finder->getFinderTypePlugin(), $finder_entities);
    $finder_type_plugins = array_filter($finder_type_plugins);

    $finder_type_plugins_keyed = [];
    foreach ($finder_type_plugins as $finder_type_plugin) {
      $finder_type_plugins_keyed[$finder_type_plugin->getPluginId()] = $finder_type_plugin;
    }
    return $finder_type_plugins_keyed;
  }

}
