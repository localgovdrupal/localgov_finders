<?php

namespace Drupal\localgov_finders\Plugin\FinderType;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\localgov_finders\Constants\FinderField;
use Drupal\localgov_finders\Field\BundleFieldDefinition;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\Field as SearchIndexField;

/**
 * Base class for Finder Type plugins.
 */
abstract class FinderTypeBase extends PluginBase implements FinderTypeInterface {

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
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexFields(ConfigEntityInterface $bundle, IndexInterface $index): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function alterField(string $field_name, SearchIndexField $field_definition): void {
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndex(IndexInterface $index): void {
  }

  /**
   * {@inheritdoc}
   */
  public function indexAddBundle(IndexInterface $index, string $entity_type_id, string $entity_bundle): void {
    $datasource = $this->indexGetDatasource($index, $entity_type_id);
    if (!$datasource) {
      throw new \Exception('Failed to update the directories search index with new bundle');
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
