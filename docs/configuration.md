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
        "colorVariant" => "green",
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

Theme code. Available themes are: `default`. Please, be sure that the theme is compiled into the final single file.

### colorVariant

Default value: `null`

Theme color code. Available variants are: `green`, `red`. Please, be sure that the color variant is compiled into the 
final single file together with selected theme.

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

List of databases to hide from the UI. Value `__system` will be expanded to all system databases for the current driver.
The `*` character can be used as a wildcard.

❗️Warning: Access to these databases is not restricted. They can be still selected by modifying URL parameters.

For example:
```php
$config = [
    "hiddenDatabases" => ["__system", "some_other_database"],
];
```

### hiddenSchemas

Default value: `[]`

List of schemas to hide from the UI. Value `__system` will be expanded to all system schemas for the current driver.
The `*` character can be used as a wildcard.

❗️Warning: Access to these schemas is not restricted. They can be still selected by modifying URL parameters.

For example:
```php
$config = [
    "hiddenSchemas" => ["__system", "some_other_schema"],
];
```

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
