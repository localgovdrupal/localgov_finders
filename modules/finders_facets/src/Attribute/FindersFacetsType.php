<?php

namespace Drupal\finders_facets\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

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
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The plugin label.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   The plugin description.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly TranslatableMarkup $description,
  ) {
  }

}
