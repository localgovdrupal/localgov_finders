<?php

namespace Drupal\finders\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\finders\FinderTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the default form handler for the Finder entity.
 */
class FinderForm extends EntityForm {

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
  ) {
    $this->finderTypeManager = $finder_type_manager;
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
      '#empty_value' => '',
      '#default_value' => $this->entity->get('type'),
    ];

    foreach ($finder_type_definitions as $finder_type_id => $definition) {
      $form['type'][$finder_type_id]['#description'] = $definition['description'];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
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
  public function save(array $form, FormStateInterface $form_state) {
    $saved = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    return $saved;
  }

}
