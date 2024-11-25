<?php

namespace Drupal\finders_facets\Hook;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\finders\Entity\FinderInterface;
use Drupal\finders\Field\BundleFieldDefinition;
use Drupal\finders_facets\FindersFacetsTypeManager;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\Field as SearchIndexField;
use Drupal\views\ViewEntityInterface;

/**
 * Contains Finders hook implementations for the Finders facets module.
 */
class FindersHooks {

  /**
   * Name of the field on channel entities for enabling facet types.
   */
  public const FACET_ENABLE_FIELD = 'finders_facets_enable';

  /**
   * Name of the field on entry entities for selecting facets.
   */
  public const FACET_SELECTION_FIELD = 'finders_facets_select';

  /**
   * Name of the search index field for filtering facets.
   */
  public const FACET_INDEXING_FIELD = 'finders_facets_filter';

  /**
   * Creates a FinderConfigManager instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list service.
   * @param \Drupal\finders_facets\FindersFacetsTypeManager $findersFacetTypeManager
   *   The finders facets type manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ModuleExtensionList $moduleExtensionList,
    protected FindersFacetsTypeManager $findersFacetTypeManager,
  ) {
  }

  /**
   * Implements hook_finders_channel_fields_alter().
   */
  #[Hook('finders_channel_fields_alter')]
  public function findersChannelFieldsAlter(array &$channel_field_definitions, ConfigEntityInterface $bundle_entity, FinderInterface $finder) {
    if (!$this->isFinderEnabledWithFacets($finder)) {
      return;
    }

    // Add the enabled facets field to a channel bundle.
    $channel_field_definitions[static::FACET_ENABLE_FIELD] = BundleFieldDefinition::create('entity_reference')
      ->setName(static::FACET_ENABLE_FIELD)
      ->setLabel(t('Enabled Facets'))
      ->setDescription(t('Which facets are enabled to be shown on this directory channel, and will be added when editing content to be added to this directory.'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setCardinality(BundleFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSettings([
        'target_type' => 'finders_facet_type',
        'handler' => 'default',
        'handler_settings' => [
          'target_bundles' => NULL,
          'auto_create' => FALSE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
      ]);
  }

  /**
   * Implements hook_finders_entry_fields_alter().
   */
  #[Hook('finders_entry_fields_alter')]
  public function findersEntryFieldsAlter(array &$entry_field_definitions, ConfigEntityInterface $bundle_entity, FinderInterface $finder) {
    if (!$this->isFinderEnabledWithFacets($finder)) {
      return;
    }

    // Add the selected facets field to a channel bundle.
    $entry_field_definitions[static::FACET_SELECTION_FIELD] = BundleFieldDefinition::create('entity_reference')
      ->setName(static::FACET_SELECTION_FIELD)
      ->setLabel(t('Facets'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setCardinality(BundleFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSettings([
        'target_type' => 'finders_facet',
        'handler' => 'finders_facets_facets',
        'handler_settings' => [
          'target_bundles' => NULL, // REMOVE!
          'auto_create' => FALSE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'finders_facets_checkbox',
      ]);
  }

  /**
   * Implements hook_finders_index_alter().
   */
  #[Hook('finders_index_alter')]
  public function findersIndexAlter(IndexInterface $index, FinderInterface $finder): void {
    if (!$this->isFinderEnabledWithFacets($finder)) {
      return;
    }

    if ($index->getField(static::FACET_INDEXING_FIELD)) {
      return;
    }

    $entry_entity_type_id = $finder->getEntryEntityTypeId();
    $datasource_id = $finder->getFinderTypePlugin()->getIndexDatasourceId($index, $entry_entity_type_id);

    $field = new SearchIndexField($index, static::FACET_INDEXING_FIELD);
    $field->setLabel('Facets');
    $field->setDataSourceId($datasource_id);
    $field->setPropertyPath(static::FACET_SELECTION_FIELD);
    $field->setType('integer');
    $field->setDependencies([
      'config' => [
        'field.storage.' . $entry_entity_type_id . '.' . static::FACET_SELECTION_FIELD,
      ],
    ]);
    $index->addField($field);
  }

  /**
   * Implements hook_finders_view_alter().
   */
  #[Hook('finders_view_alter')]
  public function findersViewAlter(ViewEntityInterface $view, IndexInterface $index, FinderInterface $finder): void {
    if (!$this->isFinderEnabledWithFacets($finder)) {
      return;
    }
  }

  /**
   * Implements hook_finders_post_configure().
   */
  #[Hook('finders_post_configure')]
  public function findersPostConfigure(FinderInterface $finder) {
    if (!$this->isFinderEnabledWithFacets($finder)) {
      return;
    }

    $finder_type = $finder->getFinderTypePlugin();
    $finder_type_id = $finder_type->getPluginId();

    // Get the finder facets type plugin which matches the finder type, if one
    // exists. Otherwise, fall back to the special '_default' plugin.
    if ($this->findersFacetTypeManager->hasDefinition($finder_type_id)) {
      $finder_facets_type_plugin = $this->findersFacetTypeManager->createInstance($finder_type_id);
    }
    else {
      $finder_facets_type_plugin = $this->findersFacetTypeManager->createInstance($this->findersFacetTypeManager::DEFAULT_PLUGIN_ID);
    }

    $finder_facets_type_plugin->findersPostConfigure($finder);
  }

  /**
   * Determines whether a finder is configured to use facets.
   *
   * @param \Drupal\finders\Entity\FinderInterface $finder
   *   The finder entity.
   *
   * @return bool
   *   TRUE if the finder is configured to use facets, FALSE if not.
   */
  protected function isFinderEnabledWithFacets(FinderInterface $finder): bool {
    return ($finder->getThirdPartySetting('finders_facets', 'facets', FALSE) == TRUE);
  }

}
