<?php

namespace Adminer;

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
set_error_handler(function ($errno, $errstr) {
	return (bool)preg_match('~^Undefined array key~', $errstr);
}, E_WARNING);

include __DIR__ . "/../adminer/include/version.inc.php";
include __DIR__ . "/../adminer/include/debug.inc.php";
include __DIR__ . "/../adminer/include/compile.inc.php";

function is_dev_version(): bool
{
	global $VERSION;

	return (bool)preg_match('~-dev$~', $VERSION);
}

function add_apo_slashes(string $s): string
{
	return addcslashes($s, "\\'");
}

function replace_lang(array $match): string
{
	global $lang_ids;

	$text = stripslashes($match[1]);
	if (!isset($lang_ids[$text])) {
		$lang_ids[$text] = count($lang_ids);
	}

	return "lang($lang_ids[$text]$match[2]";
}

function put_file(array $match, string $current_path = ""): string
{
	global $project, $selected_languages, $single_driver;

	$filename = basename($match[2]);
	$file_path = ltrim($match[2], "/");

	// Language is processed later.
	if ($filename == '$LANG.inc.php') {
		return $match[0];
	}

	$content = file_get_contents(__DIR__ . "/../$project/" . ($current_path ? "$current_path/" : "") . $file_path);

	if ($filename == "lang.inc.php") {
		$content = str_replace(
			'return $key; // !compile: convert translation key',
			'static $en_translations = null;

			// Convert string key used in plugins to compiled numeric key.
			if (is_string($key)) {
				if (!$en_translations) {
					$en_translations = get_translations("en");
				}

				// Find text in English translations or plurals map.
				if (($index = array_search($key, $en_translations)) !== false) {
					$key = $index;
				} elseif (($index = get_plural_translation_id($key)) !== null) {
					$key = $index;
				}
			}

			return $key;',
			$content, $count
		);

		if (!$count) {
			echo "function lang() not found\n";
		}

		if ($selected_languages) {
			$available_languages = array_fill_keys($selected_languages, true);
			$content = str_replace(
				'return $languages; // !compile: available languages',
				'return ' . var_export($available_languages, true) . ';',
				$content
			);
		}
	}

	$tokens = token_get_all($content); // to find out the last token

	return "?>\n$content" . (in_array($tokens[count($tokens) - 1][0], [T_CLOSE_TAG, T_INLINE_HTML], true) ? "<?php" : "");
}

function put_file_lang(): string
{
	global $lang_ids, $selected_languages;

	$languages = array_map(function ($filename) {
		preg_match('~/([^/.]+)\.inc\.php$~', $filename, $matches);
		return $matches[1];
	}, glob(__DIR__ . "/../adminer/lang/*.inc.php"));

	$cases = "";
	$plurals_map = [];

	foreach ($languages as $language) {
		// Include only selected language and "en" into single language compilation.
		// "en" is used for translations in plugins.
		if ($selected_languages && !in_array($language, $selected_languages) && $language != "en") {
			continue;
		}

		// Assign $translations
		$translations = [];
		include __DIR__ . "/../adminer/lang/$language.inc.php";

		$translation_ids = array_flip($lang_ids); // default translation
		foreach ($translations as $key => $val) {
			if ($val !== null) {
				$translation_ids[$lang_ids[$key]] = $val;

				if ($language == "en" && is_array($val)) {
					$plurals_map[$key] = $lang_ids[$key];
				}
			}
		}

		$cases .= 'case "' . $language . '": $compressed = "' . base64_encode(lzw_compress(json_encode($translation_ids, JSON_UNESCAPED_UNICODE))) . '"; break;';
	}

	$translations_version = crc32($cases);

	return '
		function get_translations($lang) {
			switch ($lang) {' . $cases . '}

			return json_decode(lzw_decompress(base64_decode($compressed)), true);
		}

		function get_plural_translation_id($key) {
			$plurals_map = ' . var_export($plurals_map, true) . ';

			return isset($plurals_map[$key]) ? $plurals_map[$key] : null;
		}

		$translations = $_SESSION["translations"];

		if ($_SESSION["translations_version"] != ' . $translations_version . ') {
			$translations = [];
			$_SESSION["translations_version"] = ' . $translations_version . ';
		}
		if ($_SESSION["translations_language"] != $LANG) {
			$translations = [];
			$_SESSION["translations_language"] = $LANG;
		}

		if (!$translations) {
			$translations = get_translations($LANG);
			$_SESSION["translations"] = $translations;
		}
	';
}

