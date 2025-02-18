<?php

namespace Adminer;

$TABLE = $_GET["create"];
$partition_by = [];
foreach (['HASH', 'LINEAR HASH', 'KEY', 'LINEAR KEY', 'RANGE', 'LIST'] as $key) {
	$partition_by[$key] = $key;
}

$referencable_primary = referencable_primary($TABLE);
$foreign_keys = [];
foreach ($referencable_primary as $table_name => $field) {
	$foreign_keys[str_replace("`", "``", $table_name) . "`" . str_replace("`", "``", $field["field"])] = $table_name; // not idf_escape() - used in JS
}

$orig_fields = [];
$table_status = [];
if ($TABLE != "") {
	$orig_fields = fields($TABLE);
	$table_status = table_status($TABLE);
	if (!$table_status) {
		$error = lang('No tables.');
	}
}

$row = $_POST;
$row["fields"] = (array) $row["fields"];
if ($row["auto_increment_col"]) {
	$row["fields"][$row["auto_increment_col"]]["auto_increment"] = true;
}

if ($_POST) {
	set_adminer_settings(["comments" => $_POST["comments"], "defaults" => $_POST["defaults"]]);
}

if ($_POST && !process_fields($row["fields"]) && !$error) {
	if ($_POST["drop"]) {
		queries_redirect(substr(ME, 0, -1), lang('Table has been dropped.'), drop_tables([$TABLE]));
	} else {
		$fields = [];
		$all_fields = [];
		$use_all_fields = false;
		$foreign = [];
		$orig_field = reset($orig_fields);
		$after = " FIRST";

		foreach ($row["fields"] as $key => $field) {
			$foreign_key = $foreign_keys[$field["type"]];
			$type_field = ($foreign_key !== null ? $referencable_primary[$foreign_key] : $field); //! can collide with user defined type
			if ($field["field"] != "") {
				if (!$field["has_default"]) {
					$field["default"] = null;
				}
				if ($key == $row["auto_increment_col"]) {
					$field["auto_increment"] = true;
				}
				$process_field = process_field($field, $type_field);
				$all_fields[] = [$field["orig"], $process_field, $after];
				if (!$orig_field || $process_field != process_field($orig_field, $orig_field)) {
					$fields[] = [$field["orig"], $process_field, $after];
					if ($field["orig"] != "" || $after) {
						$use_all_fields = true;
					}
				}
				if ($foreign_key !== null) {
					$foreign[idf_escape($field["field"])] = ($TABLE != "" && $jush != "sqlite" ? "ADD" : " ") . format_foreign_key([
						'table' => $foreign_keys[$field["type"]],
						'source' => [$field["field"]],
						'target' => [$type_field["field"]],
						'on_delete' => $field["on_delete"],
					]);
				}
				$after = " AFTER " . idf_escape($field["field"]);
			} elseif ($field["orig"] != "") {
				$use_all_fields = true;
				$fields[] = [$field["orig"]];
			}
			if ($field["orig"] != "") {
				$orig_field = next($orig_fields);
				if (!$orig_field) {
					$after = "";
				}
			}
		}

		$partitioning = "";
		if (support("partitioning")) {
			if (isset($partition_by[$row["partition_by"]])) {
				$params = array_filter($row, function ($key) {
					return preg_match('~^partition~', $key);
				}, ARRAY_FILTER_USE_KEY);

				foreach ($params["partition_names"] as $key => $name) {
					if ($name === "") {
						unset($params["partition_names"][$key]);
						unset($params["partition_values"][$key]);
					}
				}

				if ($params != get_partitions_info($TABLE)) {
					$partitions = [];
					if ($params["partition_by"] == 'RANGE' || $params["partition_by"] == 'LIST') {
						foreach ($params["partition_names"] as $key => $name) {
							$value = $params["partition_values"][$key];
							$partitions[] = "\n  PARTITION " . idf_escape($name) . " VALUES " . ($params["partition_by"] == 'RANGE' ? "LESS THAN" : "IN") . ($value != "" ? " ($value)" : " MAXVALUE"); //! SQL injection
						}
					}

					// $params["partition"] can be expression, not only column
					$partitioning .= "\nPARTITION BY {$params["partition_by"]}({$params["partition"]})";
					if ($partitions) {
						$partitioning .= " (" . implode(",", $partitions) . "\n)";
					} elseif ($params["partitions"]) {
						$partitioning .= " PARTITIONS " . (int)$params["partitions"];
					}
				}
			} elseif (preg_match("~partitioned~", $table_status["Create_options"])) {
				$partitioning .= "\nREMOVE PARTITIONING";
			}
		}

		$message = lang('Table has been altered.');
		if ($TABLE == "") {
			cookie("adminer_engine", $row["Engine"]);
			$message = lang('Table has been created.');
		}
		$name = trim($row["name"]);

		queries_redirect(ME . (support("table") ? "table=" : "select=") . urlencode($name), $message, alter_table(
			$TABLE,
			$name,
			($jush == "sqlite" && ($use_all_fields || $foreign) ? $all_fields : $fields),
			$foreign,
			($row["Comment"] != $table_status["Comment"] ? $row["Comment"] : null),
			($row["Engine"] && $row["Engine"] != $table_status["Engine"] ? $row["Engine"] : ""),
			($row["Collation"] && $row["Collation"] != $table_status["Collation"] ? $row["Collation"] : ""),
			($row["Auto_increment"] != "" ? number($row["Auto_increment"]) : ""),
			$partitioning
		));
	}
}

