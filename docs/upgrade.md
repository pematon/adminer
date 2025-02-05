Upgrade guide
=============

Migrating to 5.0
----------------

- Minimum required PHP version is 7.1.
- TODO: Adminer namespace
- TODO: adminer_object -> create_adminer
- TODO: AdminerPlugin -> Pluginer
- TODO: removed autoload of plugins based on class name 
- TODO: removed all designs, new configurable theme and color variants
- TODO: removed driver `elastic5`, all drivers can be compiled
- TODO: removed plugin `AdminerVersionNoverify`, config option `versionVerification`
- TODO: removed plugin `AdminerDatabaseHide`, config options `hiddenDatabases`, `hiddenSchemas`
- TODO: removed plugin `AdminerDotJs`, config option `jsUrls`
- TODO: removed plugin `AdminerLoginSsl`, config options `ssl*`
- TODO: removed plugin `AdminerDotJs`, adminer.js is autoloaded
- TODO: config option `visibleCollations` as a replacement for the plugin [AdminerCollations](https://github.com/pematon/adminer-plugins#adminercollations)
- TODO: plugin interface
    - credentials() -> getCredentials()
    - login() -> authenticate()
- TODO: removed customizable css() method, config option `cssUrls`
- TODO: removed unused selectQueryBuild() customization method
- TODO: set `navigationMode` as a replacement for [AdminerSimpleMenu](https://github.com/pematon/adminer-plugins?tab=readme-ov-file#adminersimplemenu)
    - Set to `reversed` for original-like menu

Migrating to 4.10
-----------------

- Remove plugin AdminerTablesFilter (plugins/tables-filter.php) if you use it. Its functionality was integrated into the
  base code.
- If you use complex custom theme, you will probably need to adjust a thing or two.

Migrating to 4.9
----------------

- Minimum required PHP version is 5.6.
