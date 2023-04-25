# Session Inspector Module

Using this module you can allow your users the ability to view any sessions
they currently have open on a Drupal site, and look at closing down any
sessions they don't recognise. The goal of this module is to allow users to
manage their sessions themselves without administrators needing to intervene.

This helps with security as users can remove any sessions from locations or
devices that they don't recognise.


## Usage

- Install the module as normal.
- Give the user roles you want to allow access to the sessions page the
permission "inspect own users sessions".
- Users with the permission can now see the Session tab on their user profile
screens. They can also access this directly at `/user/[uid]/sessions`.
- Users can delete any sessions they want, including the session they are
currently using.

You can also assign the permission "inspect other user sessions" to
certain roles who can view the sessions in use by other users. Use this
permission sparingly though.

A configuration screen exists at `/admin/config/people/session_inspector`
that allows the browser, hostname and date format types to be selected.


## Permissions

The following permissions are defined.

### Inspect and manage own user sessions

- Machine name: inspect own user sessions:
- Description: User able to access and inspect their own session information.

### Inspect and manage other users sessions'

- Machine name: inspect other user sessions
- Description: User is able to access and inspect and manage other user
- session information.

### Configure the session inspector module

- Machine name: administer session inspector configuration:
- Description: Configure module and select from available plugins.


## Plugins

This module defines two plugins, format browser and format hostname.

### BrowserFormat

This plugin allows the user agent string to be translated into something
more readable.

The plugin must exist at `src\Plugin\BrowserFormat\` and must contain the
following annotation.

```
/**
 * @BrowserFormat(
 *   id = "basic",
 *   name = @Translation("Basic browser format")
 * )
 */
```

The plugin should extend the `BrowserFormatInterface` interface, which requires
a single method of `formatBrowser()` to be defined. This method accepts the user
agent string and must return a string.

### HostnameFormat

This plugin allows the user agent string to be translated into something
more readable.

The plugin must exist at `src\Plugin\HostnameFormat\` and must contain the
following annotation.

```
/**
 * @HostnameFormat(
 *   id = "basic",
 *   name = @Translation("Basic hostname format")
 * )
 */
```

The plugin should extend the HostnameFormatInterface interface, which requires a
single method of `formatHostname()` to be defined. This method accepts the user
agent string and must return a string.


## Events

When a session is destroyed the event `SessionInspectorEvents::SESSION_DESTROYED`
is triggered. You can use this event to trigger event deletion in upstream
services.


## Links

Here are some useful links that detail aspects of the session inspector module:

- [Drupal 9: Creating A Session Inspector Module](https://www.hashbangcode.com/article/drupal-9-creating-session-inspector-module)
- [Drupal 9: Adding Custom Plugins To The Session Inspector Module](https://www.hashbangcode.com/article/drupal-9-adding-custom-plugins-session-inspector-module)
- [Adding events to the Drupal Session Inspector module](https://www.codeenigma.com/blog/drupal-session-inspector-module)