function short_identifier(int $number, string $chars): string
{
	$return = '';

	while ($number >= 0) {
		$return .= $chars[$number % strlen($chars)];
		$number = floor($number / strlen($chars)) - 1;
	}

	return $return;
}

// based on http://latrine.dgx.cz/jak-zredukovat-php-skripty
function php_shrink(string $input): string
{
	global $VERSION;

	$special_variables = array_flip(['$this', '$GLOBALS', '$_GET', '$_POST', '$_FILES', '$_COOKIE', '$_SESSION', '$_SERVER', '$http_response_header', '$php_errormsg']);
	$short_variables = [];
	$shortening = true;
	$tokens = token_get_all($input);

	// remove unnecessary { }
	//! change also `while () { if () {;} }` to `while () if () ;` but be careful about `if () { if () { } } else { }
	$shorten = 0;
	$opening = -1;
	foreach ($tokens as $i => $token) {
		if (in_array($token[0], [T_IF, T_ELSE, T_ELSEIF, T_WHILE, T_DO, T_FOR, T_FOREACH], true)) {
			$shorten = ($token[0] == T_FOR ? 4 : 2);
			$opening = -1;
		} elseif (in_array($token[0], [T_SWITCH, T_FUNCTION, T_CLASS, T_CLOSE_TAG], true)) {
			$shorten = 0;
		} elseif ($token === ';') {
			$shorten--;
		} elseif ($token === '{') {
			if ($opening < 0) {
				$opening = $i;
			} elseif ($shorten > 1) {
				$shorten = 0;
			}
		} elseif ($token === '}' && $opening >= 0 && $shorten == 1) {
			unset($tokens[$opening]);
			unset($tokens[$i]);
			$shorten = 0;
			$opening = -1;
		}
	}
	$tokens = array_values($tokens);

	foreach ($tokens as $token) {
		if ($token[0] === T_VARIABLE && !isset($special_variables[$token[1]])) {
			$short_variables[$token[1]]++;
		}
	}

	arsort($short_variables);
	$chars = implode(range('a', 'z')) . '_' . implode(range('A', 'Z'));
	// preserve variable names between versions if possible
	$short_variables2 = array_splice($short_variables, strlen($chars));
	ksort($short_variables);
	ksort($short_variables2);
	$short_variables += $short_variables2;
	foreach (array_keys($short_variables) as $number => $key) {
		$short_variables[$key] = short_identifier($number, $chars); // could use also numbers and \x7f-\xff
	}

	$set = array_flip(preg_split('//', '!"#$%&\'()*+,-./:;<=>?@[]^`{|}'));
	$space = '';
	$output = '';
	$in_echo = false;
	$doc_comment = false; // include only first /**

	for (reset($tokens); list($i, $token) = each($tokens); ) {
		if (!is_array($token)) {
			$token = [0, $token];
		}

		if (isset($tokens[$i+4]) && $tokens[$i+2][0] === T_CLOSE_TAG && $tokens[$i+3][0] === T_INLINE_HTML && $tokens[$i+4][0] === T_OPEN_TAG
			&& strlen(add_apo_slashes($tokens[$i+3][1])) < strlen($tokens[$i+3][1]) + 3
		) {
			$tokens[$i+2] = [T_ECHO, 'echo'];
			$tokens[$i+3] = [T_CONSTANT_ENCAPSED_STRING, "'" . add_apo_slashes($tokens[$i+3][1]) . "'"];
			$tokens[$i+4] = [0, ';'];
		}

		if ($token[0] == T_COMMENT || $token[0] == T_WHITESPACE || ($token[0] == T_DOC_COMMENT && $doc_comment)) {
			$space = " ";
		} else {
			if ($token[0] == T_DOC_COMMENT) {
				$doc_comment = true;
				$token[1] = substr_replace($token[1], "* @version $VERSION\n", -2, 0);
			}
			if ($token[0] == T_VAR) {
				$shortening = false;
			} elseif (!$shortening) {
				if ($token[1] == ';') {
					$shortening = true;
				}
			} elseif ($token[0] == T_ECHO) {
				$in_echo = true;
			} elseif ($token[1] == ';' && $in_echo) {
				if ($tokens[$i+1][0] === T_WHITESPACE && $tokens[$i+2][0] === T_ECHO) {
					next($tokens);
					$i++;
				}
				if ($tokens[$i+1][0] === T_ECHO) {
					// join two consecutive echos
					next($tokens);
					$token[1] = ','; // '.' would conflict with "a".1+2 and would use more memory //! remove ',' and "," but not $var","
				} else {
					$in_echo = false;
				}
			} elseif ($token[0] === T_VARIABLE && !isset($special_variables[$token[1]])) {
				$token[1] = '$' . $short_variables[$token[1]];
			}

			if ($token[0] == T_FUNCTION || $token[0] == T_CLASS || $token[0] == T_INTERFACE || $token[0] == T_TRAIT) {
				$space = "\n";
			} elseif (isset($set[substr($output, -1)]) || isset($set[$token[1][0]])) {
				$space = '';
			}

			$output .= $space . $token[1];
			$space = '';
		}
	}

	return $output;
}

