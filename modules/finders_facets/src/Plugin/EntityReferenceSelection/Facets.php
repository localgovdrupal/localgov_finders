<?php

namespace Drupal\finders_facets\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Attribute\EntityReferenceSelection;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Entity reference selection plugin for the facets field on entries.
 */
#[EntityReferenceSelection(
  id: 'finders_facets_facets',
  label: new TranslatableMarkup('Facets'),
  group: 'finders_facets_facets',
  weight: 0,
  entity_types: [
    'finders_facet',
  ],
)]
class Facets extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    unset($form['target_bundles']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    return $query;
  }

}
