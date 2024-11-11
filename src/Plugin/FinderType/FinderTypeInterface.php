<?php

namespace Drupal\finders\Plugin\FinderType;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\Field as SearchIndexField;
use Drupal\views\ViewEntityInterface;

/**
 * Interface for Finder Type plugins.
 */
interface FinderTypeInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Gets the name of a bundle field this plugin defines.
   *
   * @param string $field_constant
   *   The name of a field name constant on the plugin class.
   *
   * @return string
   *   The name of the field.
   */
  public function getFieldName(string $field_constant): string;

  /**
   * Gets the bundle field definitions for a finder channel bundle.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The channel bundle entity.
   *
   * @return \Drupal\finders\Field\BundleFieldDefinition[]
   *   An array of bundle field definitions, keyed by the field name.
   */
  public function getChannelFieldDefinitions(ConfigEntityInterface $bundle): array;

  /**
   * Gets the bundle field definitions for a finder entry bundle.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The entry bundle entity.
   *
   * @return \Drupal\finders\Field\BundleFieldDefinition[]
   *   An array of bundle field definitions, keyed by the field name.
   */
  public function getEntryFieldDefinitions(ConfigEntityInterface $bundle): array;

  /**
   * Gets the Search API Index ID(s) for the finder.
   *
   * @return string[]
   *   An array of the ID names.
   */
  public function getIndexIds(): array;

  /**
   * Gets the View ID(s) that relate to a particular search index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index that has been set up for the finder.
   *
   * @return string[]
   *   An array of the view machine names.
   */
  public function getViewIds(IndexInterface $search_index): array;

  /**
   * Gets the search datasource plugin ID for the given index and entity type.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index being altered.
   * @param string $entity_type_id
   *   The entity type ID that the datasource is for.
   *
   * @return string
   *   The plugin ID of the datasource to use.
   *
   * @see \Drupal\search_api\Datasource\DatasourceInterface
   */
  public function getIndexDatasourceId(IndexInterface $index, string $entity_type_id): string;

  /**
   * Alter the Search API index when a channel bundle is configured.
   *
   * After it is configured, for anything unusual we've not thought of.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The updated index about to be saved.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $channel_bundle_entity
   *   The channel bundle.
   */
  public function alterSearchIndexForChannel(IndexInterface $index, ConfigEntityInterface $channel_bundle_entity): void;

  /**
   * Alters the view for a search index when a channel bundle is configured.
   *
   * @param ViewEntityInterface $view
   *   The view about to be saved.
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index. It has already been updated for the channel bundle and
   *   has been saved.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $channel_bundle_entity
   *   The channel bundle.
   */
  public function alterViewForChannel(ViewEntityInterface $view, IndexInterface $index, ConfigEntityInterface $channel_bundle_entity): void;

  /**
   * Alter the Search API index when an entry bundle is configured.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The updated index about to be saved.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entry_bundle_entity
   *   The entry bundle.
   */
  public function alterSearchIndexForEntry(IndexInterface $index, ConfigEntityInterface $entry_bundle_entity): void;

  /**
   * Alters the view for a search index when an entry bundle is configured.
   *
   * @param ViewEntityInterface $view
   *   The view about to be saved.
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index. It has already been updated for the entry bundle and
   *   has been saved.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entry_bundle_entity
   *   The entry bundle.
   */
  public function alterViewForEntry(ViewEntityInterface $view, IndexInterface $index, ConfigEntityInterface $entry_bundle_entity): void;

}
