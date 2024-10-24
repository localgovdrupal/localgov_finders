<?php

namespace Drupal\localgov_finders\Plugin\FinderType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\localgov_finders\Attribute\FinderType;

/**
 * Directories finder type for sites built on Directories 3.x.
 *
 * This will use config fields to maintain compatibility.
 *
 * POC -- will move to the LGD Directories module.
 */
#[FinderType(
  id: "directories_legacy",
  label: new TranslatableMarkup("Directories (legacy support)"),
  description: new TranslatableMarkup("Provides directories which have entries (pages, venues, etc.) which can be filtered and searched"),
)]
class Directories extends FinderTypeBase {

}
