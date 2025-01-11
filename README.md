AdminerNeo
==========

**AdminerNeo** is a full-featured database management tool written in PHP. It consists of a single file ready to deploy 
to the target server. As a companion, **AdminerNeo Editor** offers data manipulation for end-users. 

Supported database drivers:
- MySQL, MariaDB, PostgreSQL, SQLite, MS SQL, Oracle, MongoDB
- With plugin: SimpleDB, Elasticsearch (beta), Firebird (alpha), ClickHouse (alpha)

AdminerNeo is based on the [Adminer](https://www.adminer.org/) project by Jakub Vrána.

<img src="docs/images/screenshot.webp" width="830px" alt="Screenshot"/>

Requirements
------------

- PHP 7.1+ with enabled sessions.

Security
--------

AdminerNeo does not allow connecting to databases without a password, and it rate-limits connection attempts to protect
against brute force attacks. However, it is highly recommended to **restrict access to AdminerNeo** 🔒 by whitelisting IP
addresses allowed to connect to it, by password protecting access in your web server, or by enabling security plugins
(e.g. to require an OTP).

Migration from older versions
-----------------------------

Version 5 has been significantly redesigned and refactored. Unfortunately, this has resulted in many changes that break
backward compatibility.

A complete list of changes can be found in the [Upgrade Guide](docs/upgrade.md).

Usage
-----

Download one for the latest [release files](https://github.com/adminerneo/adminerneo/releases), upload to the HTTP server 
with PHP and enjoy 😉 If you are not satisfied with any combination of the database driver and language, you can 
download the source code and compile your own AdminerNeo:

- Download the source code.
- Run `composer install` to install dependencies.
- Run compile.php:

```shell
# AdminerNeo
php compile.php <drivers> <languages>

# AdminerNeo Editor
php compile.php editor <drivers> <languages>
```

For example:
```shell
php compile.php pgsql cs
php compile.php mysql,pgsql en,de,cs,sk
```

[Available drivers](https://github.com/adminerneo/adminerneo/tree/main/adminer/drivers), 
[languages](https://github.com/adminerneo/adminerneo/tree/main/adminer/lang).

Configuration
-------------

You can define a configuration as a constructor parameter. Create `index.php` file implementing `create_adminer()` 
method that returns configured `Adminer` instance.

```php
<?php

use Adminer\Adminer;

function create_adminer(): Adminer 
{
    // Define configuration.
    $config = [
        "theme" => "default-green",
    ];
	
    return new Adminer($config);
}

// Include original Adminer.
include "adminer.php";
```

Available configuration parameters:

| Parameter                   | Default value | Description                                                                                                                                     |
|-----------------------------|---------------|-------------------------------------------------------------------------------------------------------------------------------------------------|
| `theme`                     | default       | Theme code. Available themes are: `default`, `default-green`, `default-red`.                                                                    |
| `cssUrls`                   | []            | List of custom CSS files.                                                                                                                       |
| `jsUrls`                    | []            | List of custom Javascript files.                                                                                                                |
| `navigationMode`            | simple        | Main navigation mode that affects the left menu with the list of tables and top links: `simple`, `dual`, `reversed`.                            |
| `preferSelection`           | false         | Whether data selection is the primary action for all table links.                                                                               |
| `recordsPerPage`            | 50            | Number of selected records per one page.                                                                                                        |
| `versionVerification`       | true          | Whether verification of the new Adminer's version is enabled.                                                                                   |
| `hiddenDatabases`           | []            | List of databases to hide from the UI or a `__system` keyword to hide all system databases. Access to these databases is not restricted.        |
| `hiddenSchemas`             | []            | List of schemas to hide from the UI or a `__system` keyword to hide all system schemas. Access to these schemas is not restricted.              |
| `sslKey`                    | null          | MySQL: The path name to the SSL key file.                                                                                                       |
| `sslCertificate`            | null          | MySQL: The path name to the certificate file.                                                                                                   |
| `sslCaCertificate`          | null          | MySQL: The path name to the certificate authority file.                                                                                         |
| `sslMode`                   | null          | PostgreSQL: Value for [sslmode connection parameter](https://www.postgresql.org/docs/current/libpq-connect.html#LIBPQ-CONNECT-SSLMODE).         |
| `sslEncrypt`                | null          | MS SQL: Value for [Encrypt connection option](https://learn.microsoft.com/en-us/sql/connect/php/connection-options).                            |
| `sslTrustServerCertificate` | null          | MS SQL: Value for [TrustServerCertificate connection option](https://www.postgresql.org/docs/current/libpq-connect.html#LIBPQ-CONNECT-SSLMODE). |

For detailed information see [Configuration documentation](docs/upgrade.md).

Plugins
-------

AdminerNeo functions can be changed or extended by plugins. Plugins are managed by `Pluginer` customization class. 

* Download `Pluginer.php` and plugins you want and place them into the `plugins` folder.
* Create `index.php` file implementing `create_adminer()` method that returns Pluginer instance.

File structure will be:

```
- plugins
    - drivers
        - elastic.php
    - Pluginer.php
    - dump-xml.php
    - tinymce.php
    - file-upload.php
    - ...
- adminer.php
- index.php
```

Index.php:

```php
<?php

use Adminer\Pluginer;

function create_adminer(): Pluginer
{
    // Required to run any plugin.
    include "plugins/Pluginer.php";
    
    // Include plugins.
    include "plugins/dump-xml.php";
    include "plugins/tinymce.php.php";
    include "plugins/file-upload.php";
    
    // Enable plugins.
    $plugins = [
        new AdminerDumpXml(),
        new AdminerTinymce(),
        new AdminerFileUpload("data/"),
        // ...
    ];
    
    // Enable extra drivers just by including them.
    include "plugins/drivers/elastic.php";
    
    // Define configuration.
    $config = [
        "theme" => "default-green",
    ];
    
    return new Pluginer($plugins, $config);
}

// Include AdminerNeo or AdminerNeo Editor.
include "adminer.php";
```

[Available plugins](https://github.com/adminerneo/adminerneo/tree/main/plugins).

Main project files
------------------

- adminer/index.php - Run development version of AdminerNeo.
- editor/index.php - Run development version of AdminerNeo Editor.
- editor/example.php - Example Editor customization.
- adminer/plugins.php - Plugins demo.
- adminer/sqlite.php - Development version of AdminerNeo with SQLite allowed.
- editor/sqlite.php - Development version of Editor with SQLite allowed.
- compile.php - Create a single file version.
- lang.php - Update translations.
- tests/katalon.html - Katalon Automation Recorder test suite.

Project history
---------------

Adminer was originally developed by Jakub Vrána, and it can be still found on [official pages](https://www.adminer.org/).
Unfortunately, it is not maintained for several years. In the meantime, I (@peterpp) created for my company a set of
custom plugins, modern theme, fixed some bugs and practically rewrote the Elasticsearch driver. I also looked closely 
and contributed to the [AdminerEvo](https://www.adminerevo.org/) project that looked promising. However, I finally 
decided to continue working on this fork and fulfill my own vision.

What to expect
--------------

Our top priority is fixing the security issues and reported bugs. But we really want to move forward and transform
AdminerNeo to a tool that will keep its simplicity, yet looks much better, is even easier to use and can be configured
without requirement of additional plugins.

### Version 4.x

Original design and backward compatibility is maintained. Many bugs have been fixed and several functional and 
UI improvements have been introduced.

### Version 5

Bridges are burned 🔥🔥🔥. Our goals are:

- **Requirements** - Bump minimal PHP to 7.1, maybe even higher. 
- **Themes** – Modernize the current old-school theme, add new default theme based on our [Adminer theme](https://github.com/pematon/adminer-theme), 
support dark mode, configurable color variants for production/devel environment. All current designs will be removed. 
- **Plugins** - Integrate several basic plugins, enable them by optional configuration.
- **Codebase** - Prefer code readability before minimalism, use PER coding style, add namespaces.
- **Compilation** - Allow to export selected drivers, themes, languages and plugins into a single adminer.php file.
