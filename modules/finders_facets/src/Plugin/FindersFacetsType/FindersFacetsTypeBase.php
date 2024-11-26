<?php

namespace Drupal\finders_facets\Plugin\FindersFacetsType;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\FileStorage as ConfigFileStorage;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\FacetInterface;
use Drupal\finders\Entity\FinderInterface;
use Drupal\finders_facets\Hook\FindersHooks;
use Drupal\search_api\IndexInterface;
use Drupal\views\ViewEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Finders Facets Type plugins.
 */
abstract class FindersFacetsTypeBase extends PluginBase implements FindersFacetsTypeInterface, ContainerFactoryPluginInterface {

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
        $content_facet = $facet_storage->load($facet_id);
        if (!$content_facet) {
          $content_facet = $this->loadViewContentFacetsFacet($facet_id, $facet_template_config_values, $finder, $index, $view);

          // Allow plugins to omit the content facet for a view.
          if ($content_facet) {
            $content_facet->save();
          }
        }

        if ($content_facet) {
          $this->ensureFacetBlock($content_facet, $finder, $index, $view);
        }

        // Allow plugins to add further facets.
        $extra_facets = $this->ensureViewFacets($finder, $index, $view);

        foreach ($extra_facets as $facet) {
          $this->ensureFacetBlock($facet, $finder, $index, $view);
        }
      }
    }
  }

  /**
   * Creates a content facet for the given index and view.
   *
   * @param string $facet_id
   *   The ID of the facet to create.
   * @param array $facet_template_config_values
   *   The template values.
   * @param \Drupal\finders\Entity\FinderInterface $finder
   *   The finder being configured.
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index to add facets for.
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view on the given search index to add facets for.
   *
   * @return \Drupal\facets\FacetInterface|null
   *   The facet config entity, or NULL to not create a facet for this view and
   *   index. It is the responsibility of the caller to save this.
   */
  protected function loadViewContentFacetsFacet(string $facet_id, array $facet_template_config_values, FinderInterface $finder, IndexInterface $index, ViewEntityInterface $view): ?FacetInterface {
    $finder_type = $finder->getFinderTypePlugin();
    $facet_storage = $this->entityTypeManager->getStorage('facets_facet');

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

    $facet = $facet_storage->create($facet_values);
    return $facet;
  }

  /**
   * Ensure additional facets for an index and view.
   *
   * @param \Drupal\finders\Entity\FinderInterface $finder
   *   The finder being configured.
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index to add facets for.
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view on the given search index to add facets for.
   *
   * @return \Drupal\facets\FacetInterface[]
   *   An array of facets that blocks should be created for. Implementations of
   *   this method may choose not to return a facet they have created.
   */
  protected function ensureViewFacets(FinderInterface $finder, IndexInterface $index, ViewEntityInterface $view): array {
    // Do nothing in the base class.
    return [];
  }

  /**
   * Ensures a block exists for the given facet.
   *
   * This is based on the config template file, and is configured to show on
   * all channel bundles of the finder.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet to create a block for.
   * @param \Drupal\finders\Entity\FinderInterface $finder
   *   The finder being configured.
   * @param \Drupal\search_api\IndexInterface $index
   *   The index the facet is for.
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view the facet is on.
   */
  protected function ensureFacetBlock(FacetInterface $facet, FinderInterface $finder, IndexInterface $index, ViewEntityInterface $view): void {
    $finder_type = $finder->getFinderTypePlugin();
    $block_storage = $this->entityTypeManager->getStorage('block');

    // Get the config template for a block.
    $template_directory = $this->moduleExtensionList->getPath('finders_facets') . '/config/template';
    $config_source = new ConfigFileStorage($template_directory);
    $config_filename = 'block.block.finders_facets_template';
    $block_template_config_values = $config_source->read($config_filename);

    $block_id = 'finders_facets_' . $facet->id();

    // Don't do anything if the block already exists.
    // @todo Update the block to show on all channels.
    if ($block = $block_storage->load($block_id)) {
      return;
    }

    $block_values = $block_template_config_values;

    $block_values['settings']['label'] = 'Finders facet - ' . $finder_type->getPluginDefinition()['label'] . ' - ' . $view->id();

    // Get the main theme (rather than the active theme, which will likely be
    // the admin theme).
    $config = \Drupal::config('system.theme');
    $theme = $config->get('default');

    // Figure out a region, because core still doesn't provide a way to get a
    // sidebar region.
    $regions = system_region_list($theme);
    if (isset($regions['sidebar_first'])) {
      $region = 'sidebar_first';
    }
    elseif ($matches = preg_grep('/^sidebar/', array_keys($regions))) {
      $region = reset($matches);
    }
    else {
      $region = system_default_region($theme);
    }

    $replacements = [
      'FACET_ID' => $facet->id(),
      'THEME_ID' => $theme,
      'BLOCK_ID' => $block_id,
      'REGION' => $region,
    ];

    array_walk_recursive($block_values, function (&$config_value) use ($replacements) {
      if (is_string($config_value)) {
        $config_value = str_replace(array_keys($replacements), array_values($replacements), $config_value);
      }
    });

    // Show the block on channel bundles for the finder.
    $finder_channel_entity_type_id = $finder->getChannelEntityTypeId();
    $finder_channel_bundles = $finder->getChannelBundleIds();
    $block_values['visibility']['entity_bundle:' . $finder_channel_entity_type_id] = [
      'id' => 'entity_bundle:' . $finder_channel_entity_type_id,
      'bundles' => array_combine($finder_channel_bundles, $finder_channel_bundles),
      'context_mapping' => [
        $finder_channel_entity_type_id => "@{$finder_channel_entity_type_id}.{$finder_channel_entity_type_id}_route_context:{$finder_channel_entity_type_id}",
      ],
    ];

    $block = $block_storage->create($block_values);
    $block->save();
  }

}
