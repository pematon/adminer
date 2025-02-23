<?php
$TABLE = $_GET["check"];
$name = $_GET["name"];
$row = $_POST;

if ($row && !$error) {
	$result = ($name == "" || queries("ALTER TABLE " . table($TABLE) . " DROP CONSTRAINT " . idf_escape($name)));
	if (!$row["drop"] && $result) {
		$result = queries("ALTER TABLE " . table($TABLE) . " ADD" . ($row["name"] != "" ? " CONSTRAINT " . idf_escape($row["name"]) . "" : "") . " CHECK ($row[clause])"); //! SQL injection
	}
	queries_redirect(
		ME . "table=" . urlencode($TABLE),
		($row["drop"] ? lang('Check has been dropped.') : ($name != "" ? lang('Check has been altered.') : lang('Check has been created.'))),
		$result
	);
}

page_header(($name != "" ? lang('Alter check') . ": " . h($name) : lang('Create check')), $error, array("table" => $TABLE));

if (!$row) {
	$checks = check_constraints($TABLE);
	$row = array("name" => $name, "clause" => $checks[$name]);
}
?>

<form action="" method="post">
<p><?php echo lang('Name'); ?>: <input name="name" value="<?php echo h($row["name"]); ?>" data-maxlength="64" autocapitalize="off"><?php echo doc_link(array(
	'sql' => 'create-table-check-constraints.html',
	'mariadb' => 'constraint/',
)); ?>
<p><?php textarea("clause", $row["clause"]); ?>
<p><input type="submit" value="<?php echo lang('Save'); ?>">
<?php if ($name != "") { ?><input type="submit" name="drop" value="<?php echo lang('Drop'); ?>"><?php echo confirm(lang('Drop %s?', $name)); ?><?php } ?>
<input type="hidden" name="token" value="<?php echo $token; ?>">
</form>
