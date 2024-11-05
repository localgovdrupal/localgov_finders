<?php

namespace Drupal\finders_facets;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\localgov_finders\BundleConfigForm;

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
   * @param \Drupal\localgov_finders\BundleConfigForm $inner
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

    $form['localgov_finders'] += $this->getFacetsFormElements($form_state);

    foreach (Element::children($form['actions']) as $action) {
      $form['actions'][$action]['#validate'][] = $this->validate(...);
      $form['actions'][$action]['#submit'][] = $this->submit(...);
    }
  }


  public function getFacetsFormElements(FormStateInterface $form_state): array {
    $form = [];

    $form['facets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use facets with this channel'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state): void {
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, FormStateInterface $form_state): void {

  }

}
