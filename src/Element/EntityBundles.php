<?php

namespace Drupal\finders\Element;

use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElementBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Form element for selecting bundles of an entity type.
 *
 * @FormElement("finders_entity_bundles")
 */
class EntityBundles extends FormElementBase implements ContainerFactoryPluginInterface {

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
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Creates a FindersEntityBundle instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;

    return [
      '#input' => TRUE,
      '#field_types' => [],
      '#process' => [
        [$class, 'processEntityBundles'],
      ],
      '#options_element_type' => 'select',
    ];
  }

  /**
   * Process callback.
   */
  public static function processEntityBundles(&$element, FormStateInterface $form_state, &$complete_form) {
    $entity_type_manager = \Drupal::service('entity_type.manager');

    $element['#tree'] = TRUE;

    $container_html_id = HtmlUtility::getUniqueId('finders_entity_bundles');
    $element['container'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $element['#title'],
      '#description' => $element['#description'] ?? '',
      '#attributes' => ['id' => $container_html_id],
    ];

    // Try to get a default value for the entity_type_id element.
    $entity_type_id_parents = $element['#parents'];
    $entity_type_id_parents[] = 'entity_type_id';

    $default_entity_type_id = $element['#default_value']['entity_type_id'] ?? '';
    $selected_entity_type_id = $form_state->getValue([...$element['#parents'], 'container', 'entity_type_id']) ?? $default_entity_type_id;

    $entity_type_options = [];
    foreach ($entity_type_manager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->getGroup() != 'content' || empty($entity_type->getBundleEntityType())) {
        // Only work with content entity types which have a bundle entity type.
        continue;
      }

      $entity_type_options[$entity_type_id] = $entity_type->getLabel();
    }
    natcasesort($entity_type_options);

    $element['container']['entity_type_id'] = [
      '#type' => $element['#options_element_type'],
      '#title' => t('Entity type'),
      '#options' => $entity_type_options,
      '#empty_value' => '',
      '#required' => $element['#required'],
      '#default_value' => $selected_entity_type_id,
      '#ajax' => [
        'callback' => static::class . '::entityTypeDropdownCallback',
        'wrapper' => $container_html_id,
        'options' => [
          // Pass the array parents to the AJAX callback in a query parameter,
          // so that it can determine where in the form our element is located.
          'query' => [
            'element_parents' => implode('/', $element['#array_parents']),
          ],
        ],
      ],
    ];

    // Lock the entity type if it is set already -- finders do not yet support
    // removal of channel or entry types.
    if ($default_entity_type_id) {
      $element['container']['entity_type_id']['#disabled'] = TRUE;
    }

    // Non-JS support: button to choose the entity type.
    $array_parents = array_merge($element['#array_parents'], ['container', 'entity_type_id']);
    $element['container']['choose_entity_type_id'] = [
      '#type' => 'submit',
      '#value' => t('Choose entity type'),
      '#attributes' => ['class' => ['js-hide', 'ajax-example-inline']],
      '#limit_validation_errors' => [
        $array_parents,
      ],
      '#validate' => [],
      '#submit' => [static::class . '::entityTypeSubmit'],
    ];

    if ($selected_entity_type_id) {
      $selected_entity_type = $entity_type_manager->getDefinition($selected_entity_type_id);
      $bundle_entity_type_id = $selected_entity_type->getBundleEntityType();
      $bundle_entity_type = $entity_type_manager->getDefinition($selected_entity_type->getBundleEntityType());

      $bundle_options = [];
      foreach ($entity_type_manager->getStorage($bundle_entity_type_id)->loadMultiple() as $bundle_entity) {
        // TODO: label translation.
        $bundle_options[$bundle_entity->id()] = $bundle_entity->label();
      }

      natcasesort($bundle_options);

      $element['container']['bundles'] = [
        '#type' => 'checkboxes',
        '#title' => $selected_entity_type->getBundleLabel(),
        '#options' => $bundle_options,
        '#empty_value' => '',
        '#default_value' => $element['#default_value']['bundles'] ?? NULL,
        '#required' => $element['#required'],
      ];

      // Lock the bundles that are already set.
      if ($default_entity_type_id) {
        foreach ($element['container']['bundles']['#default_value'] as $bundle) {
          $element['container']['bundles'][$bundle]['#disabled'] = TRUE;
        }
      }

      if (empty($bundle_options)) {
        $element['container']['bundles']['#description'] = t('No bundles on the @entity-type entity type. Please select another.', [
          '@entity-type' => $entity_type_options[$selected_entity_type_id],
        ]);
      }
    }

    return $element;
  }

  /**
   * AJAX callback for the entity type ID select element.
   */
  public static function entityTypeDropdownCallback(&$form, FormStateInterface &$form_state, Request $request) {
    $form_parents = explode('/', $request->query->get('element_parents'));

    // Sanitize form parents before using them.
    $form_parents = array_filter($form_parents, [Element::class, 'child']);

    // Retrieve the element to be rendered.
    $form = NestedArray::getValue($form, $form_parents);

    return $form;
  }

}
