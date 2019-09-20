# Delicious Brains Automatic Logins

WordPress library for generating automatic login URLs for users

### Requirements

This package is designed to be used on a WordPress site project, not for a plugin or theme. 

It needs to be running PHP 5.3 or higher.

It requires the [deliciousbrains/wp-migration](https://github.com/deliciousbrains/wp-migrations) package and so the site will need to be set up to run `wp dbi migrate` as a last stage build step in your deployment process.

### Installation

- `composer require deliciousbrains/wp-auto-login`
- Bootstrap the package by adding `\DeliciousBrains\WPAutoLogin\AutoLogin::instance();` to an mu-plugin.

### Use

To generate a URL that will automatically login a user and land them at a specific URL use this function:

`dbi_get_auto_login_url( $destination_url, $user_id', $query_parms );`

The URL will expire in 120 days. However, you can pass the number of seconds the URL will be valid for as the fourth argument, e.g valid for 1 day:

`dbi_get_auto_login_url( $destination_url, $user_id', $query_parms, 86400 );`
