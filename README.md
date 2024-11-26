# Finders

Finders allows content editors to create searchable and filterable lists of
items, without needing to change site configuration.

Site administrators configure a Finder configuration entity, defining which
content types act as:
  - channels: Content which shows lists of entries
  - entries: Content which appears in channels

Content editors can then create channel and entry entities, which can be
searched by site users.

Finders uses Search API and Views to create lists of entry entities. Faceted
search can be added by enabling the Finders Facets submodule.

Finders can be customised and extended by creating custom Finder type plugins.

Originally designed for LocalGov Drupal Directories as a simple way for site
builders to create ways for users to find content entries. This has been
generalized so information architecture modules such as directories, events,
news, consultations, anything that has entries in a channel can be configured by
the site builder to create an easy system for users to search and filter through
content.

## Requirements

This module requires the following modules:

- [search_api](https://www.drupal.org/project/search_api)
- [viewsreference](https://www.drupal.org/project/viewsreference)
