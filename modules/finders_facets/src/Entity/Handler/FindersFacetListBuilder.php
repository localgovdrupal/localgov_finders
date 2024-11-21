<?php

namespace Drupal\finders_facets\Entity\Handler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides the list builder handler for the Finders Facet entity.
 */
class FindersFacetListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['table'] = parent::render();

    $total = $this->getStorage()
      ->getQuery()
      ->count()
      ->accessCheck(TRUE)
      ->execute();

    $build['summary']['#markup'] = $this->t('Total finder facets: @total', ['@total' => $total]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['bundle'] = $this->t('Facet Type');
    $header['title'] = $this->t('Title');
    $header['author'] = $this->t('Author');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['bundle'] = $entity->bundle();
    $row['title'] = $entity->toLink(NULL, 'edit-form');
    $row['uid']['data'] = [
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    ];
    return $row + parent::buildRow($entity);
  }

}
