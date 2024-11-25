<?php

namespace Drupal\finders\Enum;

/**
 * Values for the finder role.
 */
enum FinderRole: string {

  /**
   * Bundles with this role show lists of entries.
   */
  case Channels = 'channels';

  /**
   * Bundles with this role are entries shown in listings.
   */
  case Entries = 'entries';

}