if (!function_exists("each")) {
	function each(&$arr) {
		$key = key($arr);
		next($arr);
		return $key === null ? false : [$key, $arr[$key]];
	}
}

function min_version(): bool
{
	return true;
}

// Parse script arguments.
$arguments = $argv;
array_shift($arguments);

$project = "adminer";
if ($arguments[0] == "editor") {
	$project = "editor";
	array_shift($arguments);
}

$selected_drivers = [];
if ($arguments) {
	$params = explode(",", $arguments[0]);
	if (file_exists(__DIR__ . "/../adminer/drivers/" . $params[0] . ".inc.php")) {
		$selected_drivers = $params;
		array_shift($arguments);
	}
}
$single_driver = count($selected_drivers) == 1 ? $selected_drivers[0] : null;

$selected_languages = [];
if ($arguments) {
	$params = explode(",", $arguments[0]);
	if (file_exists(__DIR__ . "/../adminer/lang/" . $params[0] . ".inc.php")) {
		$selected_languages = $params;
		array_shift($arguments);
	}
}
$single_language = count($selected_languages) == 1 ? $selected_languages[0] : null;

if ($arguments) {
	echo "Usage: php compile.php [editor] [driver] [language]\n";
	echo "Purpose: Compile adminer[-driver][-lang].php or editor[-driver][-lang].php.\n";
	exit(1);
}

// Check function definition in drivers.
/* Disabled for now because it reports too many warnings.
$file = file_get_contents(__DIR__ . "/../adminer/drivers/mysql.inc.php");
$file = preg_replace('~class Min_Driver.*\n\t}~sU', '', $file);
preg_match_all('~\bfunction ([^(]+)~', $file, $matches); //! respect context (extension, class)
$functions = array_combine($matches[1], $matches[0]);
//! do not warn about functions without declared support()
unset($functions["__construct"], $functions["__destruct"], $functions["set_charset"]);

foreach (glob(__DIR__ . "/../adminer/drivers/*.inc.php") as $filename) {
	preg_match('~/([^/.]+)\.inc\.php$~', $filename, $matches);
	if ($matches[1] == "mysql" || ($selected_drivers && !in_array($matches[1], $selected_drivers))) {
		continue;
	}

	$file = file_get_contents($filename);
	foreach ($functions as $function) {
		if (!strpos($file, "$function(")) {
			fprintf(STDERR, "Missing $function in $filename\n");
		}
	}
}
*/

include __DIR__ . "/../adminer/include/pdo.inc.php";
include __DIR__ . "/../adminer/include/driver.inc.php";

$features = ["call" => "routine", "dump", "event", "privileges", "procedure" => "routine", "processlist", "routine", "scheme", "sequence", "status", "trigger", "type", "user" => "privileges", "variables", "view"];
$lang_ids = []; // global variable simplifies usage in a callback functions

// Start with index.php.
$file = file_get_contents(__DIR__ . "/../$project/index.php");

// Remove including source code for unsupported features in single-driver file.
if ($single_driver) {
	$_GET[$single_driver] = true; // to load the driver
	include __DIR__ . "/../adminer/drivers/$single_driver.inc.php";

	foreach ($features as $key => $feature) {
		if (!support($feature)) {
			if (is_string($key)) {
				$feature = $key;
			}
			$file = str_replace("} elseif (isset(\$_GET[\"$feature\"])) {\n\tinclude \"$feature.inc.php\";\n", "", $file);
		}
	}
	if (!support("routine")) {
		$file = str_replace("if (isset(\$_GET[\"callf\"])) {\n\t\$_GET[\"call\"] = \$_GET[\"callf\"];\n}\nif (isset(\$_GET[\"function\"])) {\n\t\$_GET[\"procedure\"] = \$_GET[\"function\"];\n}\n", "", $file);
	}
}

// Compile files included into the index.php.
$file = preg_replace_callback('~\binclude (__DIR__ \. )?"([^"]*)";~', 'Adminer\put_file', $file);

