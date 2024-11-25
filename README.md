# Finders

Finders allows content editors to create searchable and filterable lists of
items, without needing to change site configuration.

Site administrators configure a Finder configuration entity, defining which
content types act as:
  - channels: Content which shows lists of entries
  - entries: Content which appears in channels

Content editors can then create channel and entry entities, which can be
searched by site users.

The Finders Facets submodule allows channels to use faceted search.

Finders uses Search API and Views to create lists of entry entities.

Finders can be customised and extended by creating custom Finder type plugins.

## Requirements

This module requires the following modules:

- [search_api](https://www.drupal.org/project/search_api)
- [viewsreference](https://www.drupal.org/project/viewsreference)

