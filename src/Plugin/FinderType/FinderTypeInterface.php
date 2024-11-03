<?php

namespace Drupal\localgov_finders\Plugin\FinderType;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\Field as SearchIndexField;

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
   *   Entity bundle.
   *
   * @return \Drupal\localgov_finders\Field\BundleFieldDefinition[]
   *   An array of bundle field definitions, keyed by the field name.
   */
  public function getChannelFieldDefinitions(ConfigEntityInterface $bundle): array;

  /**
   * Gets the bundle field definitions for a finder entry bundle.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The bundle entity.
   *
   * @return \Drupal\localgov_finders\Field\BundleFieldDefinition[]
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
   * Gets the View ID(s) for the finder.
   *
   * @return string[]
   *   An array of the view machine names.
   */
  public function getViewIds(): array;

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
   * Alter the Search API index when an entry bundle is configured.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The updated index about to be saved.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entry_bundle_entity
   *   The entry bundle.
   */
  public function alterSearchIndexForEntry(IndexInterface $index, ConfigEntityInterface $entry_bundle_entity): void;

}
