<?php

namespace Drupal\localgov_finders\Plugin\FinderType;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\localgov_finders\Field\BundleFieldDefinition;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\Field as SearchIndexField;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\search_api\Utility\PluginHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Finder Type plugins.
 *
 * @todo there's a lot in here now.
 * Should some maybe go out into Traits / Services?
 */
abstract class FinderTypeBase extends PluginBase implements FinderTypeInterface, ContainerFactoryPluginInterface {

  // @todo Confirm we want typed constants, and target ≥ PHP8.3

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The plugin helper service.
   *
   * @var \Drupal\search_api\Utility\PluginHelperInterface
   */
  protected $pluginHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('search_api.plugin_helper'),
    );
  }

  /**
   * Creates a FinderTypeBase instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\search_api\Utility\PluginHelperInterface $plugin_helper
   *   The plugin helper service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    PluginHelperInterface $plugin_helper,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->pluginHelper = $plugin_helper;
  }

  /**
   * The field name for the channel types field.
   *
   * @see self::getChannelTypesFieldDefinition()
   */
  const string CHANNEL_TYPES_FIELD = 'localgov_finders_channel_types';

  /**
   * The field name for the channel selection field.
   *
   * @see self::getChannelSelectionFieldDefinition()
   */
  const string CHANNEL_SELECTION_FIELD = 'localgov_finders_channels';

  /**
   * The field name for the title sort field.
   */
  const string TITLE_SORT_FIELD = 'localgov_finders_title_sort';

  /**
   * {@inheritdoc}
   */
  public function getFieldName(string $field_constant): string {
    return constant(static::class . '::' . $field_constant);
  }

  /**
   * {@inheritdoc}
   */
  public function getChannelFieldDefinitions(ConfigEntityInterface $bundle): array {
    $field_definitions = [];

    $channel_types_field_definition = $this->getChannelTypesFieldDefinition($bundle);
    $field_definitions[$channel_types_field_definition->getName()] = $channel_types_field_definition;

    // TODO: further fields:
    // enabled facets
    // finder view.

    /** @var \Drupal\localgov_finders\Field\BundleFieldDefinition $field_definition */
    foreach ($field_definitions as $field_definition) {
      // Set the target bundle on all bundle fields.
      $field_definition->setTargetBundle($bundle->id());
    }

    return $field_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntryFieldDefinitions(ConfigEntityInterface $bundle): array {
    $field_definitions = [];

    $channels_selection_field_definition = $this->getChannelSelectionFieldDefinition($bundle);
    $field_definitions[$channels_selection_field_definition->getName()] = $channels_selection_field_definition;

    $title_sort_field = $this->getTitleSortFieldDefinition($bundle);
    $field_definitions[$title_sort_field->getName()] = $title_sort_field;

    // TODO: further fields:

    foreach ($field_definitions as $field_definition) {
      // Set the target bundle on all bundle fields.
      $field_definition->setTargetBundle($bundle->id());
    }

    return $field_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexIds(): array {
    return [
      'localgov_finders_index_default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getViewIds(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexDatasourceId(IndexInterface $index, string $entity_type_id) {
    return 'entity:' . $entity_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function alterSearchIndexForChannel(IndexInterface $index, ConfigEntityInterface $channel_bundle_entity): void {
    // Additional configuration to the default template could be done here.
    // Or use a preconfigured template in /config/template/search_api.index.[index_name].yml
  }

  /**
   * {@inheritdoc}
   */
  public function alterSearchIndexForEntry(IndexInterface $index, ConfigEntityInterface $entry_bundle_entity): void {
    $this->addEntryBundleToDatasource($index, $entry_bundle_entity);
    $this->alterIndexFields($index, $entry_bundle_entity);
  }

  /**
   * Adds an entry bundle to an index's datasource.
   *
   * @todo could this be replaced with a Search API plugin that looks for
   * enabled bundles. It would be of all entity types though. And we still add
   * fields so change the config, so it's maybe fine to keep doing here?
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index being updated.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entry_bundle_entity
   *   The bundle entity being added.
   *
   * @throws \Exception
   *   Throws an exception if the bundle can't be added to the index datasource.
   */
  protected function addEntryBundleToDatasource(IndexInterface $index, ConfigEntityInterface $entry_bundle_entity): void {
    $entry_entity_type_id = $entry_bundle_entity->getEntityType()->getBundleOf();
    $entry_bundle_id = $entry_bundle_entity->id();

    $datasource = $this->indexGetDatasource($index, $entry_entity_type_id);
    if (!$datasource) {
      throw new \Exception('Failed to update the directories search index with new bundle');
    }

    $configuration = $datasource->getConfiguration();
    $configuration['bundles']['default'] = FALSE;
    if (!in_array($entry_bundle_id, $configuration['bundles']['selected'])) {
      $configuration['bundles']['selected'][] = $entry_bundle_id;
    }
    $datasource->setConfiguration($configuration);
  }

  /**
   * Adds and updates fields on an index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index being updated.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entry_bundle_entity
   *   The bundle entity being added.
   */
  protected function alterIndexFields(IndexInterface $index, ConfigEntityInterface $entry_bundle_entity): void {
    // Get the entity type ID of the entry entities that the entry bundle entity
    // defines.
    $entry_entity_type_id = $entry_bundle_entity->getEntityType()->getBundleOf();
    $entry_bundle_id = $entry_bundle_entity->id();

    $datasource_id = $this->getIndexDatasourceId($index, $entry_entity_type_id);

    $label_field_name = $this->entityTypeManager->getDefinition($entry_entity_type_id)->getKey('label');
    if (!$index->getField($label_field_name)) {
      $title_field = new SearchIndexField($index, $label_field_name);
      $title_field->setDatasourceId($datasource_id);
      $title_field->setType('text');
      $title_field->setPropertyPath($label_field_name);
      $title_field->setBoost(5.0);
      $title_field->setLabel('Title');

      $index->addField($title_field);
    }

    if (!$index->getField(static::CHANNEL_SELECTION_FIELD)) {
      $channel_selection_field = new SearchIndexField($index, static::CHANNEL_SELECTION_FIELD);
      $channel_selection_field->setLabel('Directory channels');
      $channel_selection_field->setDatasourceId($datasource_id);
      $channel_selection_field->setPropertyPath(static::CHANNEL_SELECTION_FIELD);
      $channel_selection_field->setType('string');
      $channel_selection_field->setDependencies([
        'config' => [
          'field.storage.node.' . $entry_entity_type_id . '.' . static::CHANNEL_SELECTION_FIELD,
        ],
      ]);
      $index->addField($channel_selection_field);
    }

    if (!$index->getField(static::TITLE_SORT_FIELD)) {
      $sort_title_field = new SearchIndexField($index, static::TITLE_SORT_FIELD);
      $sort_title_field->setDatasourceId($datasource_id);
      $sort_title_field->setType('string');
      $sort_title_field->setPropertyPath(static::TITLE_SORT_FIELD);
      $sort_title_field->setLabel('Title (sort)');
      $sort_title_field->setDependencies([
        'config' => [
          'field.storage.' . $entry_entity_type_id . '.' . static::TITLE_SORT_FIELD,
        ],
      ]);

      $index->addField($sort_title_field);
    }

    $this->alterIndexRenderedItemField($index, $entry_bundle_entity, $datasource_id);
  }

  /**
   * Adds an entry bundle to the index's rendered item field.
   *
   * If the rendered item field does not yet exist on the index, it is created.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index being updated.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entry_bundle_entity
   *   The bundle entity being added.
   * @param string $datasource_id
   *   The ID of the datasource for the entry bundle.
   *
   * @see self::alterIndexFields()
   */
  protected function alterIndexRenderedItemField(IndexInterface $index, ConfigEntityInterface $entry_bundle_entity, string $datasource_id): void {
    $entry_bundle_id = $entry_bundle_entity->id();

    $rendered_item_field = $index->getField('rendered_item');
    if (!$rendered_item_field) {
      // There is no rendered item field yet on this index, so create it.
      // The rendered_item index field is independent of datasource, so should
      // not have a datasource set on it.
      $rendered_item_field = new SearchIndexField($index, 'rendered_item');
      $rendered_item_field->setType('text');
      $rendered_item_field->setPropertyPath('rendered_item');
      $rendered_item_field->setLabel('Rendered HTML output');

      $configuration = $rendered_item_field->getConfiguration();
      $configuration['roles'][AccountInterface::ANONYMOUS_ROLE] = AccountInterface::ANONYMOUS_ROLE;
      $configuration['view_mode'][$datasource_id][$entry_bundle_id] = 'directory_index';
      $rendered_item_field->setConfiguration($configuration);

      $index->addField($rendered_item_field);
    }
    else {
      // The rendered item field already exists on this index. Add the new entry
      // bumdle to the field's view mode configuration.
      $configuration = $rendered_item_field->getConfiguration();
      // TODO make the view mode a plugin constant.
      $configuration['view_mode'][$datasource_id][$entry_bundle_id] = 'directory_index';
      $rendered_item_field->setConfiguration($configuration);
    }
  }

  /**
   * Get index entity datasource or create it if it is not found.
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
    $datasource_id = $this->getIndexDatasourceId($index, $entity_type_id);

    if ($index->isValidDatasource($datasource_id)) {
      $datasource = $index->getDatasource($datasource_id);
    }
    else {
      $datasource = $this->pluginHelper->createDatasourcePlugin($index, $datasource_id);

      $index->addDatasource($datasource);
    }

    return $datasource;
  }

  /**
   * Gets the definition for the channel types field.
   *
   * This field on channels controls which entry bundles can be set as being in
   * the channel.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle
   *   The bundle entity.
   *
   * @return \Drupal\localgov_finders\Field\BundleFieldDefinition
   *   The bundle field definition.
   *
   * @see \Drupal\localgov_finders\Plugin\EntityReferenceSelection\EntryTypes
   */
  protected function getChannelTypesFieldDefinition(ConfigEntityInterface $bundle): BundleFieldDefinition {
    $bundle_entity_type = $bundle->getEntityType();
    // TODO: remove this assumption! channels and entries might not be the
    // same entity type!
    $content_entity_type_id = $bundle_entity_type->getBundleOf();

    return BundleFieldDefinition::create('entity_reference')
      ->setName(static::CHANNEL_TYPES_FIELD)
      ->setTargetEntityTypeId($content_entity_type_id)
      ->setLabel(t('Enabled Content types'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setCardinality(BundleFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSettings([
        'handler' => 'localgov_finders_entry_types',
        'target_type' => $bundle_entity_type->id(),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
      ]);
  }

  /**
   * Gets the definition for the channel selection field.
   *
   * This field on entries controls which channels an entry entity appears in.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle
   *   The bundle entity.
   *
   * @return \Drupal\localgov_finders\Field\BundleFieldDefinition
   *   The bundle field definition.
   */
  protected function getChannelSelectionFieldDefinition(ConfigEntityInterface $bundle): BundleFieldDefinition {
    $bundle_entity_type = $bundle->getEntityType();
    $content_entity_type_id = $bundle_entity_type->getBundleOf();

    return BundleFieldDefinition::create('entity_reference')
      ->setName(static::CHANNEL_SELECTION_FIELD)
      ->setTargetEntityTypeId($content_entity_type_id)
      ->setLabel(t('Directory channels'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setCardinality(BundleFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSettings([
        'handler' => 'localgov_finders_channels',
        'target_type' => $content_entity_type_id,
        'handler_settings' => [
          // We don't use this setting in our selection plugin, but the class it
          // inherits from does. Setting this to NULL means it skips its bundle
          // filtering which we don't need.
          'target_bundles' => NULL,
          'sort' => [
            'field' => 'title',
            'direction' => 'DESC',
          ],
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
      ]);
  }

  /**
   * Gets the definition for the title sort field.
   *
   * This field on entries allows an override of the entity label for sorting.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle
   *   The bundle entity.
   *
   * @return \Drupal\localgov_finders\Field\BundleFieldDefinition
   *   The bundle field definition.
   */
  protected function getTitleSortFieldDefinition(ConfigEntityInterface $bundle): BundleFieldDefinition {
    $bundle_entity_type = $bundle->getEntityType();
    $content_entity_type_id = $bundle_entity_type->getBundleOf();

    return BundleFieldDefinition::create('string')
      ->setName(static::TITLE_SORT_FIELD)
      ->setTargetEntityTypeId($content_entity_type_id)
      ->setLabel(t('Title used for sorting'))
      ->setDescription(t("<strong>Can be left blank</strong>. If this field is completed it will be used instead of the <em>Title</em> for alphabetically sorted lists. For example to move 'The' or 'A' from the beginning of a name."))
      ->setRequired(FALSE)
      ->setTranslatable(TRUE)
      ->setCardinality(1)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ]);
  }

}
