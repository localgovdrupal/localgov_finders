<?php

namespace Drupal\localgov_finders;

use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Config\FileStorage as ConfigFileStorage;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Field\FieldDefinitionListenerInterface;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\localgov_finders\Enum\FinderRole;
use Drupal\localgov_finders\Plugin\FinderType\FinderTypeInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
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

    // Create the finder type's search indexes if they don't already.
    $index_ids = $finder_type->getIndexIds();
    foreach ($index_ids as $index_id) {
      $index = $this->entityTypeManager->getStorage('search_api_index')->load($index_id);

      if (empty($index)) {
        $this->createSearchIndex($bundle_entity, $finder_type, $index_id);
      }
    }

    // TEMP! The rest of this method doesn't work yet!
    return;

    foreach ($finder_type->getIndexIds() as $index_id) {
      $index = $this->entityTypeManager->getStorage('search_api_index')->load($index_id);
      assert($index instanceof IndexInterface);

      try {
        // This doesn't look right -- it's adding the channel bundle!
        $finder_type->indexAddBundle($index, $bundle_entity);
      }
      catch (\Exception $e) {
        $this->loggerChannelFactory->get('localgov_finders')->error('Failed to update the directories search index with new bundle');
      }

      // Configure fields on the index.
      // There are fields that need only adding once, they just can't exist
      // on the index till the content type and field is there.
      // eg The Directory Channel selection field.
      // There are also fields that could already exist that need the bundle
      // or other configuration or settings on them.
      // eg The rendered item bundle.

      foreach ($finder_type->getIndexFields($bundle_entity, $index) as $field_name => $field_definition) {
        if (!$index->getField($field_name)) {
          $index->addField($field_definition);
        }
        $field = $index->getField($field_name);
        $finder_type->alterField($field_name, $field);
      }
      $this->renderedItemAddBundle($index, $entity_type_id, $bundle_id);
      $this->indexAddChannelsField($index);
      // The Channel is also the trigger for adding/removing from the index.
      // So also handle fields already existing on the entity that should be
      // included in the index.
      //$entity_fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle_entity);
      //if (array_key_exists(Constants::FACET_SELECTION_FIELD, $entity_fields)) {
      //  $this->indexAddFacetField($index);
      //}
      //if (array_key_exists(Constants::TITLE_SORT_FIELD, $entity_fields)) {
      //  $this->indexAddTitleSortField($index);
      //}
      $finder_type->indexAlter($index);
      $index->save();

    }
    // TODO:
    // Create config.

    // Update existing config.
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

    // Update existing config.
    foreach ($finder_type->getIndexIds() as $index_id) {
      $index = $this->entityTypeManager->getStorage('search_api_index')->load($index_id);
      // TODO! not yet working!
      // $finder_type->indexAddBundle($index, $bundle_entity);
    }
  }

  /**
   * Creates a search index when a channel is created for a new finder type.
   *
   * This uses a template YAML config file in the config/template directory to
   * create a stub search index.
   *
   * The search index is not yet functional until at least one entry bundle is
   * created and a Search API backend set on it.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $channel_bundle_entity
   *   The channel bundle entity.
   * @param \Drupal\localgov_finders\Plugin\FinderType\FinderTypeInterface $finder_type
   *   The finder type plugin.
   * @param string $index_id
   *   The ID of the search index to create.
   */
  protected function createSearchIndex(ConfigEntityInterface $channel_bundle_entity, FinderTypeInterface $finder_type, string $index_id): void {
    $template_config_path = $this->moduleExtensionList->getPath('localgov_finders') . '/config/template';

    $config_src = new ConfigFileStorage($template_config_path);

    $config_filename = 'search_api.index.localgov_finders_index_template';

    $config_values = $config_src->read($config_filename);
    // dump($config_values);

    // Set the search index ID and create it.
    $config_values['id'] = $index_id;
    $search_index = $this->entityTypeManager->getStorage('search_api_index')->create($config_values);

    // Allow the finder type plugin to make changes.
    $finder_type->alterSearchIndexForChannel($search_index, $channel_bundle_entity);

    $search_index->save();
  }

  /**
   * Add entity bundle to index rendered item field.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to add bundle to.
   * @param string $entity_type_id
   *   Entity type ID.
   * @param string $entity_bundle
   *   The bundle ID.
   */
  protected function renderedItemAddBundle(IndexInterface $index, string $entity_type_id, string $entity_bundle): void {
    $index_field = $index->getField('rendered_item');
    if ($index_field) {
      $configuration = $index_field->getConfiguration();
      $configuration['view_mode']['entity:' . $entity_type_id][$entity_bundle] = 'directory_index';
      $index_field->setConfiguration($configuration);
    }
  }

  /**
   * Setup indexing on the Directory channels field of Directory entries.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to the channel field to.
   */
  protected function indexAddChannelsField(IndexInterface $index): void {
  }


}
