<?php

namespace Drupal\finders_facets\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Defines a Finders Facets Type attribute object.
 *
 * Plugin namespace: FindersFacetsType.
 */
#[\Attribute(
  \Attribute::TARGET_CLASS,
)]
class FindersFacetsType extends Plugin {

  /**
   * Constructs a FindersFacetsType attribute.
   *
   * @param string $id
   *   The plugin ID.
   */
  public function __construct(
    public readonly string $id,
  ) {
  }

}
