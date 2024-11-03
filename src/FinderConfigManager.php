<?php

namespace Drupal\localgov_finders;

use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\FileStorage as ConfigFileStorage;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Field\FieldDefinitionListenerInterface;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\localgov_finders\Enum\FinderRole;
use Drupal\localgov_finders\Plugin\FinderType\FinderTypeInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Utility\PluginHelperInterface;

/**
 * Manages definition of fields and creation of configuration for Finders.
 */
class FinderConfigManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The config installer service.
   *
   * @var \Drupal\Core\Config\ConfigInstallerInterface
   */
  protected $configInstaller;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerChannelFactory;

  /**
   * The plugin helper service.
   *
   * @var \Drupal\search_api\Utility\PluginHelperInterface
   */
  protected $pluginHelper;

  /**
   * The field storage definition listener service.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionListenerInterface
   */
  protected $fieldStorageDefinitionListener;

  /**
   * The field definition listener service.
   *
   * @var \Drupal\Core\Field\FieldDefinitionListenerInterface
   */
  protected $fieldDefinitionListener;

  /**
   * Creates a FinderConfigManager instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list service.
   * @param \Drupal\Core\Config\ConfigInstallerInterface $config_installer
   *   The config installer service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_channel_factory
   *   The logger channel factory.
   * @param \Drupal\search_api\Utility\PluginHelperInterface $plugin_helper
   *   The plugin helper service.
   * @param \Drupal\Core\Field\FieldStorageDefinitionListenerInterface $field_storage_definition_listener
   *   The field storage definition listener service.
   * @param \Drupal\Core\Field\FieldDefinitionListenerInterface $field_definition_listener
   *   The field definition listener service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    ModuleExtensionList $module_extension_list,
    ConfigInstallerInterface $config_installer,
    LoggerChannelFactoryInterface $logger_channel_factory,
    PluginHelperInterface $plugin_helper,
    FieldStorageDefinitionListenerInterface $field_storage_definition_listener,
    FieldDefinitionListenerInterface $field_definition_listener,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->moduleExtensionList = $module_extension_list;
    $this->configInstaller = $config_installer;
    $this->loggerChannelFactory = $logger_channel_factory;
    $this->pluginHelper = $plugin_helper;
    $this->fieldStorageDefinitionListener = $field_storage_definition_listener;
    $this->fieldDefinitionListener = $field_definition_listener;
  }

  /**
   * Gets the finder bundle fields for a given bundle.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle_entity
   *   The bundle entity.
   *
   * @return \Drupal\localgov_finders\Field\BundleFieldDefinition[]
   *   An array bundle field definitions, for either channel bundles or entry
   *   bundles as appropriate, keyed by the field name. If the given bundle
   *   entity is not configured for finders, an empty array is returned.
   */
  public function getBundleFieldDefinitions(ConfigEntityInterface $bundle_entity): array {
    $finder_type_manager = \Drupal::service('plugin.manager.localgov_finders_finder_type');
    $finder_type = $finder_type_manager->getBundleFinderType($bundle_entity);
    $finder_role_name = $bundle_entity->getThirdPartySetting('localgov_finders', 'finder_role', '');

    if (!$finder_type) {
      return [];
    }
    else {
      return match ($finder_role_name) {
        FinderRole::Channel->value => $finder_type->getChannelFieldDefinitions($bundle_entity),
        FinderRole::Entries->value => $finder_type->getEntryFieldDefinitions($bundle_entity),
      };
    }
  }

  /**
   * Sets configuration on a bundle entity as finder channels.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle_entity
   *   The bundle entity.
   * @param \Drupal\localgov_finders\Plugin\FinderType\FinderTypeInterface $finder_type
   *   The finder type plugin.
   */
  public function enableAsChannel(ConfigEntityInterface $bundle_entity, FinderTypeInterface $finder_type): void {
    $bundle_entity->setThirdPartySetting('localgov_finders', 'finder_type', $finder_type->getPluginId());
    $bundle_entity->setThirdPartySetting('localgov_finders', 'finder_role', FinderRole::Channel->value);
    $bundle_entity->save();
    // @see Drupal\localgov_finders\Hook\EntityHooks::entityUpdate
  }

  /**
   * Sets up a bundle as finder channels.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle_entity
   *   The entity bundle entity.
   * @param \Drupal\localgov_finders\Plugin\FinderType\FinderTypeInterface $finder_type
   *   The finder type plugin.
   */
  public function configureAsChannel(ConfigEntityInterface $bundle_entity, FinderTypeInterface $finder_type): void {
    $entity_type_id = $bundle_entity->getEntityType()->getBundleOf();
    $bundle_id = $bundle_entity->id();

    // Register bundle fields on the entity type.
    foreach ($finder_type->getChannelFieldDefinitions($bundle_entity) as $field_definition) {
      // Notify the field definition listeners. This is what updates core's
      // field map.
      $this->fieldStorageDefinitionListener->onFieldStorageDefinitionCreate($field_definition);
      $this->fieldDefinitionListener->onFieldDefinitionCreate($field_definition);
    }

    foreach ($finder_type->getIndexIds() as $index_id) {
      $index = $this->entityTypeManager->getStorage('search_api_index')->load($index_id);
      // Create the Finder's search indexe if it doesn't already exist.
      if (empty($index)) {
        $index = $this->loadTemplateIndex($index_id, $finder_type);
      }
      assert($index instanceof IndexInterface);
      $finder_type->alterSearchIndexForChannel($index, $bundle_entity);
      $index->save();
    }
  }

  /**
   * Add settings for bundle as finder entry.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle_entity
   *   The bundle entity.
   * @param \Drupal\localgov_finders\Plugin\FinderType\FinderTypeInterface $finder_type
   *   The finder type plugin.
   */
  public function enableAsEntry(ConfigEntityInterface $bundle_entity, FinderTypeInterface $finder_type): void {
    $bundle_entity->setThirdPartySetting('localgov_finders', 'finder_type', $finder_type->getPluginId());
    $bundle_entity->setThirdPartySetting('localgov_finders', 'finder_role', FinderRole::Entries->value);
    $bundle_entity->save();
    // @see Drupal\localgov_finders\Hook\EntityHooks::entityUpdate
  }

  /**
   * Sets up a bundle as finder entries.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle_entity
   *   The entity bundle entity.
   * @param \Drupal\localgov_finders\Plugin\FinderType\FinderTypeInterface $finder_type
   *   The finder type plugin.
   */
  public function configureAsEntry(ConfigEntityInterface $bundle_entity, FinderTypeInterface $finder_type): void {
    $entity_type_id = $bundle_entity->getEntityType()->getBundleOf();
    $bundle_id = $bundle_entity->id();

    // Register bundle fields on the entity type.
    foreach ($finder_type->getEntryFieldDefinitions($bundle_entity) as $field_definition) {
      // Notify the field definition listeners. This is what updates core's
      // field map.
      $this->fieldStorageDefinitionListener->onFieldStorageDefinitionCreate($field_definition);
      $this->fieldDefinitionListener->onFieldDefinitionCreate($field_definition);
    }

    // fields:
    // localgov_directory_channels
    // localgov_directory_facets_select
    // localgov_directory_title_sort

    // TODO:
    // Create config.

    // Update search indexes.
    // TODO: There is no guarantee the channel got configured first!!!!
    // @todo Maybe it only makes sense to be able to create a entry type once
    //   there is a channel type? Make config depend on each other?
    //   Equally the you can't remove the channel type till the entry types are
    //   gone.
    // Facets is also an extension (and seperate plugin probably), that could
    // should depend on the existence of the channel.
    foreach ($finder_type->getIndexIds() as $index_id) {
      $index = $this->entityTypeManager->getStorage('search_api_index')->load($index_id);
      $finder_type->alterSearchIndexForEntry($index, $bundle_entity);
      $index->save();
    }
  }

  /**
   * Creates a search index when a channel is created for a new finder type.
   *
   * This uses a template YAML config file in the config/template directory to
   * create a stub search index.
   * If there exists a Search API Index Config YAML file in the plugin module
   * config/template this will be used. If not the default index template will be used.
   *
   * @param string $index_id
   *   The ID of the search index to create.
   * @param \Drupal\localgov_finders\Plugin\FinderType\FinderTypeInterface $finder_type
   *   The finder type plugin.
   *
   * @return \Drupal\search_api\IndexInterface
   *   The search index created from the template configuration.
   */
  protected function loadTemplateIndex(string $index_id, FinderTypeInterface $finder_type): IndexInterface {
    $template_directory = $this->moduleExtensionList->getPath($finder_type->getPluginDefinition()['provider']) . '/config/template';
    $config_source = new ConfigFileStorage($template_directory);
    $config_filename = 'search_api.index.' . $index_id;
    if (!$config_source->exists($config_filename)) {
      $template_directory = $this->moduleExtensionList->getPath('localgov_finders') . '/config/template';
      $config_source = new ConfigFileStorage($template_directory);
      $config_filename = 'search_api.index.localgov_finders_index_template';
    }

    $config_values = $config_source->read($config_filename);
    $config_values['id'] = $index_id;
    $search_index = $this->entityTypeManager->getStorage('search_api_index')->create($config_values);

    return $search_index;
  }

}
