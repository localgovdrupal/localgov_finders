<?php

namespace Drupal\finders_facets\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\finders_facets\Entity\LocalgovDirectoriesFacetsType;
use Drupal\finders_facets\Hook\FindersFacetsHooks;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Display available facet options by selected channel.
 *
 * Grouping by entity reference by bundle would also be solved by
 * https://www.drupal.org/project/drupal/issues/2269823
 *
 * This is updated when the channel field widget's value changes.
 *
 * @see \Drupal\finders_facets\Hook\FindersFacetsWidgetAlterHooks
 */
#[FieldWidget(
  id: 'finders_facets_checkbox',
  label: new TranslatableMarkup('Finders facets checkboxes'),
  field_types: [
    'entity_reference',
  ],
  multiple_values: TRUE,
  weight: -10,
)]
class Facets extends OptionsWidgetBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Creates a Facets instance.
   *
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The settings.
   * @param array $third_party_settings
   *   The third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $options = $this->getOptions($items->getEntity());
    // dsm($options);
    // Trying to imagine the best way round this.
    //
    // EntityReferenceItem::getSettableOptions() called by ::getOptions()
    // removes the bundle from the array if there is only one in the available
    // results. At the moment in our case that would be if there is only one
    // bundle, or if there are more, but one has accesible values.
    //
    // I'm lacking imagination at the moment. So this ugly blunt instrument puts
    // it back for now.
    $raw_options = \Drupal::service('plugin.manager.entity_reference_selection')->getSelectionHandler($this->fieldDefinition, $items->getEntity())->getReferenceableEntities();
    if (count($raw_options) == 1) {
      $target_type = $this->fieldDefinition->getSetting('target_type');
      $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($target_type);
      $bundle = key($raw_options);
      $bundle_label = (string) $bundles[$bundle]['label'];
      $options = [
        $bundle_label => $options,
      ];
    }

    $host_entity = $items->getEntity();
    $bundle_entity_type_id = $host_entity->getEntityType()->getBundleEntityType();
    $bundle_entity = $this->entityTypeManager->getStorage($bundle_entity_type_id)->load($host_entity->bundle());
    $finder = $this->entityTypeManager->getStorage('finder')->getFinderForBundleEntity($bundle_entity);

    /** @var \Drupal\finders\Plugin\FinderType\FinderTypeInterface $finder_type_plugin */
    $finder_type_plugin = $finder->getFinderTypePlugin();

    $channel_field_name = $finder_type_plugin->getFieldName('CHANNEL_SELECTION_FIELD');
    $facet_types_enabled_field_name = FindersFacetsHooks::FACET_ENABLE_FIELD;

    // Get the enabled facet types from the host entity's channels.
    $enabled = [];
    if ($user_input = $form_state->getValue($channel_field_name)) {
      $entity_ids = array_column($user_input, 'target_id');

      // If there is user input in the form, use the selected channels.
      $channel_entity_type_id = $finder->getChannelEntityTypeId();
      $channel_entities = $this->entityTypeManager->getStorage($channel_entity_type_id)->loadMultiple($entity_ids);

      foreach ($channel_entities as $channel_entity) {
        foreach ($channel_entity->get($facet_types_enabled_field_name)->referencedEntities() as $facet_type) {
          $enabled[$facet_type->label()] = $facet_type->label();
        }
      }
    }
    else {
      // If there is no user form input, get the channels referenced by the
      // host entity.
      foreach ($host_entity->get($channel_field_name)->referencedEntities() as $channel_entity) {
        foreach ($channel_entity->get($facet_types_enabled_field_name)->referencedEntities() as $facet_type) {
          $enabled[$facet_type->label()] = $facet_type->label();
        }
      }
    }

    // And only allow bundles associated with the channels.
    // The $options array has outer keys which are the bundle *labels*, not IDs.
    $options = array_intersect_key($options, $enabled);

    // Set selected from any existing values.
    $selected = $this->getSelectedOptions($items);
    // If there is only one option and it's required default it.
    if ($this->required && count($options) == 1) {
      $single_bundle_options = reset($options);
      if (count($single_bundle_options) == 1) {
        $selected = [key($single_bundle_options)];
      }
    }

    $element += [
      '#type' => 'fieldset',
    ];

    if (empty($options)) {
      $element['#description'] = $this->t('Select finder channels to add facets');
    }
    foreach ($options as $bundle_label => $bundle_options) {
      $element[$bundle_label] = [
        '#title' => $bundle_label,
        '#type' => 'checkboxes',
        '#default_value' => $selected,
        '#options' => $bundle_options,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function supportsGroups() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    // Flatten the array again.
    $values = $form_state->getValue($element['#field_name']);
    if ($values) {
      $element['#value'] = [];
      foreach ($values as $options) {
        foreach ($options as $key => $value) {
          if ($value) {
            $element['#value'][$key] = $value;
          }
        }
      }
    }
    // None option.
    if (empty($element['#value'])) {
      $element['#value'] = '_none';
    }

    parent::validateElement($element, $form_state);
  }

}
