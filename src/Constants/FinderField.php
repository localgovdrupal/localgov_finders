<?php

namespace Drupal\localgov_finders\Constants;

/**
 * Finder field name constants.
 *
 * TODO move these values to the plugin class, as they are overridable
 * per-plugin and possibly don't need to be known outside of a plugin.
 */
class FinderField {

  const CHANNEL_TYPES_FIELD = 'localgov_directory_channel_types';

  const CHANNEL_VIEW_FIELD = 'localgov_directory_channel_view';

  const CHANNEL_FACETS_ENABLE_FIELD = 'localgov_directory_facets_enable';

  const CHANNEL_SELECTION_FIELD = 'localgov_directory_channels';

  const TITLE_SORT_FIELD = 'localgov_directory_title_sort';

  const FACET_SELECTION_FIELD = 'localgov_directory_facets_select';

}
