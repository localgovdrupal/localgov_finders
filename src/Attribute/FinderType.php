<?php

namespace Drupal\localgov_finders\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Finder Type attribute object.
 *
 * Plugin namespace: FinderType.
 */
#[\Attribute(
  \Attribute::TARGET_CLASS,
)]
class FinderType extends Plugin {

  /**
   * Constructs a FinderType attribute.
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
