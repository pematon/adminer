<?php

namespace Adminer;

$TABLE = $_GET["download"];
$fields = fields($TABLE);
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=" . friendly_url("$TABLE-" . implode("_", $_GET["where"])) . "." . friendly_url($_GET["field"]));
$select = [idf_escape($_GET["field"])];
$result = $driver->select($TABLE, $select, [where($_GET, $fields)], $select);
$row = ($result ? $result->fetch_row() : []);
echo $driver->value($row[0], $fields[$_GET["field"]]);
exit; // don't output footer
