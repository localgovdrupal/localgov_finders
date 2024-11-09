<?php

namespace Drupal\finders_facets;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\finders\BundleConfigForm;
use Drupal\finders\Enum\FinderRole;

/**
 * Alters the bundle entity form to add form elements for facets.
 */
class BundleConfigFormFacets {

  use StringTranslationTrait;

  /**
   * Whether JSON:API's read-only mode is enabled.
   *
   * @var bool
   */
  protected $readOnlyModeIsEnabled;

  /**
   * Constructor.
   *
   * @param \Drupal\finders\BundleConfigForm $inner
   *   The decorated form alterer service.
   */
  public function __construct(
    protected BundleConfigForm $inner
    ) {
  }

  /**
   * {@inheritdoc}
   */
  public function alterBundleForm(&$form, FormStateInterface $form_state): void {
    $this->inner->alterBundleForm($form, $form_state);

    $bundle_type = $form_state->getFormObject()->getEntity();

    // Add our form elements into the 'finders' form group.
    $form['finders'] += $this->getFacetsFormElements($bundle_type, $form_state);

    foreach (Element::children($form['actions']) as $action) {
      $form['actions'][$action]['#validate'][] = static::class . '::validate';
      $form['actions'][$action]['#submit'][] = static::class . '::submit';
    }
  }

  /**
   * Gets the form elements for the bundle entity form.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle_type
   *   The bundle entity.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   An array of form elements.
   */
  public function getFacetsFormElements(ConfigEntityInterface $bundle_type, FormStateInterface $form_state): array {
    $form = [];

    $form['facets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use facets with this channel'),
      '#default_value' => $bundle_type->getThirdPartySetting('finders_facets', 'facets', FALSE),
      '#states' => [
        'invisible' => [
          ':input[name="finders[finder_role]"]' => [
            '!value' => FinderRole::Channel,
          ],
        ],
      ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function validate(array $form, FormStateInterface $form_state): void {
  }

  /**
   * {@inheritdoc}
   */
  public static function submit(array $form, FormStateInterface $form_state): void {
    $bundle_type = $form_state->getFormObject()->getEntity();
    assert($bundle_type instanceof ConfigEntityInterface);

    if (empty($form_state->getValue(['finders', 'facets']))) {
      $bundle_type->unsetThirdPartySetting(
        'finders_facets',
        'facets',
      );
    }
    else {
      $bundle_type->setThirdPartySetting(
        'finders_facets',
        'facets',
        $form_state->getValue(['finders', 'facets'])
      );
      $bundle_type->save();
    }
  }

}
