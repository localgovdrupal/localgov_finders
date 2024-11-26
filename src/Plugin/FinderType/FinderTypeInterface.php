<?php

namespace Drupal\finders\Plugin\FinderType;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\finders\Entity\FinderInterface;
use Drupal\search_api\IndexInterface;
use Drupal\views\ViewEntityInterface;

/**
 * Interface for Finder Type plugins.
 */
interface FinderTypeInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Gets the value of a constant on a finder type plugin.
   *
   * @param string $field_constant
   *   The name of a constant on the plugin class.
   *
   * @return string
   *   The value of the constant.
   */
  public function getFinderTypeConstant(string $field_constant): string;

  /**
   * Gets the bundle field definitions for a finder channel bundle.
   *
   * This should only be called from the matching method on the config manager.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The channel bundle entity.
   * @param \Drupal\finders\Entity\FinderInterface $finder
   *   The finder the channel bundle is in.
   *
   * @return \Drupal\finders\Field\BundleFieldDefinition[]
   *   An array of bundle field definitions, keyed by the field name. These may
   *   omit the target entity type and bundle as a convenience.
   */
  public function getChannelFieldDefinitions(ConfigEntityInterface $bundle, FinderInterface $finder): array;

  /**
   * Gets the bundle field definitions for a finder entry bundle.
   *
   * This should only be called from the matching method on the config manager.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The entry bundle entity.
   * @param \Drupal\finders\Entity\FinderInterface $finder
   *   The finder the entry bundle is in.
   *
   * @return \Drupal\finders\Field\BundleFieldDefinition[]
   *   An array of bundle field definitions, keyed by the field name. These may
   *   omit the target entity type and bundle as a convenience.
   */
  public function getEntryFieldDefinitions(ConfigEntityInterface $bundle, FinderInterface $finder): array;

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
   *   The search index being created or updated.
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
   * Alter the Search API index when a finder is configured.
   *
   * After it is configured, for anything unusual we've not thought of.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The updated index about to be saved.
   * @param \Drupal\finders\Entity\FinderInterface $finder
   *   The finder being configured.
   */
  public function alterSearchIndex(IndexInterface $index, FinderInterface $finder): void;

  /**
   * Alters the view for a search index when a finder is configured.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view about to be saved.
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index. It has already been updated for the channel bundle and
   *   has been saved.
   * @param \Drupal\finders\Entity\FinderInterface $finder
   *   The finder being configured.
   */
  public function alterView(ViewEntityInterface $view, IndexInterface $index, FinderInterface $finder): void;

}
