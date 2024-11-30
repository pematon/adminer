Upgrade guide
=============

Migrating to 5.0
----------------

- Minimum required PHP version is 7.1.
- TODO: Adminer namespace
- TODO: adminer_object -> create_adminer
- TODO: AdminerPlugin -> Pluginer
- TODO: removed autoload of plugins based on class name 
- TODO: removed all designs, new configurable theme

Migrating to 4.10
-----------------

- Remove plugin AdminerTablesFilter (plugins/tables-filter.php) if you use it. Its functionality was integrated into the
  base code.
- If you use complex custom theme, you will probably need to adjust a thing or two.

Migrating to 4.9
----------------

- Minimum required PHP version is 5.6.
