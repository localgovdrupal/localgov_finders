<?php

namespace Drupal\finders\Entity\Handler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides the list builder handler for the Finder entity.
 */
class FinderListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];
    $row['name'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

}
