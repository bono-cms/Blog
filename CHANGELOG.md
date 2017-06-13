CHANGELOG
=========

1.2
---

 * Ability to attach related posts
 * Added `getRecent()` method in `\Blog\Service\SiteService`
 * Support for parent categories and images
 * Replaced `text` input with `textarea` for `keywords` attribute
 * Fixed issue with quote escaping
 * In category template, added missing `Save & Create` and `Add & Create` buttons
 * Changed the way of storing configuration data. Since now its stored in the database
 * Added `name` attribute
 * Added support for table prefix
 * Updated module icon
 * Improved internal structure
 * Added `getCustomDateFormat()` for post entities to be able to turn their timestamps into any format
 * Added additional "Go home" item to reset category filters
 * Since now, the active category name is highlighted as green when browsing the grid
 * Added ability to fetch mostly viewed posts. Now users can simply call `getMostlyViewed()` on `$blog` service to get a collection of entities
 * Added view counter for posts
 * Minor improvements in internals
 * Adjusted the grid to two column view

1.1
---

 * Improved internals

1.0
---

 * First public version