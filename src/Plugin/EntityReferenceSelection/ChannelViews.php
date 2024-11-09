<?php

namespace Drupal\finders\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Attribute\EntityReferenceSelection;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\finders\FinderTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Selection plugin for  channel views fields on a channel entity.
 *
 * This limits the referenceable views to those whose base table is a SearchAPI
 * index for the same finder type as the host entity.
 */
#[EntityReferenceSelection(
  id: 'finders_channel_views',
  label: new TranslatableMarkup("Channel Views"),
  group: 'finders_channel_views',
  weight: 0,
  entity_types: [
    'view',
  ],
)]
class ChannelViews extends SelectionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The finder type manager.
   *
   * @var \Drupal\finders\FinderTypeManager
   */
  protected $finderTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.finders_finder_type'),
    );
  }

  /**
   * Creates a ChannelViews instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\finders\FinderTypeManager $finder_type_manager
   *   The finder type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    FinderTypeManager $finder_type_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->finderTypeManager = $finder_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    // Get the entity we are getting field values for.
    $host_entity = $this->configuration['entity'];
    $host_entity_bundle_entity_type_id = $host_entity->getEntityType()->getBundleEntityType();
    $host_entity_bundle_entity = $this->entityTypeManager->getStorage($host_entity_bundle_entity_type_id)->load($host_entity->bundle());

    $finder_type_plugin = $this->finderTypeManager->getBundleFinderType($host_entity_bundle_entity);

    if (empty($finder_type_plugin)) {
      \Drupal::messenger()->addError('This entity selection plugin must be used on fields which are on a finder channel bundle.');
      return [];
    }

    $index_ids = $finder_type_plugin->getIndexIds();

    // Derive SearchAPI's Views data base tables for the finder type's search
    // indexes.
    // @see search_api_views_data()
    $views_base_tables = array_map(fn ($index_id) => 'search_api_index_' . $index_id, $index_ids);

    // Filter to the views which are one of the index base tables for the
    // host entity's finder type.
    $views = $this->entityTypeManager->getStorage('view')->loadMultiple();
    $views = array_filter($views, fn ($view) => in_array($view->get('base_table'), $views_base_tables));

    $options = [];
    foreach ($views as $view_id => $view) {
      $options[$view_id] = $view->label();
    }
    natcasesort($options);

    $options = ['view' => $options];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function countReferenceableEntities($match = NULL, $match_operator = 'CONTAINS') {
    $options = $this->getReferenceableEntities($match, $match_operator);
    return count($options);
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
    return $ids;
  }

}
