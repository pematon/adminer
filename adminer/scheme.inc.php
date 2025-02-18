<?php

namespace Adminer;

$row = $_POST;

if ($_POST && !$error) {
	$link = preg_replace('~ns=[^&]*&~', '', ME) . "ns=";
	if ($_POST["drop"]) {
		query_redirect("DROP SCHEMA " . idf_escape($_GET["ns"]), $link, lang('Schema has been dropped.'));
	} else {
		$name = trim($row["name"]);
		$link .= urlencode($name);
		if ($_GET["ns"] == "") {
			query_redirect("CREATE SCHEMA " . idf_escape($name), $link, lang('Schema has been created.'));
		} elseif ($_GET["ns"] != $name) {
			query_redirect("ALTER SCHEMA " . idf_escape($_GET["ns"]) . " RENAME TO " . idf_escape($name), $link, lang('Schema has been altered.')); //! sp_rename in MS SQL
		} else {
			redirect($link);
		}
	}
}

if ($_GET["ns"] != "") {
	page_header(lang('Alter schema') . ": " . h($_GET["ns"]), $error, [lang('Alter schema')]);
} else {
	page_header(lang('Create schema'), $error, [lang('Create schema')]);
}

if (!$row) {
	$row["name"] = $_GET["ns"];
}
?>

<form action="" method="post">
<p><input class="input" name="name" id="name" value="<?php echo h($row["name"]); ?>" autocapitalize="off" autofocus>
<input type="submit" class="button default" value="<?php echo lang('Save'); ?>">
<?php
if ($_GET["ns"] != "") {
	echo "<input type='submit' class='button' name='drop' value='" . lang('Drop') . "'>" . confirm(lang('Drop %s?', $_GET["ns"])) . "\n";
}
?>
<input type="hidden" name="token" value="<?php echo $token; ?>">
</form>
