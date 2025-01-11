Configuration
=============

You can define a configuration as a constructor parameter. Create `index.php` file implementing `create_adminer()`
method that returns configured Adminer instance.

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

Options
-------

### theme

Default value: `default`

Theme code. Available themes are: `default`, `default-green`, `default-red`. Please, be sure that the theme is compiled 
into the final single file.

### cssUrls

Default value: `[]`

List of custom CSS files.

### jsUrls

Default value: `[]`

List of custom Javascript files.

### navigationMode

Default value: `simple`

Main navigation mode that affects the left menu with the list of tables and top links.

- `simple` - Only one primary link is displayed in the left menu.
- `dual` - Both primary link and secondary icon are displayed in the left menu.
- `reversed` - Dual mode with reversed order of the primary link and secondary icon.

### preferSelection

Default value: `false`

Whether data selection is the primary action for all table links.

### recordsPerPage

Default value: `50`

Number of selected records per one page.

### versionVerification

Default value: `true`

Whether verification of the new Adminer's version is enabled.

### hiddenDatabases

Default value: `[]`

List of databases to hide from the UI or a `__system` keyword to hide all system databases.

❗️Warning: Access to these databases is not restricted. They can be still selected by modifying URL parameters.

### hiddenSchemas

Default value: `[]`

List of schemas to hide from the UI or a `__system` keyword to hide all system schemas.

❗️Warning: Access to these schemas is not restricted.

### sslKey

Default value: `null`

MySQL: The path name to the SSL key file.

### sslCertificate

Default value: `null`

MySQL: The path name to the certificate file.

### sslCaCertificate

Default value: `null`

MySQL: The path name to the certificate authority file.

### sslMode

Default value: `null`

PostgreSQL: Value for [sslmode connection parameter](https://www.postgresql.org/docs/current/libpq-connect.html#LIBPQ-CONNECT-SSLMODE).

### sslEncrypt

Default value: `null`

MS SQL: Value for [Encrypt connection option](https://learn.microsoft.com/en-us/sql/connect/php/connection-options).

### sslTrustServerCertificate

Default value: `null`

MS SQL: Value for [TrustServerCertificate connection option](https://www.postgresql.org/docs/current/libpq-connect.html#LIBPQ-CONNECT-SSLMODE).
