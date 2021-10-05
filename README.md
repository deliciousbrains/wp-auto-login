# Delicious Brains Automatic Logins

WordPress library for generating automatic login URLs for users

## Requirements

This package is designed to be used on a WordPress site project, not for a plugin or theme.

It needs to be running PHP 5.3 or higher.

It requires the [deliciousbrains/wp-migration](https://github.com/deliciousbrains/wp-migrations) package and so the site will need to be set up to run `wp dbi migrate` as a last stage build step in your deployment process.

You should also run `wp dbi migrate` after updating the package to make sure you have up to date database tables.

It automatically purges expired keys from the database daily, and there are WP-CLI commands to:

1. Manually purge expired keys
2. Manually generate an auto-login URL

## Installation

- `composer require deliciousbrains/wp-auto-login`
- Bootstrap the package by adding `\DeliciousBrains\WPAutoLogin\AutoLogin::instance();` to an mu-plugin.

There are two parameters you can pass when bootstrapping the package:

 * A custom WP-CLI parent command name (default: `'dbi'`)
 * A global default expiry time in seconds (default: `10368000` - 120 days)

 These options are explained below.

## Use

To generate a URL that will automatically login a user and land them at a specific URL use this function:

`dbi_get_auto_login_url( $destination_url, $user_id, [$query_params], [$expiry], [$one_time] );`

The URL will expire in 120 days. However, you can pass the number of seconds the URL will be valid for as the fourth argument, e.g valid for 1 day:

`dbi_get_auto_login_url( $destination_url, $user_id, $query_params, 86400 );`

You can also specify your own global default for expiry when bootstrapping the package as explained in the "Installation" section above. Use:

`\DeliciousBrains\WPAutoLogin\AutoLogin::instance( 'dbi', <expiry_in_seconds> );`

There is also an option to generate links that can only be used once:

`dbi_get_auto_login_url( $destination_url, $user_id, $query_parms, null, true );`

## WP-CLI

There are two WP-CLI commands.

The commands are added as sub-commands of a parent command. By default the parent command is `dbi` (for example: `wp dbi purge_autologin_keys`). But this can be changed when you bootstrap the package.

For example, doing:

`\DeliciousBrains\WPAutoLogin\AutoLogin::instance( 'autologin', <expiry_in_seconds> );`

will make the commands to be like:

`wp autologin purge_autologin_keys`

### purge_autologin_keys

This command purges any expired keys from the WordPress database. On most sites this happens daily, automatically, with a [WP-Cron task](https://developer.wordpress.org/plugins/cron/). But if you have disabled WP-Cron or want to do this manually for whatever reason then this WP-CLI command lets you do it:

`wp dbi purge_autologin_keys`

### auto_login_url

This command manually generates an auto-login URL that logs a specified user in and sends them to a specified URL.

`wp dbi auto_login_url <user_id> <url> [--expiry=<seconds>]`

The default expiry is used, but you can override it for each link that you create with this command by specifying your own expiry in seconds.

Example:

`wp dbi auto_login_url 12345 https://example.com/dashboard --expiry=21600`

Will generate a link that logs in the user with ID 12345 and takes them to https://example.com/dashboard. The link will be valid for 6 hours.

You can add `--one-time` to generate a single-use link:

`wp dbi auto_login_url 12345 https://example.com/dashboard --one-time`