if ($TABLE != "") {
	page_header(lang('Alter table') . ": " . h($TABLE), $error, ["table" => $TABLE, lang('Alter table')]);
} else {
	page_header(lang('Create table'), $error, [lang('Create table')]);
}

if (!$_POST) {
	$row = [
		"Engine" => $_COOKIE["adminer_engine"],
		"fields" => [["field" => "", "type" => (isset($types["int"]) ? "int" : (isset($types["integer"]) ? "integer" : "")), "on_update" => ""]],
		"partition_names" => [""],
	];

	if ($TABLE != "") {
		$row = $table_status;
		$row["name"] = $TABLE;
		$row["fields"] = [];
		if (!$_GET["auto_increment"]) { // don't prefill by original Auto_increment for the sake of performance and not reusing deleted ids
			$row["Auto_increment"] = "";
		}
		foreach ($orig_fields as $field) {
			$field["has_default"] = isset($field["default"]);
			$row["fields"][] = $field;
		}

		if (support("partitioning")) {
			$row += get_partitions_info($TABLE);
			$row["partition_names"][] = "";
			$row["partition_values"][] = "";
		}
	}
}

$keep_collations = [];
if ($row["Collation"]) {
	$keep_collations[$row["Collation"]] = true;
}
foreach ($row["fields"] as $field) {
	if ($field["collation"]) {
		$keep_collations[$field["collation"]] = true;
	}
}

$collations = $adminer->collations(array_keys($keep_collations));

$engines = engines();
// case of engine may differ
foreach ($engines as $engine) {
	if (!strcasecmp($engine, $row["Engine"])) {
		$row["Engine"] = $engine;
		break;
	}
}
?>

<form action="" method="post" id="form">
<?php
	if (support("columns") || $TABLE == "") {
		echo "<p>";
		echo lang('Table name'), ": ";

		echo "<input class='input' name='name' data-maxlength='64' value='", h($row["name"]), "' autocapitalize='off'", (($TABLE == "" && !$_POST) ? " autofocus" : ""), ">";

		if ($engines) {
			echo " <select name='Engine'>", optionlist(["" => "(" . lang('engine') . ")"] + $engines, $row["Engine"]), "</select>", help_script_command("value", true);
		}

		if ($collations && !preg_match("~sqlite|mssql~", $jush)) {
			echo " ", html_select("Collation", ["" => "(" . lang('collation') . ")"] + $collations, $row["Collation"]);
		}

		echo " <input type='submit' class='button default' value='", lang('Save'), "'>";
		echo "</p>";
	}
?>

<?php if (support("columns")) { ?>
<div class="scrollable">
<table id="edit-fields" class="nowrap">
<?php
edit_fields($row["fields"], $collations, "TABLE", $foreign_keys);
?>
</table>
<?php echo script("editFields();"); ?>
</div>
<p>
<?php echo lang('Auto Increment'); ?>: <input type="number" class="input" name="Auto_increment" size="6" value="<?php echo h($row["Auto_increment"]); ?>">
<?php
$comments = ($_POST ? $_POST["comments"] : adminer_setting("comments"));
echo (support("comment")
	? checkbox("comments", 1, $comments, lang('Comment'), "editingCommentsClick(this, true);", "jsonly")
		. ' ' . (preg_match('~\n~', $row["Comment"])
			? "<textarea name='Comment' rows='2' cols='20'" . ($comments ? "" : " class='hidden'") . ">" . h($row["Comment"]) . "</textarea>"
			: '<input name="Comment" value="' . h($row["Comment"]) . '" data-maxlength="' . (min_version(5.5) ? 2048 : 60) . '"' . ($comments ? "" : " class='input hidden'") . '>'
		)
	: '')
;
?>
<p>
<input type="submit" class="button default" value="<?php echo lang('Save'); ?>">
<?php } ?>

<?php if ($TABLE != "") { ?><input type="submit" class="button" name="drop" value="<?php echo lang('Drop'); ?>"><?php echo confirm(lang('Drop %s?', $TABLE)); ?><?php } ?>
<?php
if (support("partitioning")) {
	echo "<div class='field-sets'>\n";
	$partition_table = preg_match('~RANGE|LIST~', $row["partition_by"]);
	print_fieldset_start("partition", lang('Partition by'), "split", (bool)$row["partition_by"]);
	?>
<p>
<?php echo "<select name='partition_by'>" . optionlist(["" => ""] + $partition_by, $row["partition_by"]) . "</select>" . help_script_command("value.replace(/./, 'PARTITION BY \$&')", true) . script("qsl('select').onchange = partitionByChange;"); ?>
(<input class="input" name="partition" value="<?php echo h($row["partition"]); ?>">)
<?php echo lang('Partitions'); ?>: <input type="number" name="partitions" class="input size <?php echo ($partition_table || !$row["partition_by"] ? "hidden" : ""); ?>" value="<?php echo h($row["partitions"]); ?>">
<table id="partition-table"<?php echo ($partition_table ? "" : " class='hidden'"); ?>>
<thead><tr><th><?php echo lang('Partition name'); ?><th><?php echo lang('Values'); ?></thead>
<?php
foreach ($row["partition_names"] as $key => $val) {
	echo '<tr>';
	echo '<td><input class="input" name="partition_names[]" value="' . h($val) . '" autocapitalize="off">';
	echo ($key == count($row["partition_names"]) - 1 ? script("qsl('input').oninput = partitionNameChange;") : '');
	echo '<td><input class="input" name="partition_values[]" value="' . h($row["partition_values"][$key]) . '">';
}
?>
</table>
<?php
	print_fieldset_end("partition");
	echo "</div>\n";
}
?>
<input type="hidden" name="token" value="<?php echo $token; ?>">
</form>
