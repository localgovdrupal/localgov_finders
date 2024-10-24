<?php

namespace Drupal\localgov_finders\Plugin\FinderType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\localgov_finders\Attribute\FinderType;
use Drupal\localgov_finders\Plugin\FinderType\FinderTypeBase;

/**
 * Finder type for events.
 *
 * Provides calendars and listings.
 *
 * POC -- will move to the LGD Directories module.
 */
#[FinderType(
  id: "events",
  label: new TranslatableMarkup("Events"),
  description: new TranslatableMarkup("Provides event calendars and listings"),
)]
class Events extends FinderTypeBase {

}
