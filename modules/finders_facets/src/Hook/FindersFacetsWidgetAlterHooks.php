<?php

namespace Drupal\finders_facets\Hook;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Contains widget alter hook implementations for the Finders facets module.
 *
 * This alters the finders_channels field widget to add AJAX to update the
 * facets field when a channel is selected.
 *
 * @see \Drupal\finders\Plugin\Field\FieldWidget\Channels
 */
class FindersFacetsWidgetAlterHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_field_widget_single_element_WIDGET_TYPE_form_alter().
   */
  #[Hook('field_widget_single_element_finders_channels_form_alter')]
  public function fieldWidgetSingleElementFindersChannelsFormAlter(array &$element, FormStateInterface $form_state, array $context) {
    $ajax = [
      'callback' => [
        static::class,
        'updateFields',
      ],
      'disable-refocus' => FALSE,
      'event' => 'change',
      'progress' => [
        'type' => 'throbber',
        'message' => $this->t('Loading facets...'),
      ],
    ];

    foreach (Element::children($element) as $key) {
      $element[$key]['#ajax'] = $ajax;
    }
  }

  /**
   * AJAX callback to rebuild form fields dependent on selected channels.
   *
   * Presently hard codes the one field - by name.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Replacements for form.
   */
  public static function updateFields(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();

    $renderer = \Drupal::service('renderer');
    // Render just this field, alone, no matter how it's placed on the form.
    $field = $form['localgov_directory_facets_select'];
    unset($field['#parents']);
    unset($field['#group']);
    unset($field['#groups']);
    $facets_field = $renderer->render($field);

    // And replace it.
    $response = new AjaxResponse();
    // @todo Derive this value from the field name constant for DRY.
    $response->addCommand(new ReplaceCommand('[data-drupal-selector=edit-finders-facets-select-wrapper]', $facets_field));

    return $response;
  }

}
