<?php

namespace Drupal\localgov_finders\Enum;

/**
 * Values for the finder role.
 */
enum FinderRole: string {

  /**
   * Nodes whose node type has this role show lists of items.
   */
  case Channel = 'channel';

  /**
   * Nodes whose node type has this role are items shown in listings.
   */
  case Items = 'items';

}
