<?php

namespace Drupal\finders;

use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\FileStorage as ConfigFileStorage;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Field\FieldDefinitionListenerInterface;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\finders\Enum\FinderRole;
use Drupal\finders\Plugin\FinderType\FinderTypeInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Utility\PluginHelperInterface;
use Drupal\views\ViewEntityInterface;

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
   * @return \Drupal\finders\Field\BundleFieldDefinition[]
   *   An array bundle field definitions, for either channel bundles or entry
   *   bundles as appropriate, keyed by the field name. If the given bundle
   *   entity is not configured for finders, an empty array is returned.
   */
  public function getBundleFieldDefinitions(ConfigEntityInterface $bundle_entity): array {
    $finder_type_manager = \Drupal::service('plugin.manager.finders_finder_type');
    $finder_type = $finder_type_manager->getBundleFinderType($bundle_entity);
    $finder_role_name = $bundle_entity->getThirdPartySetting('finders', 'finder_role', '');

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
   * @param \Drupal\finders\Plugin\FinderType\FinderTypeInterface $finder_type
   *   The finder type plugin.
   */
  public function enableAsChannel(ConfigEntityInterface $bundle_entity, FinderTypeInterface $finder_type): void {
    $bundle_entity->setThirdPartySetting('finders', 'finder_type', $finder_type->getPluginId());
    $bundle_entity->setThirdPartySetting('finders', 'finder_role', FinderRole::Channel->value);
    $bundle_entity->save();
    // @see Drupal\finders\Hook\EntityHooks::entityUpdate
  }

  /**
   * Sets up a bundle as finder channels.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle_entity
   *   The entity bundle entity.
   * @param \Drupal\finders\Plugin\FinderType\FinderTypeInterface $finder_type
   *   The finder type plugin.
   */
  public function configureAsChannel(ConfigEntityInterface $bundle_entity, FinderTypeInterface $finder_type): void {
    $entity_type_id = $bundle_entity->getEntityType()->getBundleOf();
    $bundle_id = $bundle_entity->id();

    $channel_field_definitions = $finder_type->getChannelFieldDefinitions($bundle_entity);

    // Allow modules to alter the channel field definitions.
    \Drupal::moduleHandler()->alter('finders_channel_fields', $channel_field_definitions, $bundle_entity, $finder_type);

    // Register bundle fields on the entity type.
    foreach ($channel_field_definitions as $field_definition) {
      // Notify the field definition listeners. This is what updates core's
      // field map.
      $this->fieldStorageDefinitionListener->onFieldStorageDefinitionCreate($field_definition);
      $this->fieldDefinitionListener->onFieldDefinitionCreate($field_definition);
    }

    // Set up the indexes for the finder type.
    foreach ($finder_type->getIndexIds() as $index_id) {
      $index = $this->entityTypeManager->getStorage('search_api_index')->load($index_id);
      // Create the Finder's search index if it doesn't already exist.
      if (empty($index)) {
        $index = $this->loadTemplateIndex($index_id, $finder_type);
      }
      assert($index instanceof IndexInterface);
      $finder_type->alterSearchIndexForChannel($index, $bundle_entity);

      // Allow modules to alter the channel field definitions.
      // This is a separate alter hook so that the bundle fields exist for
      // implementations of this hook to check.
      \Drupal::moduleHandler()->alter('finders_index', $index, $bundle_entity, $finder_type);

      $index->save();

      // Set up a view for the index.
      foreach ($finder_type->getViewIds($index) as $view_id) {
        $view = $this->entityTypeManager->getStorage('view')->load($view_id);
        // Create the view if it doesn't already exist.
        if (empty($view)) {
          $view = $this->loadTemplateView($view_id, $finder_type, $index);
        }
        // $finder_type->alterViewForChannel($view, $index, $bundle_entity);

        $view->save();
      }
    }
  }

  /**
   * Add settings for bundle as finder entry.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle_entity
   *   The bundle entity.
   * @param \Drupal\finders\Plugin\FinderType\FinderTypeInterface $finder_type
   *   The finder type plugin.
   */
  public function enableAsEntry(ConfigEntityInterface $bundle_entity, FinderTypeInterface $finder_type): void {
    $bundle_entity->setThirdPartySetting('finders', 'finder_type', $finder_type->getPluginId());
    $bundle_entity->setThirdPartySetting('finders', 'finder_role', FinderRole::Entries->value);
    $bundle_entity->save();
    // @see Drupal\finders\Hook\EntityHooks::entityUpdate
  }

  /**
   * Sets up a bundle as finder entries.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle_entity
   *   The entity bundle entity.
   * @param \Drupal\finders\Plugin\FinderType\FinderTypeInterface $finder_type
   *   The finder type plugin.
   */
  public function configureAsEntry(ConfigEntityInterface $bundle_entity, FinderTypeInterface $finder_type): void {
    $entity_type_id = $bundle_entity->getEntityType()->getBundleOf();
    $bundle_id = $bundle_entity->id();

    $entry_field_definitions = $finder_type->getEntryFieldDefinitions($bundle_entity);

    // Allow modules to alter the entry field definitions.
    \Drupal::moduleHandler()->alter('finders_entry_fields', $entry_field_definitions, $bundle_entity, $finder_type);

    // Register bundle fields on the entity type.
    foreach ($entry_field_definitions as $field_definition) {
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
    // @todo Maybe it only makes sense to be able to create a entry type once
    //   there is a channel type? Make config depend on each other?
    //   Equally the you can't remove the channel type till the entry types are
    //   gone.
    // Facets is also an extension (and seperate plugin probably), that could
    // should depend on the existence of the channel.
    foreach ($finder_type->getIndexIds() as $index_id) {
      $index = $this->entityTypeManager->getStorage('search_api_index')->load($index_id);
      // Create the Finder's search index if it doesn't already exist.
      if (empty($index)) {
        $index = $this->loadTemplateIndex($index_id, $finder_type);
      }
      assert($index instanceof IndexInterface);

      $finder_type->alterSearchIndexForEntry($index, $bundle_entity);

      \Drupal::moduleHandler()->alter('finders_index', $index, $bundle_entity, $finder_type);

      $index->save();
    }
  }

  /**
   * Creates a stub search index template from a template YAML config file.
   *
   * If there exists a Search API Index Config YAML file in the config/template
   * directory of the module that provides the finder type, then this is used as
   * the template. If not, the default index template will be used.
   *
   * @param string $index_id
   *   The ID of the search index to create.
   * @param \Drupal\finders\Plugin\FinderType\FinderTypeInterface $finder_type
   *   The finder type plugin.
   *
   * @return \Drupal\search_api\IndexInterface
   *   The search index created from the template configuration. It is the
   *   caller's responsibility to save this.
   */
  protected function loadTemplateIndex(string $index_id, FinderTypeInterface $finder_type): IndexInterface {
    $template_directory = $this->moduleExtensionList->getPath($finder_type->getPluginDefinition()['provider']) . '/config/template';
    $config_source = new ConfigFileStorage($template_directory);
    $config_filename = 'search_api.index.' . $index_id;

    // Fall back to the default index template if the finder type module does
    // not provide a template for the index ID.
    if (!$config_source->exists($config_filename)) {
      $template_directory = $this->moduleExtensionList->getPath('finders') . '/config/template';
      $config_source = new ConfigFileStorage($template_directory);
      $config_filename = 'search_api.index.finders_index_template';
    }

    $config_values = $config_source->read($config_filename);

    // Set the ID and name of the index.
    $config_values['id'] = $index_id;
    $config_values['name'] = $finder_type->getPluginDefinition()['label'];

    $search_index = $this->entityTypeManager->getStorage('search_api_index')->create($config_values);

    // Set a default server if one exists.
    $servers = $this->entityTypeManager->getStorage('search_api_server')->loadMultiple();
    foreach ($servers as $server_id => $server) {
      if ($server->getThirdPartySetting('finders', 'default_server', NULL)) {
        $search_index->setServer($server);
        $search_index->setStatus(TRUE);
      }
    }

    return $search_index;
  }

  /**
   * Creates a stub view template from a template YAML config file.
   *
   * @param string $view_id
   *   The ID of the view to create.
   * @param \Drupal\finders\Plugin\FinderType\FinderTypeInterface $finder_type
   *   The finder type plugin.
   * @param \Drupal\search_api\IndexInterface $search_index
   *   The search index which is the source for the view's data. This has
   *   already been set up for the finder type.
   *
   * @return \Drupal\views\ViewEntityInterface
   *   The view created from the template configuration. It is the caller's
   *   responsibility to save this.
   *
   * @see config/template/views.view.finder_channel_template.yml
   */
  protected function loadTemplateView(string $view_id, FinderTypeInterface $finder_type, IndexInterface $search_index): ViewEntityInterface {
    $template_directory = $this->moduleExtensionList->getPath($finder_type->getPluginDefinition()['provider']) . '/config/template';
    $config_source = new ConfigFileStorage($template_directory);
    $config_filename = 'views.view.' . $view_id;

    // Fall back to the default index template if the finder type module does
    // not provide a template for the view ID.
    if (!$config_source->exists($config_filename)) {
      $template_directory = $this->moduleExtensionList->getPath('finders') . '/config/template';
      $config_source = new ConfigFileStorage($template_directory);
      $config_filename = 'views.view.finder_channel_template';
    }

    $config_values = $config_source->read($config_filename);

    // Set the ID and label of the view.
    $config_values['id'] = $view_id;
    $config_values['label'] = $finder_type->getPluginDefinition()['label'];

    // Set the index as a config and cache dependency.
    $config_values['dependencies']['config'][] = $search_index->id();
    foreach ($config_values['display'] as &$display_definition) {
      $display_definition['cache_metadata']['tags'][] = 'config:search_api.index.' . $search_index->id();
    }

    // Set up the base table.
    // search_api_views_data() declares a base table for each index.
    $base_table = 'search_api_index_' . $search_index->id();
    $config_values['base_table'] = $base_table;
    foreach (['fields', 'filters', 'sorts', 'arguments'] as $views_plugin_type) {
      foreach ($config_values['display']['default']['display_options'][$views_plugin_type] as &$definition) {
        $definition['table'] = $base_table;
      }
    }

    $view = $this->entityTypeManager->getStorage('view')->create($config_values);

    return $view;
  }

}
