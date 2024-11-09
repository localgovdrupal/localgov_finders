<?php

namespace Drupal\finders\Plugin\FinderType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\finders\Attribute\FinderType;

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
class LegacyDirectories extends FinderTypeBase {

  /**
   * {@inheritdoc}
   */
  const string CHANNEL_TYPES_FIELD = 'localgov_directory_channel_types';

  /**
   * {@inheritdoc}
   */
  const string CHANNEL_SELECTION_FIELD = 'localgov_directory_channels';

  /**
   * {@inheritdoc}
   */
  const string TITLE_SORT_FIELD = 'localgov_directory_title_sort';

  /**
   * {@inheritdoc}
   */
  public function getIndexIds(): array {
    return ['locaalgov_directories_index_default'];
  }

  /**
   * {@inheritdoc}
   */
  public function getViewIds(): array {
    return ['localgov_directory_channel_view'];
  }

}
