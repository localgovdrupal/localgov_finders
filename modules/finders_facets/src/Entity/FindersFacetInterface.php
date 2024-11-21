<?php

namespace Drupal\finders_facets\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface for Finders Facet entities.
 */
interface FindersFacetInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

}
