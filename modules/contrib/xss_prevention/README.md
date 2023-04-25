# XSS Prevention
This module's aim is to prevent XSS attacks.

It checks if URL contains some characters (%3e, %3c, >, <) to detect tag enclosure, or a list of JS event handlers.
When an attack is detected, the module will log it and make a redirection to the homepage.

## Installation
Normal module installation procedure. See
https://www.drupal.org/documentation/install/modules-themes/modules-8

## Configuration
You can configure the set of characters or the list of JS event handlers to check at /admin/config/system/xss_prevention.