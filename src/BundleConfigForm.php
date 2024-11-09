<?php

declare(strict_types=1);

namespace Drupal\finders;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Form section added to configure Finder on bundle.
 */
class BundleConfigForm implements BundleConfigFormInterface {

  use StringTranslationTrait;

  /**
   * Creates a BundleConfigForm instance.
   *
   * @param \Drupal\finders\FinderTypeManager $finderTypeManager
   *   The finder type manager.
   */
  public function __construct(protected FinderTypeManager $finderTypeManager) {
  }

  /**
   * {@inheritdoc}
   */
  public function alterBundleForm(&$form, FormStateInterface $form_state): void {
    $bundle_type = $form_state->getFormObject()->getEntity();
    assert($bundle_type instanceof ConfigEntityInterface);
    $form += $this->getFormElements($bundle_type);
    foreach (Element::children($form['actions']) as $action) {
      $form['actions'][$action]['#validate'][] = static::class . '::validate';
      $form['actions'][$action]['#submit'][] = static::class . '::submit';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormElements(ConfigEntityInterface $bundle_type): array {
    $form = [];

    $form['finders'] = [
      '#type' => 'details',
      '#group' => 'additional_settings',
      '#title' => $this->t('LocalGov Finder type'),
      '#attributes' => ['class' => ['localgov-finders-type']],
      '#tree' => TRUE,
      '#weight' => 10,
    ];

    $finder_type_definitions = $this->finderTypeManager->getDefinitions();

    // @todo Remove empty options when
    // https://www.drupal.org/project/drupal/issues/3194345 is fixed in core.
    $options = [
      '' => $this->t('None'),
    ];
    $options += array_map(fn($definition) => $definition['label'], $finder_type_definitions);

    $form['finders']['finder_type'] = [
      '#type' => 'radios',
      '#title' => $this->t("Finder type"),
      '#options' => $options,
      '#empty_value' => '',
      '#default_value' => $bundle_type->getThirdPartySetting('finders', 'finder_type', ''),
    ];

    foreach ($finder_type_definitions as $finder_type_id => $definition) {
      $form['finders']['finder_type'][$finder_type_id]['#description'] = $definition['description'];
    }

    $form['finders']['finder_role'] = [
      '#type' => 'radios',
      '#title' => $this->t("Finder role"),
      '#options' => [
        '' => $this->t('None'),
        'channel' => $this->t('Finder channel: nodes of this type are finders'),
        'entries' => $this->t('Entries: nodes of this type are entries that can be shown in finders'),
      ],
      '#default_value' => $bundle_type->getThirdPartySetting('finders', 'finder_role', ''),
      // @todo Use States to hide this & make it required if a type is selected.
    ];

    // Don't allow removal of finder type fields.
    // @todo Add support for removing finder configuration.
    $form['finders']['finder_type']['#disabled'] = !empty($form['finders']['finder_type']['#default_value']);
    $form['finders']['finder_role']['#disabled'] = !empty($form['finders']['finder_role']['#default_value']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function validate(array $form, FormStateInterface $form_state): void {
    if (!empty($form_state->getValue(['finders', 'finder_type'])) && empty($form_state->getValue(['finders', 'finder_role']))) {
      $form_state->setError($form['finders']['finder_role'], t('The Finder role must be set if a Finder type is set.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function submit(array $form, FormStateInterface $form_state): void {
    $bundle_type = $form_state->getFormObject()->getEntity();
    assert($bundle_type instanceof ConfigEntityInterface);

    if (!empty($form_state->getValue(['finders', 'finder_type'])) && !empty($form_state->getValue(['finders', 'finder_role']))) {
      $bundle_type->setThirdPartySetting(
        'finders',
        'finder_type',
        $form_state->getValue(['finders', 'finder_type'])
      );
      $bundle_type->setThirdPartySetting(
        'finders',
        'finder_role',
        $form_state->getValue(['finders', 'finder_role'])
      );
      $bundle_type->save();
    }
  }

}
