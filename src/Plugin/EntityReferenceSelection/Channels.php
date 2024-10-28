<?php

namespace Drupal\localgov_finders\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Attribute\EntityReferenceSelection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\localgov_finders\FinderTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Selection plugin for finder channels.
 */
#[EntityReferenceSelection(
  id: 'localgov_finders_channels',
  label: new TranslatableMarkup("Finder channels"),
  group: 'localgov_finders_channels',
  weight: 0,
  entity_types: [],
)]
class Channels extends DefaultSelection {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The finder type manager.
   *
   * @var \Drupal\localgov_finders\FinderTypeManager
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
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository'),
      $container->get('plugin.manager.localgov_finders_finder_type'),
    );
  }

  /**
   * Creates a Channels instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\localgov_finders\FinderTypeManager $finder_type_manager
   *   The finder type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler,
    AccountInterface $current_user,
    EntityFieldManagerInterface $entity_field_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityRepositoryInterface $entity_repository,
    FinderTypeManager $finder_type_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $module_handler, $current_user, $entity_field_manager, $entity_type_bundle_info, $entity_repository);
    $this->entityTypeManager = $entity_type_manager;
    $this->finderTypeManager = $finder_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = [
      'sort' => [
        'field' => '_none',
        'direction' => 'ASC',
      ],
    ] + parent::defaultConfiguration();
    unset($config['target_bundles']);
    unset($config['auto_create']);
    unset($config['auto_create_bundle']);
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    unset($form['target_bundles']);
    unset($form['auto_create']);
    unset($form['auto_create_bundle']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    // If the js didn't update the form.
    $form_state->unsetValue(['settings', 'handler_settings', 'target_bundles']);
    $form_state->unsetValue(['settings', 'handler_settings', 'auto_create']);
    $form_state->unsetValue([
      'settings',
      'handler_settings',
      'auto_create_bundle',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // Get the entity we are getting field values for.
    $host_entity = $this->configuration['entity'];

    $bundle_entity_type_id = $host_entity->getEntityType()->getBundleEntityType();
    $bundle_entity = $this->entityTypeManager->getStorage($bundle_entity_type_id)->load($host_entity->bundle());
    $finder_type = $this->finderTypeManager->getBundleFinderType($bundle_entity);
    $channel_bundles = $this->finderTypeManager->getChannelBundles($host_entity->getEntityType(), $finder_type);

    // Limit the query to bundles which are channels of the same finder type.
    $query->condition('type', array_keys($channel_bundles), 'IN');

    // Condition for channel types field, if it is set.
    $channel_types_field_name = $finder_type->getFieldName('CHANNEL_TYPES_FIELD');
    $or = $query->orConditionGroup();
    $or->notExists($channel_types_field_name);
    if ($this->configuration['entity']) {
      // The field can be instantiated without an entity.
      // The entity is not really part of the configuration.
      // Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface::getSelectionHandler
      // In practical situations this is used for forms etc. before the
      // configuration has been made, not when the field is on an entity type.
      // Really it would be nicer to be able to get to the bundle associated
      // with the configuration as there has to be one!
      $bundle = $this->configuration['entity']->bundle();
      $or->condition($channel_types_field_name, $bundle, 'IN');
    }
    $query->condition($or);

    return $query;
  }

}
