<?php

namespace Drupal\finders_facets\Plugin\FindersFacetsType;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\FacetInterface;
use Drupal\finders\Entity\FinderInterface;
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
   * Ensure additional facets for an index and view.
   *
   * @param \Drupal\finders\Entity\FinderInterface $finder
   *   The finder being configured.
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index to add facets for.
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view on the given search index to add facets for.
   */
  protected function ensureViewFacets(FinderInterface $finder, IndexInterface $index, ViewEntityInterface $view): void {
    // Do nothing in this plugin.
  }

}
