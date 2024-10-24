<?php

namespace Drupal\localgov_finders\Enum;

/**
 * Values for the finder role.
 */
enum FinderRole: string {

  /**
   * Nodes whose node type has this role show lists of entries.
   */
  case Channel = 'channel';

  /**
   * Nodes whose node type has this role are entries shown in listings.
   */
  case Entries = 'entries';

}
