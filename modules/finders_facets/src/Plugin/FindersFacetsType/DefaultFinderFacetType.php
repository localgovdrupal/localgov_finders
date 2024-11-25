<?php

namespace Drupal\finders_facets\Plugin\FindersFacetsType;

use Drupal\Core\Config\FileStorage as ConfigFileStorage;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\finders\Entity\FinderInterface;
use Drupal\finders_facets\Attribute\FindersFacetsType;
use Drupal\finders_facets\Hook\FindersHooks;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default plugin for finder facets types.
 *
 * This is used if there is no finders facet plugin whose ID matches a finder
 * entity's finder type plugin.
 */
#[FindersFacetsType(
  id: '_default',
)]
class DefaultFinderFacetType extends FindersFacetsTypeBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleExtensionList;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('extension.list.module'),
    );
  }

  /**
   * Creates a Default instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module handler.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ModuleExtensionList $module_extension_list,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public function findersPostConfigure(FinderInterface $finder): void {
    $finder_type = $finder->getFinderTypePlugin();

    // Get the config template for a facet.
    $template_directory = $this->moduleExtensionList->getPath('finders_facets') . '/config/template';
    $config_source = new ConfigFileStorage($template_directory);
    $config_filename = 'facets.facet.finders_facets_template';
    $facet_template_config_values = $config_source->read($config_filename);

    $facet_storage = $this->entityTypeManager->getStorage('facets_facet');

    // Ensure a facet for each view.
    $indexes = $this->entityTypeManager->getStorage('search_api_index')->loadMultiple($finder_type->getIndexIds());
    foreach ($indexes as $index) {
      $view_ids = $finder_type->getViewIds($index);
      $views = $this->entityTypeManager->getStorage('view')->loadMultiple($view_ids);

      foreach ($views as $view) {
        // No need to prefix with 'finders'; the index ID should already have
        // that.
        $facet_id = $index->id() . '_' . $view->id();

        // Do not overwrite an existing facet.
        $facet = $facet_storage->load($facet_id);
        if ($facet) {
          continue;
        }

        // Copy the template and replace values.
        $facet_values = $facet_template_config_values;

        $facet_values['id'] = $facet_id;
        $facet_values['name'] = 'Finders - ' . $finder_type->getPluginDefinition()['label'] . ' - ' . $view->id();

        $facet_values['dependencies']['config'] = [
          $index->getConfigDependencyName(),
          $view->getConfigDependencyName(),
        ];

        $facet_values['facet_source_id'] = 'search_api:views_embed__' . $view->id() . '__channel_embed';
        $facet_values['field_identifier'] = FindersHooks::FACET_INDEXING_FIELD;

        // Save the facet.
        $facet = $facet_storage->create($facet_values);
        $facet->save();

        $this->ensureViewFacets($finder, $index, $view);
      }
    }

    // TODO: further config:
    // facet block

  }

}
