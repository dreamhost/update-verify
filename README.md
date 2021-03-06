# Update Verify #
**Contributors:** danielbachhuber, ipstenu, dreamhost  
**Tags:** wordpress updates  
**Requires at least:** 4.4  
**Tested up to:** 5.7
**Stable tag:** 0.2.0  
**License:** MIT  
**License URI:** https://opensource.org/licenses/MIT  

Verifies the WordPress update process

## Description ##

Verifies the WordPress update process by comparing heuristics about the WordPress installation before and after the update. Specifically, these include:

* HTTP status code (200 or otherwise).
* Whether or not a PHP fatal error is detected.
* Whether or not the closing `</body>` tag is detected.

### Reporting mode ###

By default, Update Verify operates in a reporting mode; these heuristics are output alongside standard web or CLI update output.

    $ wp core update --path=/path/to/wordpress
    Updating to version 4.9.1 (en_US)...
    Fetching pre-update site response...
     -> HTTP status code: 200
     -> Correctly detected closing </body> tag.
     -> No uncaught fatal error detected.
    Unpacking the update...
    Fetching post-update site response...
     -> HTTP status code: 200
     -> Correctly detected closing </body> tag.
     -> No uncaught fatal error detected.
    Cleaning up files...
    No files found that need cleaning up.
    Success: WordPress updated successfully.

### WP-CLI command ###

To use the heuristics to influence the update process, run the `wp core safe-update` WP-CLI update command instead.

Under the hood, this WP-CLI command aborts the update process if it detects WordPress to already be broken, and rolls back to the prior WordPress version if the update process caused detectable breakage.

For WordPress installations older than 3.7, `wp core safe-update` will first use `wp core download` to bring the installation up to WordPress 3.7, and then use WordPress' internal upgrade process for the remaining update.

Here's an example of what you might see running `wp core safe-update`:

    ###
    # 500 status code observed while updating from WP 4.6 to 4.9, and causes rollback.
    ###
    $ wp core safe-update --path=/path/to/wordpress
    Currently running version 4.6
    Updating to version 4.9.1 (en_US)...
    Fetching pre-update site response...
     -> HTTP status code: 200
     -> Correctly detected closing </body> tag.
     -> No uncaught fatal error detected.
    Unpacking the update...
    Fetching post-update site response...
     -> HTTP status code: 500
     -> No closing </body> tag detected.
     -> No uncaught fatal error detected.
    Rolling WordPress back to version 4.6...
    Downloading WordPress 4.6 (en_US)...
    154 files cleaned up.
    Success: WordPress downloaded.
    Error: Failed post-update status code check (HTTP code 500).

If the update process is successful, all output is written to `STDOUT` and the process returns exit code `0`. If the update process fails in some way (pre- or post-updatE), the final error message is written to `STDERR` and the process returns exit code `1`.

## Installation ##

Update Verify can be installed as a WP-CLI package or as a WordPress plugin.

Installing as a WP-CLI package means Update Verify can be used globally on a server and will only execute during CLI-based updates:

    wp package install dreamhost/update-verify

Installing as a plugin to means Update Verify can also be executed during web-based updates:

    wp plugin install --activate https://github.com/dreamhost/update-verify

Both installation methods expose the `wp core safe-update` WP-CLI command, which requires WP-CLI 1.5.0-alpha-d71d228 or newer.

## Changelog ##

### 0.2.0 ( April 8, 2021) ###
* Finishing IP override and better fails in all cases.

### 0.1.3 (Feb 17, 2021) ###
* Allow IP override for internal DNS.

### 0.1.2 (Dec 10, 2019) ###
* Added new check with pure curl to account for cases when a proxy is down (the HTTP API library makes this a fatal, which breaks further checks)

### 0.1.1 (??? ??, 2018) ###
* Updated 'closing </body> tag' detection message to clarify it's a success.

### 0.1.0 (??? ??, 2017) ###
* Initial release.
