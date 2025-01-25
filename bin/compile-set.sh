#!/bin/sh

set -e

# Root directory.
BASEDIR=$( cd `dirname $0`/.. ; pwd )
cd "$BASEDIR"

composer update

php bin/compile.php
php bin/compile.php en
php bin/compile.php de
php bin/compile.php cs
php bin/compile.php sk

php bin/compile.php mysql
php bin/compile.php mysql en
php bin/compile.php mysql de
php bin/compile.php mysql cs
php bin/compile.php mysql sk

php bin/compile.php editor
php bin/compile.php editor en
php bin/compile.php editor mysql
php bin/compile.php editor mysql en
