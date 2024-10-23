<?php

namespace Drupal\localgov_finders;

use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Field\FieldDefinitionListenerInterface;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\localgov_finders\Enum\FinderRole;
use Drupal\localgov_finders\Plugin\FinderType\FinderTypeInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\search_api\Utility\PluginHelperInterface;

/**
 * Manages creation of configuration for Finders.
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
   * Sets up a node type as a finder channel.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type.
   * @param \Drupal\localgov_finders\Plugin\FinderType\FinderTypeInterface $finder_type
   *   The finder type plugin.
   */
  public function enableNodeTypeAsChannel(NodeTypeInterface $node_type, FinderTypeInterface $finder_type): void {
    $node_type->setThirdPartySetting('localgov_finders', 'finder_type', $finder_type->getPluginId());
    $node_type->setThirdPartySetting('localgov_finders', 'finder_role', FinderRole::Channel->value);
    $node_type->save();

    // Create fields on the node type.
    foreach ($finder_type->getChannelFieldDefinitions() as $field_definition) {
      $this->fieldStorageDefinitionListener->onFieldStorageDefinitionCreate($field_definition);
      $this->fieldDefinitionListener->onFieldDefinitionCreate($field_definition);
    }
  }

  /**
   * Sets up a node type as finder entries.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type.
   * @param \Drupal\localgov_finders\Plugin\FinderType\FinderTypeInterface $finder_type
   *   The finder type plugin.
   */
  public function enableNodeTypeAsFinderEntries(NodeTypeInterface $node_type, FinderTypeInterface $finder_type): void {
    $node_type->setThirdPartySetting('localgov_finders', 'finder_type', $finder_type->getPluginId());
    $node_type->setThirdPartySetting('localgov_finders', 'finder_role', FinderRole::Entries->value);
    $node_type->save();

    // fields:
    // localgov_directory_channels
    // localgov_directory_facets_select
    // localgov_directory_title_sort

  }

}
