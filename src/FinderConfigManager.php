<?php

namespace Drupal\localgov_finders;

use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Field\FieldDefinitionListenerInterface;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\localgov_finders\Enum\FinderRole;
use Drupal\localgov_finders\Plugin\FinderType\FinderTypeInterface;
use Drupal\node\NodeTypeInterface;
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
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle
   *   The bundle entity.
   *
   * @return array
   *   An array bundle field definitions, for either channel bundles or entry
   *   bundles as appropriate, keyed by the field name. If the given bundle
   *   entity is not configured for finders, an empty array is returned.
   */
  public function getBundleFieldDefinitions(ConfigEntityInterface $bundle): array {
    $finder_type_manager = \Drupal::service('plugin.manager.localgov_finders_finder_type');
    $finder_type = $finder_type_manager->getBundleFinderType($bundle);
    $finder_role_name = $bundle->getThirdPartySetting('localgov_finders', 'finder_role', '');

    if (!$finder_type) {
      return [];
    }
    else {
      return match ($finder_role_name) {
        FinderRole::Channel->value => $finder_type->getChannelFieldDefinitions($bundle),
        FinderRole::Entries->value => $finder_type->getEntryFieldDefinitions($bundle),
      };
    }
  }

  /**
   * Sets configuration on a bundle entity as finder channels.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type.
   * @param \Drupal\localgov_finders\Plugin\FinderType\FinderTypeInterface $finder_type
   *   The finder type plugin.
   */
  public function enableAsChannel(ConfigEntityInterface $entity_bundle, FinderTypeInterface $finder_type): void {
    $entity_bundle->setThirdPartySetting('localgov_finders', 'finder_type', $finder_type->getPluginId());
    $entity_bundle->setThirdPartySetting('localgov_finders', 'finder_role', FinderRole::Channel->value);
    $entity_bundle->save();
    // @see Drupal\localgov_finders\Hook\EntityHooks::entityUpdate
  }

  /**
   * Sets up a bundle as finder channels.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity_bundle
   *   The entity bundle entity.
   * @param \Drupal\localgov_finders\Plugin\FinderType\FinderTypeInterface $finder_type
   *   The finder type plugin.
   */
  public function configureAsChannel(ConfigEntityInterface $entity_bundle, FinderTypeInterface $finder_type): void {
    $entity_type_id = $entity_bundle->getEntityType()->getBundleOf();
    $bundle_id = $entity_bundle->id();

    // Register bundle fields on the entity type.
    foreach ($finder_type->getChannelFieldDefinitions($entity_bundle) as $field_definition) {
      // Notify the field definition listeners. This is what updates core's
      // field map.
      $this->fieldStorageDefinitionListener->onFieldStorageDefinitionCreate($field_definition);
      $this->fieldDefinitionListener->onFieldDefinitionCreate($field_definition);
    }

    //
    foreach ($finder_type->getIndexIds() as $index_id) {
      $index = $this->entityTypeManager->getStorage('search_api_index')->load($index_id);
      assert($index instanceof IndexInterface);
      $this->indexAddBundle($index, $entity_type_id, $bundle_id);
      // Configure fields on the index.
      // There are fields that need only adding once, they just can't exist
      // on the index till the content type and field is there.
      // eg The Directory Channel selection field.
      // There are also fields that could already exist that need the bundle
      // or other configuration or settings on them.
      // eg The rendered item bundle.

      foreach ($finder_type->getIndexFields($entity_bundle) as $field_name => $field_definition) {
        if (!$index->getField($field_name)) {
          $index->addField($field_definition);
        }
        $field = $index->getField($field_name);
        $finder_type->alterField($field_name, $field);
      }
      $this->renderedItemAddBundle($index, $entity_type_id, $entity_id);
      $this->indexAddChannelsField($index);
      // The Channel is also the trigger for adding/removing from the index.
      // So also handle fields already existing on the entity that should be
      // included in the index.
      //$entity_fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $entity_bundle);
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
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type.
   * @param \Drupal\localgov_finders\Plugin\FinderType\FinderTypeInterface $finder_type
   *   The finder type plugin.
   */
  public function enableAsEntry(ConfigEntityInterface $entity_bundle, FinderTypeInterface $finder_type): void {
    $entity_bundle->setThirdPartySetting('localgov_finders', 'finder_type', $finder_type->getPluginId());
    $entity_bundle->setThirdPartySetting('localgov_finders', 'finder_role', FinderRole::Entries->value);
    $entity_bundle->save();
    // @see Drupal\localgov_finders\Hook\EntityHooks::entityUpdate
  }

  public function configureAsEntry(ConfigEntityInterface $entity_bundle, FinderTypeInterface $finder_type): void {
    // fields:
    // localgov_directory_channels
    // localgov_directory_facets_select
    // localgov_directory_title_sort

    // TODO:
    // Create config.

    // Update existing config.
  }

  /**
   * Add entity bundle to index datasource.
   *
   * @todo could this be replaced with a Search API plugin that looks for
   * enabled bundles. It would be of all entity types though. And we still add
   * fields so change the config, so it's maybe fine to keep doing here?
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to add bundle to.
   * @param string $entity_type_id
   *   Entity type ID.
   * @param string $entity_bundle
   *   The bundle ID.
   */
  protected function indexAddBundle(IndexInterface $index, string $entity_type_id, string $entity_bundle): void {
    $datasource = $this->indexGetDatasource($index, $entity_type_id);
    if (!$datasource) {
      $this->logger->error('Failed to update the directories search index with new bundle');
      return;
    }

    $configuration = $datasource->getConfiguration();
    $configuration['bundles']['default'] = FALSE;
    if (!in_array($entity_bundle, $configuration['bundles']['selected'])) {
      $configuration['bundles']['selected'][] = $entity_bundle;
    }
    $datasource->setConfiguration($configuration);
  }

  /**
   * Get index entity datasource.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to retrieve the datasource from.
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return \Drupal\search_api\Datasource\DatasourceInterface
   *   The datasource.
   */
  protected function indexGetDatasource(IndexInterface $index, string $entity_type_id): DatasourceInterface {
    $datasource = $index->getDatasource('entity:' . $entity_type_id);
    if (!$datasource) {
      // If the content:node datasource has been lost so have the fields most
      // probably and it's more of a mess. But leaving this here anyway.
      $datasource = $this->pluginHelper->createDatasourcePlugin($index, 'entity:' . $entity_type_id);
    }

    return $datasource;
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
