<?php

namespace Drupal\finders_facets\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Contains form alter hook implementations for the Finders facets module.
 */
class FormAlterHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_finder_form_alter')]
  public function formFinderFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    $finder = $form_state->getFormObject()->getEntity();

    $form['facets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable facets'),
      '#default_value' => $finder->getThirdPartySetting('finders_facets', 'facets', FALSE),
    ];

    // The validate handler goes on the entire form, otherwise it zaps the form
    // class's own validation.
    $form['#validate'][] = static::class . '::finderFormValidate';

    foreach (Element::children($form['actions']) as $action) {
      $form['actions'][$action]['#submit'][] = static::class . '::finderFormsubmit';
    }
  }

  /**
   * Form validate handler for the finder form alteration.
   */
  public static function finderFormValidate(array $form, FormStateInterface $form_state): void {
  }

  /**
   * Form submit handler for the finder form alteration.
   */
  public static function finderFormsubmit(array $form, FormStateInterface $form_state): void {
    $finder = $form_state->getFormObject()->getEntity();

    if (empty($form_state->getValue(['facets']))) {
      $finder->unsetThirdPartySetting(
        'finders_facets',
        'facets',
      );
    }
    else {
      $finder->setThirdPartySetting(
        'finders_facets',
        'facets',
        $form_state->getValue(['facets'])
      );
      $finder->save();
    }
  }

}
