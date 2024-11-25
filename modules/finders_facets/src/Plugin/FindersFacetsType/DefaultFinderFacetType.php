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
class DefaultFinderFacetType extends FindersFacetsTypeBase {

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
