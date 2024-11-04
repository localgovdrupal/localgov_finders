<?php

namespace Drupal\localgov_finders;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\localgov_finders\Attribute\FinderType;
use Drupal\localgov_finders\Enum\FinderRole;
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

  /**
   * Gets the finder type plugin for an entity bundle entity.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle
   *   The bundle entity.
   *
   * @return \Drupal\localgov_finders\Plugin\FinderType\FinderTypeInterface
   *   A finder type plugin if the bundle entity has one set, or NULL otherwise.
   */
  public function getBundleFinderType(ConfigEntityInterface $bundle): ?FinderTypeInterface {
    $finder_type_id = $bundle->getThirdPartySetting('localgov_finders', 'finder_type', '');

    if ($finder_type_id) {
      return $this->createInstance($finder_type_id);
    }
    else {
      return NULL;
    }
  }

  /**
   * Gets the bundle entities which are entries for the given finder type.
   *
   * For example, for the node entity type and the directories finder type, this
   * will return all the node type entities which are configured to be directory
   * entries.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $content_entity_type
   *   The entity type to get bundles for.
   * @param \Drupal\localgov_finders\Plugin\FinderType\FinderTypeInterface $finder_type
   *   The finder type plugin to get bundles for.
   *
   * @return array
   *   An array of bundle entities, keyed by the entity ID.
   */
  public function getEntryBundles(EntityTypeInterface $content_entity_type, FinderTypeInterface $finder_type): array {
    $bundle_entity_type_id = $content_entity_type->getBundleEntityType();

    $entity_type_manager = \Drupal::service('entity_type.manager');
    $bundle_entities = $entity_type_manager->getStorage($bundle_entity_type_id)->loadMultiple();
    $finder_type_id = $finder_type->getPluginId();

    return array_filter(
      $bundle_entities,
      fn ($bundle_entity) =>
        $bundle_entity->getThirdPartySetting('localgov_finders', 'finder_type', '') == $finder_type_id &&
        $bundle_entity->getThirdPartySetting('localgov_finders', 'finder_role', '') == FinderRole::Entries->value
    );
  }

  /**
   * Gets the bundle entities which are channels for the given finder type.
   *
   * For example, for the node entity type and the directories finder type, this
   * will return all the node type entities which are configured to be directory
   * channels.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $content_entity_type
   *   The entity type to get bundles for.
   * @param \Drupal\localgov_finders\Plugin\FinderType\FinderTypeInterface $finder_type
   *   The finder type plugin to get bundles for.
   *
   * @return array
   *   An array of bundle entities, keyed by the entity ID.
   */
  public function getChannelBundles(EntityTypeInterface $content_entity_type, FinderTypeInterface $finder_type): array {
    $bundle_entity_type_id = $content_entity_type->getBundleEntityType();

    $entity_type_manager = \Drupal::service('entity_type.manager');
    $bundle_entities = $entity_type_manager->getStorage($bundle_entity_type_id)->loadMultiple();
    $finder_type_id = $finder_type->getPluginId();

    return array_filter(
      $bundle_entities,
      fn ($bundle_entity) =>
        $bundle_entity->getThirdPartySetting('localgov_finders', 'finder_type', '') == $finder_type_id &&
        $bundle_entity->getThirdPartySetting('localgov_finders', 'finder_role', '') == FinderRole::Channel->value
    );
  }

  /**
   * Gets all the active finder type plugins for a content entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $content_entity_type
   *   The content entity type to get finder plugins for.
   *
   * @return \Drupal\localgov_finders\Plugin\FinderType\FinderTypeInterface[]
   *   An array of finder type plugins, keyed by the plugin ID.
   */
  public function getActiveFinderTypes(EntityTypeInterface $content_entity_type): array {
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $bundle_entity_type_id = $content_entity_type->getBundleEntityType();
    $bundle_entities = $entity_type_manager->getStorage($bundle_entity_type_id)->loadMultiple();

    $finder_type_plugins = array_map($this->getBundleFinderType(...), $bundle_entities);
    $finder_type_plugins = array_filter($finder_type_plugins);

    $finder_type_plugins_keyed = [];
    foreach ($finder_type_plugins as $finder_type_plugin) {
      $finder_type_plugins_keyed[$finder_type_plugin->getPluginId()] = $finder_type_plugin;
    }
    return $finder_type_plugins_keyed;
  }

}