// Remove including devel files.
$file = str_replace('include __DIR__ . "/debug.inc.php"', '', $file);
$file = str_replace('include __DIR__ . "/compile.inc.php";', '', $file);
$file = str_replace('include __DIR__ . "/coverage.inc.php";', '', $file);

// Remove including unwanted drivers.
if ($selected_drivers) {
	$file = preg_replace_callback('~\binclude __DIR__ \. "/../drivers/([^.]+).*\n~', function ($match) use ($selected_drivers) {
		return in_array($match[1], $selected_drivers) ? $match[0] : "";
	}, $file);
}

// Compile files included into the /adminer/include/bootstrap.inc.php.
$file = preg_replace_callback('~\binclude (__DIR__ \. )?"([^"]*)";~', function ($match) {
	return put_file($match, "../adminer/include");
}, $file);

if ($single_driver) {
	// Remove source code for unsupported features.
	foreach ($features as $feature) {
		if (!support($feature)) {
			$file = preg_replace("((\t*)" . preg_quote('if (support("' . $feature . '")') . ".*\n\\1\\})sU", '', $file);
		}
	}

	// Remove Jush modules for other drivers.
	$file = preg_replace('~"\.\./vendor/vrana/jush/modules/jush-(?!textarea\.|txt\.|js\.|' . ($single_driver == "mysql" ? "sql" : preg_quote($single_driver)) . '\.)[^.]+.js",\n~', '', $file);

	$file = preg_replace_callback('~doc_link\(array\((.*)\)\)~sU', function ($match) use ($single_driver) {
		list(, $links) = $match;
		$links = preg_replace("~'(?!(" . ($single_driver == "mysql" ? "sql|mariadb" : $single_driver) . ")')[^']*' => [^,]*,?~", '', $links);
		return (trim($links) ? "doc_link([$links])" : "''");
	}, $file);

	//! strip doc_link() definition
}

// Compile language files.
$file = preg_replace_callback("~lang\\('((?:[^\\\\']+|\\\\.)*)'([,)])~s", 'Adminer\replace_lang', $file);
$file = preg_replace_callback('~\binclude __DIR__ \. "([^"]*\$LANG.inc.php)";~', 'Adminer\put_file_lang', $file);

$file = str_replace("\r", "", $file);

// Clean up namespaces.
preg_match_all('~^use ([^; ]+);~m', $file, $matches);
$file = preg_replace('~^use ([^; ]+);~m', "", $file);
$usages = implode("\n", array_combine($matches[1], $matches[0]));

$pos = strpos($file, "namespace Adminer;\n") + strlen("namespace Adminer;\n");
$file = substr($file, 0, $pos) . $usages . str_replace("namespace Adminer;\n", "", substr($file, $pos));

// Integrate static files.
preg_match_all('~link_files\("([^"]+)", \[([^]]+)]\)~', $file, $matches);

$cases = "";
$linked_files = [];
for ($i = 0; $i < count($matches[0]); $i++) {
	$name = $matches[1][$i];
	$files = trim($matches[2][$i], " \n\r\t\",");

	// TODO: Compile selected themes.
	if ($name == '$theme.css' || $name == '$theme-$variant.css') {
		continue;
	}

	// TODO: Compile icon variants.
	$name = str_replace('icon$postfix', "icon", $name);
	$files = str_replace('icon$postfix', "icon", $files);

	$file_paths = preg_split('~",\s+"~', $files);

	$linked_files[$name] = linked_filename($name, $file_paths);
	$cases .= 'case "' . $linked_files[$name] . '": $file = "' . compile_file($name, $file_paths) . '"; break;';
}

$file = str_replace(
	'$file = read_compiled_file($filename); // !compile: get compiled file',
	'switch ($filename) {' . $cases . '}',
	$file
);

$file = preg_replace_callback('~link_files\("([^"]+)", [^)]+\)~', function (array $match) use ($linked_files) {
	$name = $match[1];

	// TODO: Compile icon variants.
	$name = str_replace('icon$postfix', "icon", $name);

	return 'BASE_URL . "?file=' . urlencode($linked_files[$name]) . '"';
}, $file);

// Remove superfluous PHP tags.
$file = preg_replace("~<\\?php\\s*\\?>\n?|\\?>\n?<\\?php~", '', $file);

// Shrink final file.
$file = php_shrink($file);

// Save file to export directory.
@mkdir("export", 0777, true);
$filename = "export/$project"
	. (is_dev_version() ? "" : "-$VERSION")
	. ($single_driver ? "-$single_driver" : "")
	. ($single_language ? "-$single_language" : "")
	. ".php";

file_put_contents($filename, $file);
echo "$filename created (" . strlen($file) . " B).\n";
