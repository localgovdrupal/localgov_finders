<?php

namespace Drupal\finders_test\Plugin\FinderType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\finders\Attribute\FinderType;
use Drupal\finders\Plugin\FinderType\FinderTypeBase;

/**
 * Finder type for using in tests.
 */
#[FinderType(
  id: "test",
  label: new TranslatableMarkup("Test finder type"),
  description: new TranslatableMarkup("Provides dummy plugin for testing"),
)]
class Test extends FinderTypeBase {

}
