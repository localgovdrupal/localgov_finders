<?php

namespace Drupal\localgov_finders\Plugin\FinderType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\localgov_finders\Attribute\FinderType;

/**
 * Directories finder type.
 *
 * POC -- will move to the LGD Directories module.
 */
#[FinderType(
  id: "directories",
  label: new TranslatableMarkup("Directories"),
  description: new TranslatableMarkup("Provides directories which have entries (pages, venues, etc.) which can be filtered and searched"),
)]
class Directories extends FinderTypeBase {

}
