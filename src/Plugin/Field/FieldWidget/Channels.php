<?php

namespace Drupal\finders\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Field widget to display finder channels.
 *
 * This shows the available options as two widgets:
 *  - radios to select the primary item, stored in the delta 0 of the field
 *  - checkboxes to select further items.
 */
#[FieldWidget(
  id: 'finders_channels',
  label: new TranslatableMarkup('Finder channels'),
  field_types: [
    'entity_reference',
  ],
  multiple_values: TRUE,
  weight: '-10',
)]
class Channels extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $primary_options = $secondary_options = $this->getOptions($items->getEntity());
    $secondary_selected = $this->getSelectedOptions($items);
    $primary_selected = array_shift($secondary_selected);

    $element += [
      '#type' => 'fieldset',
    ];

    if (empty($primary_options)) {
      $element['#description'] = $this->t('The finder channels this content should be found in. Will change the available facets.');
    }

    $element['primary'] = [
      '#title' => $this->t('Primary'),
      '#type' => 'radios',
      '#default_value' => $primary_selected,
      '#options' => $primary_options,
      // @todo Restore 'Path, breadcrumb, will be set for this channel'
      // description text when pathauto is set up.
      '#description' => $this->t('The primary channel this appears in.'),
    ];

    $element['secondary'] = [
      '#title' => $this->t('Others'),
      '#type' => 'checkboxes',
      '#default_value' => $secondary_selected,
      '#options' => $secondary_options,
      '#description' => $this->t('Other channels this will appear in.'),
    ];
    foreach ($secondary_options as $key => $value) {
      // @todo Use CHANNEL_SELECTION_FIELD from the appropriate finder type.
      $element['secondary'][$key]['#states']['invisible'] = [':input[name=finders_channels\[primary\]]' => ['value' => $key]];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    // Flatten the array again.
    if ($form_state->get('default_value_widget')) {
      $values = $form_state->getValue('default_value_input')[$element['#field_name']];
    }
    else {
      $values = $form_state->getValue($element['#field_name']);
    }
    if ($values) {
      $element['#value'] = array_filter(
        [$values['primary'] => $values['primary']] + $values['secondary']
      );
    }

    parent::validateElement($element, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This widget is only available on finder channel fields.
    // Checking for our reference selection plugin is the simplest way here.
    $field_settings = $field_definition->getSettings();
    if (isset($field_settings['handler']) && $field_settings['handler'] == 'finders_channels') {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
