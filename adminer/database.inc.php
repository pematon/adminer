<?php

namespace Adminer;

$row = $_POST;

if ($_POST && !$error && !isset($_POST["add_x"])) { // add is an image and PHP changes add.x to add_x
	$name = trim($row["name"]);
	if ($_POST["drop"]) {
		$_GET["db"] = ""; // to save in global history
		queries_redirect(remove_from_uri("db|database"), lang('Database has been dropped.'), drop_databases([DB]));
	} elseif (DB !== $name) {
		// create or rename database
		if (DB != "") {
			$_GET["db"] = $name;
			queries_redirect(preg_replace('~\bdb=[^&]*&~', '', ME) . "db=" . urlencode($name), lang('Database has been renamed.'), rename_database($name, $row["collation"]));
		} else {
			$databases = explode("\n", str_replace("\r", "", $name));
			$success = true;
			$last = "";
			foreach ($databases as $db) {
				if (count($databases) == 1 || $db != "") { // ignore empty lines but always try to create single database
					if (!create_database($db, $row["collation"])) {
						$success = false;
					}
					$last = $db;
				}
			}
			restart_session();
			set_session("dbs", null);
			queries_redirect(ME . "db=" . urlencode($last), lang('Database has been created.'), $success);
		}
	} else {
		// alter database
		if (!$row["collation"]) {
			redirect(substr(ME, 0, -1));
		}
		query_redirect("ALTER DATABASE " . idf_escape($name) . (preg_match('~^[a-z0-9_]+$~i', $row["collation"]) ? " COLLATE $row[collation]" : ""), substr(ME, 0, -1), lang('Database has been altered.'));
	}
}

if (DB != "") {
	page_header(lang('Alter database') . ": " . h(DB), $error, [lang('Alter database')]);
} else {
	page_header(lang('Create database'), $error, [lang('Create database')]);
}

$name = DB;
if ($_POST) {
	$name = $row["name"];
} elseif (DB != "") {
	$row["collation"] = db_collation(DB, collations());
} elseif ($jush == "sql") {
	// propose database name with limited privileges
	foreach (get_vals("SHOW GRANTS") as $grant) {
		if (preg_match('~ ON (`(([^\\\\`]|``|\\\\.)*)%`\.\*)?~', $grant, $match) && $match[1]) {
			$name = stripcslashes(idf_unescape("`$match[2]`"));
			break;
		}
	}
}

$collations = $adminer->collations($row["collation"] ? [$row["collation"]] : []);

?>

<form action="" method="post">
<p>
<?php
echo ($_POST["add_x"] || strpos($name, "\n")
	? '<textarea id="name" name="name" rows="10" cols="40">' . h($name) . '</textarea><br>'
	: '<input class="input" name="name" id="name" value="' . h($name) . '" data-maxlength="64" autocapitalize="off" autofocus>'
) . "\n" . ($collations ? html_select("collation", ["" => "(" . lang('collation') . ")"] + $collations, $row["collation"]) . doc_link([
	'sql' => "charset-charsets.html",
	'mariadb' => "supported-character-sets-and-collations/",
	'mssql' => "ms187963.aspx",
]) : "");
?>
<input type="submit" class="button" value="<?php echo lang('Save'); ?>">
<?php
if (DB != "") {
	echo "<input type='submit' class='button' name='drop' value='" . lang('Drop') . "'>" . confirm(lang('Drop %s?', DB)) . "\n";
} elseif (!$_POST["add_x"] && $_GET["db"] == "") {
	echo "<button name='add_x' value='1' title='", h(lang('Add next')), "' class='button light'>", icon_solo("add"), "</button>\n";
}
?>
<input type="hidden" name="token" value="<?php echo $token; ?>">
</form>
