<?php

namespace Drupal\finders_facets\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the default form handler for the Finders Facet Type entity.
 */
class FindersFacetTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add finders facet type');
    }
    else {
      $form['#title'] = $this->t(
        'Edit %label finders facet type',
        ['%label' => $this->entity->label()]
      );
    }

    $form['label'] = [
      '#type' => "textfield",
      '#title' => $this->t("Name"),
      '#description' => $this->t("The human-readable name of this finders facet type"),
      '#default_value' => $this->entity->get('label'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => "machine_name",
      '#title' => $this->t("Name"),
      '#description' => $this->t("A unique machine-readable name for this finders facet type. It must only contain lowercase letters, numbers, and underscores."),
      '#default_value' => $this->entity->id(),
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => ['Drupal\finders_facets\Entity\FindersFacetType', 'load'],
        'source' => ['label'],
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $this->entity->get('description'),
    ];

    $form['weight'] = [
      '#type'          => 'weight',
      '#title' => $this->t("Weight"),
      '#description'   => $this->t('Facet types are displayed in ascending order by weight.'),
      '#default_value' => $this->entity->get('weight'),
      '#delta'         => 50,
      '#weight'        => 100,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save finders facets type');
    $actions['delete']['#value'] = $this->t('Delete finders facets type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $facet_type = $this->entity;

    $facet_type->set('id', trim($facet_type->id()));
    $facet_type->set('label', trim($facet_type->label()));
    $facet_type->set('description', trim($facet_type->getDescription()));
    $facet_type->set('weight', $facet_type->get('weight'));

    $status = $facet_type->save();

    $t_args = ['%name' => $facet_type->label()];
    $message = '';
    if ($status == SAVED_UPDATED) {
      $message = $this->t('The finders facets type %name has been updated.', $t_args);
    }
    elseif ($status == SAVED_NEW) {
      $message = $this->t('The finders facets type %name has been added.', $t_args);
    }
    $this->messenger()->addStatus($message);

    $form_state->setRedirectUrl($facet_type->toUrl('collection'));

    return $status;
  }

}
