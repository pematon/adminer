<?php

$lang = $argv[1] ?? null;
if ($lang) {
	// Modify session and cookie to skip processing in language detection.
	unset($_COOKIE["adminer_lang"]);
	$_SESSION["lang"] = $lang;

	include __DIR__ . "/../adminer/include/lang.inc.php";

	if (isset($argv[2]) || (!isset($languages[$lang]) && $lang != "xx")) {
		echo "Usage: php update-translations.php [lang]\nPurpose: Update adminer/lang/*.inc.php from source code messages.\n";
		exit(1);
	}
}

// Get all texts from the source code.
$file_paths = array_merge(
	glob(__DIR__ . "/../adminer/*.php"),
	glob(__DIR__ . "/../adminer/core/*.php"),
	glob(__DIR__ . "/../adminer/include/*.php"),
	glob(__DIR__ . "/../adminer/drivers/*.php"),
	glob(__DIR__ . "/../editor/*.php"),
	glob(__DIR__ . "/../editor/core/*.php"),
	glob(__DIR__ . "/../editor/include/*.php"),
	glob(__DIR__ . "/../plugins/*.php"),
	glob(__DIR__ . "/../plugins/drivers/*.php")
);
$all_messages = [];
foreach ($file_paths as $file_path) {
	$source_code = file_get_contents($file_path);

	// lang() always uses apostrophes.
	if (preg_match_all("~lang\\(('(?:[^\\\\']+|\\\\.)*')([),])~", $source_code, $matches)) {
		$all_messages += array_combine($matches[1], $matches[2]);
	}
}

// Generate language file.
foreach (glob(__DIR__ . "/../adminer/lang/" . ($_SESSION["lang"] ?: "*") . ".inc.php") as $file_path) {
	$filename = basename($file_path);
	$messages = $all_messages;

	$old_content = str_replace("\r", "", file_get_contents($file_path));

	preg_match_all("~^(\\s*(?:// [^'].*\\s+)?)(?:// )?(('(?:[^\\\\']+|\\\\.)*') => .*[^,\n]),?~m", $old_content, $matches, PREG_SET_ORDER);

	// Keep current messages.
	$new_content = "";
	foreach ($matches as $match) {
		if (isset($messages[$match[3]])) {
			$new_content .= "$match[1]$match[2],\n";
			unset($messages[$match[3]]);
		}
	}

	// Add new messages.
	if ($messages) {
		if ($filename != "en.inc.php") {
			$new_content .= "\n";
		}

		foreach ($messages as $id => $text) {
			if ($text == "," && strpos($id, "%d")) {
				$new_content .= "\t$id => [],\n";
			} elseif ($filename != "en.inc.php") {
				$new_content .= "\t$id => null,\n";
			}
		}
	}

	$new_content = "<?php\n\nnamespace Adminer;\n\n\$translations = [\n$new_content];\n";

	if ($new_content != $old_content) {
		file_put_contents($file_path, $new_content);

		echo "$filename updated\n";
	}
}
