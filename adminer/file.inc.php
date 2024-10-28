<?php

namespace Adminer;

// caching headers added in compile.php

if ($_GET["file"] == "favicon.ico") {
	header("Content-Type: image/x-icon");
	echo lzw_decompress(compile_file('../adminer/static/favicon.ico', 'Adminer\lzw_compress'));
} elseif ($_GET["file"] == "default.css") {
	header("Content-Type: text/css; charset=utf-8");
	echo lzw_decompress(compile_file('../adminer/static/default.css;../vendor/vrana/jush/jush.css', 'Adminer\minify_css'));
} elseif ($_GET["file"] == "functions.js") {
	header("Content-Type: text/javascript; charset=utf-8");
	echo lzw_decompress(compile_file('../adminer/static/functions.js;static/editing.js', 'Adminer\minify_js'));
} elseif ($_GET["file"] == "jush.js") {
	header("Content-Type: text/javascript; charset=utf-8");
	echo lzw_decompress(compile_file('../vendor/vrana/jush/modules/jush.js;../vendor/vrana/jush/modules/jush-textarea.js;../vendor/vrana/jush/modules/jush-txt.js;../vendor/vrana/jush/modules/jush-js.js;../vendor/vrana/jush/modules/jush-sql.js;../vendor/vrana/jush/modules/jush-pgsql.js;../vendor/vrana/jush/modules/jush-sqlite.js;../vendor/vrana/jush/modules/jush-mssql.js;../vendor/vrana/jush/modules/jush-oracle.js;../vendor/vrana/jush/modules/jush-simpledb.js', 'Adminer\minify_js'));
} elseif ($_GET["file"] == "icons.svg") {
	header("Content-Type: image/svg+xml");
	echo compile_file('../adminer/static/' . $_GET["file"]);
}
exit;
