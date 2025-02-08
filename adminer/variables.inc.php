<?php

namespace Adminer;

$status = isset($_GET["status"]);
$title = $status ? lang('Status') : lang('Variables');

page_header($title, "", [$title]);

$variables = ($status ? show_status() : show_variables());
if (!$variables) {
	echo "<p class='message'>" . lang('No rows.') . "\n";
} else {
	echo "<table>\n";
	foreach ($variables as $key => $val) {
		echo "<tr>";
		echo "<th><code class='jush-" . $jush . ($status ? "status" : "set") . "'>" . h($key) . "</code>";
		echo "<td>" . h($val);
	}
	echo "</table>\n";
}
