<?php

namespace Adminer;

$PROCEDURE = ($_GET["name"] ? $_GET["name"] : $_GET["procedure"]);
$routine = (isset($_GET["function"]) ? "FUNCTION" : "PROCEDURE");
$row = $_POST;
$row["fields"] = (array) $row["fields"];

if ($_POST && !process_fields($row["fields"]) && !$error) {
	$orig = routine($_GET["procedure"], $routine);
	$temp_name = "$row[name]_adminer_" . uniqid();
	drop_create(
		"DROP $routine " . routine_id($PROCEDURE, $orig),
		create_routine($routine, $row),
		"DROP $routine " . routine_id($row["name"], $row),
		create_routine($routine, ["name" => $temp_name] + $row),
		"DROP $routine " . routine_id($temp_name, $row),
		substr(ME, 0, -1),
		lang('Routine has been dropped.'),
		lang('Routine has been altered.'),
		lang('Routine has been created.'),
		$PROCEDURE,
		$row["name"]
	);
}

if ($PROCEDURE != "") {
	$title = isset($_GET["function"]) ? lang('Alter function') : lang('Alter procedure');
	page_header($title . ": " . h($PROCEDURE), $error, [$title]);
} else {
	$title = isset($_GET["function"]) ? lang('Create function') : lang('Create procedure');
	page_header($title, $error, [$title]);
}

if (!$_POST && $PROCEDURE != "") {
	$row = routine($_GET["procedure"], $routine);
	$row["name"] = $PROCEDURE;
}

$charsets = get_vals("SHOW CHARACTER SET");
sort($charsets);
$routine_languages = routine_languages();
?>

<form action="" method="post" id="form">
<p><?php echo lang('Name'); ?>: <input class="input" name="name" value="<?php echo h($row["name"]); ?>" data-maxlength="64" autocapitalize="off">
<?php echo ($routine_languages ? lang('Language') . ": " . html_select("language", $routine_languages, $row["language"]) . "\n" : ""); ?>
<input type="submit" class="button" value="<?php echo lang('Save'); ?>">
<div class="scrollable">
<table class="nowrap" id="edit-fields">
<?php
edit_fields($row["fields"], $charsets, $routine);
if (isset($_GET["function"])) {
	echo "<tbody><tr>",
		(support("move_col") ? "<th></th>" : ""),
		"<th>", lang('Return type'), "</th>";

	edit_type("returns", $row["returns"], $charsets, [], ($jush == "pgsql" ? ["void", "trigger"] : []));

	echo "<td></td></tr></tbody>\n";
}
?>
</table>
<?php echo script("editFields();"); ?>
</div>
<p><?php textarea("definition", $row["definition"]); ?>
<p>
<input type="submit" class="button" value="<?php echo lang('Save'); ?>">
<?php if ($PROCEDURE != "") { ?><input type="submit" class="button" name="drop" value="<?php echo lang('Drop'); ?>"><?php echo confirm(lang('Drop %s?', $PROCEDURE)); ?><?php } ?>
<input type="hidden" name="token" value="<?php echo $token; ?>">
</form>
