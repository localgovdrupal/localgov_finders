<?php

namespace Drupal\finders\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Validation\ConstraintValidatorFactory;
use Drupal\finders\Enum\FinderRole;
use Drupal\finders\FinderTypeManager;
use Drupal\views\ViewEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Validation;

/**
 * Provides the default form handler for the Finder entity.
 */
class FinderForm extends EntityForm {

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
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.finders_finder_type'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Creates a FinderFormDummy instance.
   *
   * @param \Drupal\finders\FinderTypeManager $finder_type_manager
   *   The finder type manager.
   */
  public function __construct(
    FinderTypeManager $finder_type_manager,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->finderTypeManager = $finder_type_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => "textfield",
      '#title' => $this->t("Name"),
      '#description' => $this->t("The human-readable name of this entity"),
      '#default_value' => $this->entity->get('label'),
      '#required' => "TRUE",
    ];
    $form['id'] = [
      '#type' => "machine_name",
      '#title' => $this->t("Name"),
      '#description' => $this->t("A unique machine-readable name for this entity. It must only contain lowercase letters, numbers, and underscores."),
      '#default_value' => $this->entity->id(),
      '#required' => "TRUE",
      '#machine_name' => [
        'exists' => ['Drupal\finders\Entity\Finder', 'load'],
        'source' => ['label'],
      ],
    ];

    $finder_type_definitions = $this->finderTypeManager->getDefinitions();

    // @todo Remove empty options when
    // https://www.drupal.org/project/drupal/issues/3194345 is fixed in core.
    $options = [
      '' => $this->t('None'),
    ];
    $options += array_map(fn($definition) => $definition['label'], $finder_type_definitions);

    $form['type'] = [
      '#type' => 'radios',
      '#title' => $this->t("Finder type"),
      '#options' => $options,
      '#required' => TRUE,
      '#empty_value' => '',
      '#default_value' => $this->entity->get('type'),
    ];

    foreach ($finder_type_definitions as $finder_type_id => $definition) {
      $form['type'][$finder_type_id]['#description'] = $definition['description'];
    }

    // These are required, but using #required will fail with disabling the
    // existing items. This should instead be enforced at the config validation
    // level.
    $form['channels'] = [
      '#type' => 'finders_entity_bundles',
      '#title' => $this->t('Channel bundles'),
      '#description' => $this->t("The bundles of the entities that will act as channels in this finder configuration."),
    ];
    $channels = $this->entity->get('channels');
    if (!empty($channels)) {
      $form['channels']['#default_value'] = [
        'entity_type_id' => array_key_first($channels),
        'bundles' => $channels[array_key_first($channels)],
      ];
    }

    $form['entries'] = [
      '#type' => 'finders_entity_bundles',
      '#title' => $this->t('Entry bundles'),
      '#description' => $this->t("The bundles of the entities that will act as entries in this finder configuration."),
    ];
    $entries = $this->entity->get('entries');
    if (!empty($entries)) {
      $form['entries']['#default_value'] = [
        'entity_type_id' => array_key_first($entries),
        'bundles' => $entries[array_key_first($entries)],
      ];
    }

    // Disable existing channels and entry bundles. We don't currently support
    // removing bundles from a finder.
    if (!$this->entity->isNew()) {
      $original = $this->entityTypeManager->getStorage('finder')->load($this->entity->id());

      $original_channels = $original->get('channels');
      $form['channels']['#disable_entity_type'] = TRUE;
      $form['channels']['#disabled_bundles'] = $original_channels[array_key_first($original_channels)];

      $original_entries = $original->get('entries');
      $form['entries']['#disable_entity_type'] = TRUE;
      $form['entries']['#disabled_bundles'] = $original_entries[array_key_first($original_entries)];
    }

    // Add some information on related config if the finder entity already
    // exists. We assume if the user can edit a finder, they can see these
    // config entities.
    if (!$this->entity->isNew()) {
      $form['info'] = [
        '#type' => 'details',
        '#title' => $this->t('Finder components'),
        '#open' => TRUE,
        '#weight' => 100,
      ];

      $finder_type_plugin = $this->entity->getFinderTypePlugin();

      $index_ids = $finder_type_plugin->getIndexIds();
      $indexes = $this->entityTypeManager->getStorage('search_api_index')->loadMultiple($index_ids);
      $index_items = [];
      foreach ($indexes as $index) {
        $view_ids = $finder_type_plugin->getViewIds($index);
        $views = $this->entityTypeManager->getStorage('view')->loadMultiple($view_ids);

        $index_items[] = [
          'index' => [
            '#type' => 'link',
            '#title' => 'index ' . $index->id() . ' index',
            '#url' => $index->toUrl(),
          ],
          'views' => [
            '#theme' => 'item_list',
            '#items' => array_map(
              fn (ViewEntityInterface $view) => [
                // Use a key so facets module can add a further nested list.
                'view' => [
                  '#type' => 'link',
                  '#title' => $view->label() . ' view',
                  '#url' => $view->toUrl(),
                ]
              ],
              $views
            ),
          ],
        ];
      }

      $form['info']['indexes'] = [
        '#theme' => 'item_list',
        '#items' => $index_items,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $entity = $this->buildEntity($form, $form_state);

    // Ensure at least one bundle is selected for channels and entries. We can't
    // use #required because on existing entity forms, the disabled checkboxes
    // don't appear to form validation as values.
    if ($entity->isNew()) {
      foreach (['channels', 'entries'] as $role_form_key) {
        $form_values = $form_state->getValue($role_form_key);

      // Form values may be empty during AJAX calls.
      if (!isset($form_values['container']['bundles'])) {
          continue;
        }
        $bundles = array_filter($form_values['container']['bundles']);
        if (empty($bundles)) {
          $form_state->setError($form[$role_form_key], $this->t('At least one @role bundle must be selected.', [
            '@role' => match (FinderRole::from($role_form_key)) {
              FinderRole::Channels => 'channel',
              FinderRole::Entries => 'entry',
            },
          ]));
        }
      }
    }

    // Go via the config schema validation to validate channels and entries.
    // @todo Remove this when core handles config entity validation.
    $validation_constraint = \Drupal::service('validation.constraint')->createInstance('FindersBundlesUniqueToFinder');
    $validator_factory = new ConstraintValidatorFactory(\Drupal::service('class_resolver'));
    $validator = $validator_factory->getInstance($validation_constraint);

    foreach (['channels', 'entries'] as $role_form_key) {
      $form_values = $form_state->getValue($role_form_key);

      // Form values may be empty during AJAX calls.
      if (!isset($form_values['container']['entity_type_id'])) {
        continue;
      }
      $entity_type_id = $form_values['container']['entity_type_id'];

      if (!isset($form_values['container']['bundles'])) {
        continue;
      }
      $bundles = array_filter($form_values['container']['bundles']);

      $violation_message_placeholders = $validator->doValidate($entity->id(), FinderRole::from($role_form_key), $entity_type_id, $bundles);
      if ($violation_message_placeholders) {
        $form_state->setError($form[$role_form_key], $this->t($validation_constraint->message, $violation_message_placeholders));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);

    foreach (['channels', 'entries'] as $role) {
      // @todo Figure out how to get rid of the 'container' nesting.
      $role_form_value = $form_state->getValue($role)['container'];

      if (isset($role_form_value['bundles'])) {
        $entity->set($role, [
          $role_form_value['entity_type_id'] => array_values(array_filter($role_form_value['bundles'])),
        ]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $saved = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    return $saved;
  }

}
