<?php

namespace Drupal\finders_facets\Entity\Handler;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides the list builder handler for the Finders Facet Type entity.
 */
class FindersFacetTypeListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'finders_facet_type_list';
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No finder facets types available. <a href=":link">Add finder facets type</a>.',
      [':link' => Url::fromRoute('entity.finders_facet_type.add_form')->toString()]
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];
    $header['label'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

}
