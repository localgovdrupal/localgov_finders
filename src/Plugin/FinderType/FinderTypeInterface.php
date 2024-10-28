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
   * @return array
   *   An array of field definitions, keyed by the field name.
   */
  public function getChannelFieldDefinitions(ConfigEntityInterface $bundle): array;

  /**
   * Gets the bundle field definitions for a finder entry bundle.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The bundle entity.
   *
   * @return array
   *   An array of field definitions, keyed by the field name.
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
   * Get Search API index fields.
   *
   * @todo As this has to get the index passed into it I wonder if it should
   *   just be the alter index method and the plugins do their thing?
   *   Or maybe passing a array of expected settings, and the the configuration
   *   manager goes through and applies them if different - seems much more
   *   work for little additional value.
   *   Bonus of this way is it is more structured at the moment.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   Entity bundle.
   *
   * @return \Drupal\search_api\Item\Field[]
   *   An array of field definitions, keyed by the field name.
   */
  public function getIndexFields(ConfigEntityInterface $bundle, IndexInterface $index): array;

  /**
   * Alter Search API index fields.
   *
   * @param string $field_name
   *   Field name.
   * @param \Drupal\search_api\Index\Field $field_definition
   *   Search API field definition.
   */
  public function alterField(string $field_name, SearchIndexField $field_definition): void;

  /**
   * Alter the Search API index.
   *
   * After it is configured, for anything unusual we've not thought of.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The updated index about to be saved.
   */
  public function alterIndex(IndexInterface $index): void;

  /**
   * Adds an entity bundle to an index datasource.
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
   *
   * @throws \Exception
   *   Throws an exception if the bundle can't be added to the index datasource.
   */
  public function indexAddBundle(IndexInterface $index, string $entity_type_id, string $entity_bundle): void;

}
