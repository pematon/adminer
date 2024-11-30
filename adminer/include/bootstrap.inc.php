<?php

namespace Adminer;

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
set_error_handler(function ($errno, $errstr) {
	return (bool)preg_match('~^Undefined array key~', $errstr);
}, E_WARNING);

include __DIR__ . "/debug.inc.php";
include __DIR__ . "/coverage.inc.php";

// disable filter.default
$filter = !preg_match('~^(unsafe_raw)?$~', ini_get("filter.default"));
if ($filter || ini_get("filter.default_flags")) {
	foreach (array('_GET', '_POST', '_COOKIE', '_SERVER') as $val) {
		$unsafe = filter_input_array(constant("INPUT$val"), FILTER_UNSAFE_RAW);
		if ($unsafe) {
			$$val = $unsafe;
		}
	}
}

if (function_exists("mb_internal_encoding")) {
	mb_internal_encoding("8bit");
}

include __DIR__ . "/../core/Config.php";
include __DIR__ . "/functions.inc.php";
include __DIR__ . "/compile.inc.php";

// Compiled files loading.
include __DIR__ . "/../file.inc.php";

if ($_GET["script"] == "version") {
	$file = open_file_with_lock(get_temp_dir() . "/adminer.version");
	if ($file) {
		write_and_unlock_file($file, serialize(["version" => $_POST["version"]]));
	}
	exit;
}

// Allows including Adminer inside a function.
global $adminer, $connection, $driver, $drivers, $edit_functions, $enum_length, $error, $functions, $grouping, $HTTPS, $inout, $jush, $LANG, $languages, $on_actions, $permanent, $structured_types, $has_token, $token, $translations, $types, $unsigned, $VERSION;

if (!$_SERVER["REQUEST_URI"]) { // IIS 5 compatibility
	$_SERVER["REQUEST_URI"] = $_SERVER["ORIG_PATH_INFO"];
}
if (!strpos($_SERVER["REQUEST_URI"], '?') && $_SERVER["QUERY_STRING"] != "") { // IIS 7 compatibility
	$_SERVER["REQUEST_URI"] .= "?$_SERVER[QUERY_STRING]";
}
if ($_SERVER["HTTP_X_FORWARDED_PREFIX"]) {
	$_SERVER["REQUEST_URI"] = $_SERVER["HTTP_X_FORWARDED_PREFIX"] . $_SERVER["REQUEST_URI"];
}
$HTTPS = ($_SERVER["HTTPS"] && strcasecmp($_SERVER["HTTPS"], "off")) || ini_bool("session.cookie_secure"); // session.cookie_secure could be set on HTTP if we are behind a reverse proxy

@ini_set("session.use_trans_sid", false); // protect links in export @ - may be disabled
if (!defined("SID")) {
	session_cache_limiter(""); // to allow restarting session
	session_name("adminer_sid");
	session_set_cookie_params(0, preg_replace('~\?.*~', '', $_SERVER["REQUEST_URI"]), "", $HTTPS, true);
	session_start();
}

// disable magic quotes to be able to use database escaping function
remove_slashes(array(&$_GET, &$_POST, &$_COOKIE), $filter);
if (function_exists("get_magic_quotes_runtime") && get_magic_quotes_runtime()) {
	set_magic_quotes_runtime(false);
}
@set_time_limit(0); // @ - can be disabled
@ini_set("zend.ze1_compatibility_mode", false); // @ - deprecated
@ini_set("precision", 15); // @ - can be disabled, 15 - internal PHP precision

// Migration for backward compatibility. This will keep MySQL users logged in.
if (isset($_GET["username"])) {
	// Old 'server' URL param.
	if (isset($_GET["server"])) {
		$_GET["mysql"] = $_GET["server"];
		unset($_GET["server"]);
	}

	// No URL param for any driver.
	$driver_params = array_filter(["mysql", "pgsql", "sqlite", "sqlite2", "oracle", "mssql", "mongo", "clickhouse", "elastic", "elastic5", "firebird", "simpledb"], function ($driver) {
		return isset($_GET[$driver]);
	});
	if (!$driver_params) {
		$_GET["mysql"] = "";
	}

	// Migrate session data.
	if (isset($_SESSION["pwds"]["server"])) {
		foreach (["pwds", "db", "dbs", "queries"] as $key) {
			if (isset($_SESSION[$key]["server"])) {
				$_SESSION[$key]["mysql"] = $_SESSION[$key]["server"];
				unset($_SESSION[$key]["server"]);
			}
		}
	}
}

include __DIR__ . "/lang.inc.php";
include __DIR__ . "/../lang/$LANG.inc.php";

include __DIR__ . "/pdo.inc.php";
include __DIR__ . "/driver.inc.php";

include __DIR__ . "/../drivers/mysql.inc.php";
include __DIR__ . "/../drivers/pgsql.inc.php";
include __DIR__ . "/../drivers/sqlite.inc.php";
include __DIR__ . "/../drivers/oracle.inc.php";
include __DIR__ . "/../drivers/mssql.inc.php";
include __DIR__ . "/../drivers/mongo.inc.php";

if (function_exists('\create_adminer')) {
	$adminer = \create_adminer();
} elseif (function_exists('Adminer\create_adminer')) {
	$adminer = create_adminer();
} else {
	$adminer = new Adminer();
}

if (defined("DRIVER")) {
	$config = driver_config();
	$possible_drivers = $config['possible_drivers'];
	$jush = $config['jush'];
	$types = $config['types'];
	$structured_types = $config['structured_types'];
	$unsigned = $config['unsigned'];
	$operators = $config['operators'];
	$operator_like = $config['operator_like'];
	$operator_regexp = $config['operator_regexp'];
	$functions = $config['functions'];
	$grouping = $config['grouping'];
	$edit_functions = $config['edit_functions'];

	$adminer->setOperators($operators, $operator_like, $operator_regexp);
} else {
	define("DRIVER", null);
}

define("SERVER", DRIVER ? $_GET[DRIVER] : null); // read from pgsql=localhost
define("DB", $_GET["db"]); // for the sake of speed and size
define("BASE_URL", preg_replace('~\?.*~', '', relative_uri()));
define("ME", BASE_URL . '?'
	. (sid() ? session_name() . "=" . urlencode(session_id()) . '&' : '')
	. (SERVER !== null ? DRIVER . "=" . urlencode(SERVER) . '&' : '')
	. (isset($_GET["username"]) ? "username=" . urlencode($_GET["username"]) . '&' : '')
	. (DB != "" ? 'db=' . urlencode(DB) . '&' . (isset($_GET["ns"]) ? "ns=" . urlencode($_GET["ns"]) . "&" : "") : '')
);
define("HOME_URL", substr(preg_replace('~\b(username|db|ns)=[^&]*&~', '', ME), 0, -1) ?: ".");

include __DIR__ . "/version.inc.php";
include __DIR__ . "/design.inc.php";
include __DIR__ . "/xxtea.inc.php";
include __DIR__ . "/auth.inc.php";

$on_actions = "RESTRICT|NO ACTION|CASCADE|SET NULL|SET DEFAULT"; ///< @var string used in foreign_keys()
