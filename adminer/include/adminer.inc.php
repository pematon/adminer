<?php

namespace Adminer;

// any method change in this file should be transferred to editor/include/adminer.inc.php and plugins/plugin.php

class Adminer {
	/** @var array operators used in select, null for all operators */
	var $operators = null;
	/** @var string operator for LIKE condition */
	var $operator_like = null;
	/** @var string operator for regular expression condition */
	var $operator_regexp = null;

	/** Name in title and navigation
	* @return string HTML code
	*/
	function name() {
		return "<a id='h1' href='" . h(HOME_URL) . "'>AdminerNeo</a>";
	}

	/** Connection parameters
	* @return array ($server, $username, $password)
	*/
	function credentials() {
		return array(SERVER, $_GET["username"], get_password());
	}

	/** Get SSL connection options
	* @return array array("key" => filename, "cert" => filename, "ca" => filename) or null
	*/
	function connectSsl() {
	}

	/**
	 * Gets a private key used for permanent login.
	 *
	 * @param bool $create
	 *
	 * @return string|false Cryptic string which gets combined with password or false in case of an error.
	 * @throws \Random\RandomException
	 */
	function permanentLogin($create = false) {
		return get_private_key($create);
	}

	/** Return key used to group brute force attacks; behind a reverse proxy, you want to return the last part of X-Forwarded-For
	* @return string
	*/
	function bruteForceKey() {
		return $_SERVER["REMOTE_ADDR"];
	}

	/** Get server name displayed in breadcrumbs
	* @param string
	* @return string HTML code or null
	*/
	function serverName($server) {
		return h($server);
	}

	/** Identifier of selected database
	* @return string
	*/
	function database() {
		// should be used everywhere instead of DB
		return DB;
	}

	/** Get cached list of databases
	* @param bool
	* @return array
	*/
	function databases($flush = true) {
		return get_databases($flush);
	}

	/** Get list of schemas
	* @return array
	*/
	function schemas() {
		return schemas();
	}

	/** Specify limit for waiting on some slow queries like DB list
	* @return float number of seconds
	*/
	function queryTimeout() {
		return 2;
	}

	/** Headers to send before HTML output
	* @return null
	*/
	function headers() {
	}

	/** Get Content Security Policy headers
	* @return array of arrays with directive name in key, allowed sources in value
	*/
	function csp() {
		return csp();
	}

	/** Print HTML code inside <head>
	* @return bool true to link favicon.ico and adminer.css if exists
	*/
	function head() {
		?>
<link rel="stylesheet" type="text/css" href="../vendor/vrana/jush/jush.css">
<?php
		return true;
	}

	/** Get URLs of the CSS files
	* @return array of strings
	*/
	function css() {
		$return = array();
		$filename = "adminer.css";
		if (file_exists($filename)) {
			$return[] = "$filename?v=" . crc32(file_get_contents($filename));
		}
		return $return;
	}

	/** Print login form
	* @return null
	*/
	function loginForm() {
		global $drivers;
		echo "<table class='layout'>\n";
		echo $this->loginFormField('driver', '<tr><th>' . lang('System') . '<td>', html_select("auth[driver]", $drivers, DRIVER, "loginDriver(this);") . "\n");
		echo $this->loginFormField('server', '<tr><th>' . lang('Server') . '<td>', '<input class="input" name="auth[server]" value="' . h(SERVER) . '" title="hostname[:port]" placeholder="localhost" autocapitalize="off">' . "\n");
		echo $this->loginFormField('username', '<tr><th>' . lang('Username') . '<td>', '<input class="input" name="auth[username]" id="username" value="' . h($_GET["username"]) . '" autocomplete="username" autocapitalize="off">' . script("gid('username').form['auth[driver]'].onchange();"));
		echo $this->loginFormField('password', '<tr><th>' . lang('Password') . '<td>', '<input type="password" class="input" name="auth[password]" autocomplete="current-password">' . "\n");
		echo $this->loginFormField('db', '<tr><th>' . lang('Database') . '<td>', '<input class="input" name="auth[db]" value="' . h($_GET["db"]) . '" autocapitalize="off">' . "\n");
		echo "</table>\n";
		echo "<p><input type='submit' class='button' value='" . lang('Login') . "'>\n";
		echo checkbox("auth[permanent]", 1, $_COOKIE["adminer_permanent"], lang('Permanent login')) . "\n";
	}

	/** Get login form field
	* @param string
	* @param string HTML
	* @param string HTML
	* @return string
	*/
	function loginFormField($name, $heading, $value) {
		return $heading . $value;
	}

	/** Authorize the user
	* @param string
	* @param string
	* @return mixed true for success, string for error message, false for unknown error
	*/
	function login($login, $password) {
		if ($password == "") {
			return lang('Adminer does not support accessing a database without a password, <a href="https://www.adminer.org/en/password/"%s>more information</a>.', target_blank());
		}
		return true;
	}

	/** Table caption used in navigation and headings
	* @param array result of SHOW TABLE STATUS
	* @return string HTML code, "" to ignore table
	*/
	function tableName($tableStatus) {
		return h($tableStatus["Name"]);
	}

	/** Field caption used in select and edit
	* @param array single field returned from fields()
	* @param int order of column in select
	* @return string HTML code, "" to ignore field
	*/
	function fieldName($field, $order = 0) {
		return '<span title="' . h($field["full_type"]) . '">' . h($field["field"]) . '</span>';
	}

	/** Print links after select heading
	* @param array result of SHOW TABLE STATUS
	* @param string new item options, NULL for no new item
	*/
	function selectLinks($tableStatus, $set = "") {
		global $jush, $driver;

		echo '<p id="top-links" class="links">';

		$links = [
			"select" => [lang('Select data'), "data"],
		];
		if (support("table") || support("indexes")) {
			$links["table"] = [lang('Show structure'), "structure"];
		}
		if (support("table")) {
			if (is_view($tableStatus)) {
				$links["view"] = [lang('Alter view'), "edit"];
			} else {
				$links["create"] = [lang('Alter table'), "edit"];
			}
		}
		if ($set !== null) {
			$links["edit"] = [lang('New item'), "item-add"];
		}
		$name = $tableStatus["Name"];
		foreach ($links as $key => $val) {
			echo " <a href='", h(ME), "$key=", urlencode($name), ($key == "edit" ? $set : ""), "'", bold(isset($_GET[$key])), ">", icon($val[1]), "$val[0]</a>";
		}

		echo doc_link(array($jush => $driver->tableHelp($name)), "?");
		echo "\n";
	}

	/** Get foreign keys for table
	* @param string
	* @return array same format as foreign_keys()
	*/
	function foreignKeys($table) {
		return foreign_keys($table);
	}

	/** Find backward keys for table
	* @param string
	* @param string
	* @return array $return[$target_table]["keys"][$key_name][$target_column] = $source_column; $return[$target_table]["name"] = $this->tableName($target_table);
	*/
	function backwardKeys($table, $tableName) {
		return array();
	}

	/** Print backward keys for row
	* @param array result of $this->backwardKeys()
	* @param array
	* @return null
	*/
	function backwardKeysPrint($backwardKeys, $row) {
	}

	/**
     * Query printed in select before execution.
     *
	 * @param $query string query to be executed
	 * @param $start float start time of the query
	 * @param $failed bool
	 * @return string
	 */
	function selectQuery($query, $start, $failed = false) {
		global $jush, $driver;

		$supportSql = support("sql");
		$warnings = !$failed ? $driver->warnings() : null;

		$return = "<pre><code class='jush-$jush'>" . h(str_replace("\n", " ", $query)) . "</code></pre>\n";

        $return .= "<p class='links'>";
        if ($supportSql) {
			$return .= "<a href='" . h(ME) . "sql=" . urlencode($query) . "'>" . icon("edit") . lang('Edit') . "</a>";
		}
        if ($warnings) {
			$return .= "<a href='#warnings'>" . lang('Warnings') . "</a>" . script("qsl('a').onclick = partial(toggle, 'warnings');", "");
        }
        $return .= " <span class='time'>(" . format_time($start) . ")</span>";
		$return .= "</p>\n";

		if ($warnings) {
			$return .= "<div id='warnings' class='warnings hidden'>\n$warnings\n</div>\n";
		}

		return $return;
	}

	/** Query printed in SQL command before execution
	* @param string query to be executed
	* @return string escaped query to be printed
	*/
	function sqlCommandQuery($query)
	{
		return shorten_utf8(trim($query), 1000);
	}

	/** Description of a row in a table
	* @param string
	* @return string SQL expression, empty string for no description
	*/
	function rowDescription($table) {
		return "";
	}

	/** Get descriptions of selected data
	* @param array all data to print
	* @param array
	* @return array
	*/
	function rowDescriptions($rows, $foreignKeys) {
		return $rows;
	}

	/** Get a link to use in select table
	* @param string raw value of the field
	* @param array single field returned from fields()
	* @return string or null to create the default link
	*/
	function selectLink($val, $field) {
	}

	/** Value printed in select table
	* @param string HTML-escaped value to print
	* @param string link to foreign key
	* @param array single field returned from fields()
	* @param array original value before applying editVal() and escaping
	* @return string
	*/
	function selectVal($val, $link, $field, $original) {
		$return = ($val === null ? "<i>NULL</i>" : (preg_match("~char|binary|boolean~", $field["type"]) && !preg_match("~var~", $field["type"]) ? "<code>$val</code>" : $val));
		if ($field && preg_match('~blob|bytea|raw|file~', $field["type"]) && !is_utf8($val)) {
			$return = "<i>" . lang('%d byte(s)', strlen($original)) . "</i>";
		}
		if ($field && preg_match('~json~', $field["type"])) {
			$return = "<code class='jush-js'>$return</code>";
		}
		return ($link ? "<a href='" . h($link) . "'" . (is_web_url($link) ? target_blank() : "") . ">$return</a>" : $return);
	}

	/** Value conversion used in select and edit
	* @param string
	* @param array single field returned from fields()
	* @return string
	*/
	function editVal($val, $field) {
		// Format Elasticsearch boolean value, but do not touch PostgreSQL boolean that use string value 't' or 'f'.
		if ($field && $field["type"] == "boolean" && is_bool($val)) {
			return $val ? "true" : "false";
		}

		return $val;
	}

	/** Print table structure in tabular format
	* @param array data about individual fields
	* @return null
	*/
	function tableStructurePrint($fields) {
		echo "<div class='scrollable'>\n";
		echo "<table class='nowrap'>\n";
		echo "<thead><tr><th>" . lang('Column') . "<td>" . lang('Type') . (support("comment") ? "<td>" . lang('Comment') : "") . "</thead>\n";
		foreach ($fields as $field) {
			echo "<tr" . odd() . "><th>" . h($field["field"]);
			echo "<td><span title='" . h($field["collation"]) . "'>" . h($field["full_type"]) . "</span>";
			echo ($field["null"] ? " <i>NULL</i>" : "");
			echo ($field["auto_increment"] ? " <i>" . lang('Auto Increment') . "</i>" : "");
			echo (isset($field["default"]) ? " <span title='" . lang('Default value') . "'>[<b>" . h($field["default"]) . "</b>]</span>" : "");
			echo (support("comment") ? "<td>" . h($field["comment"]) : "");
			echo "\n";
		}
		echo "</table>\n";
		echo "</div>\n";
	}

	function tablePartitionsPrint($partition_info) {
		$showList = $partition_info["partition_by"] == "RANGE" || $partition_info["partition_by"] == "LIST";

		echo "<p>";
		echo "<code>{$partition_info["partition_by"]} ({$partition_info["partition"]})</code>";
		if (!$showList) {
			echo " " . lang('Partitions') . ": " . h($partition_info["partitions"]);
		}
		echo "</p>";

		if ($showList) {
			echo "<table>\n";
			echo "<thead><tr><th>" . lang('Partition') . "</th><td>" . lang('Values') . "</td></tr></thead>\n";

			foreach ($partition_info["partition_names"] as $key => $name) {
				echo "<tr><th>" . h($name) . "</th><td>" . h($partition_info["partition_values"][$key]) . "\n";
			}

			echo "</table>\n";
		}
	}

	/** Print list of indexes on table in tabular format
	* @param array data about all indexes on a table
	* @return null
	*/
	function tableIndexesPrint($indexes) {
		echo "<table>\n";
		echo "<thead><tr><th>" . lang('Type') . "</th><td>" . lang('Column (length)') . "</td></tr></thead>\n";

		foreach ($indexes as $name => $index) {
			ksort($index["columns"]); // enforce correct columns order
			$print = [];

			foreach ($index["columns"] as $key => $val) {
				$print[] = "<i>" . h($val) . "</i>"
					. ($index["lengths"][$key] ? "(" . $index["lengths"][$key] . ")" : "")
					. ($index["descs"][$key] ? " DESC" : "")
				;
			}
			echo "<tr title='" . h($name) . "'><th>$index[type]<td>" . implode(", ", $print) . "\n";
		}

		echo "</table>\n";
	}

	/**
	 * Prints columns box in select filter.
	 *
	 * @param array $select result of selectColumnsProcess()[0]
	 * @param array $columns selectable columns
	 */
	function selectColumnsPrint(array $select, array $columns) {
		global $functions, $grouping;

		print_fieldset("select", lang('Select'), $select, true);

		$_GET["columns"][""] = [];
		$i = 0;

		foreach ($_GET["columns"] as $key => $val) {
			if ($key != "" && ($val["col"] ?? null) == "") continue;

			$column = select_input(
				"name='columns[$i][col]'",
				$columns,
				$val["col"] ?? null,
				$key !== "" ? "selectFieldChange" : "selectAddRow"
			);

			echo "<div ", ($key != "" ? "" : "class='no-sort'"), ">",
				icon("handle", "handle jsonly");

			if ($functions || $grouping) {
				echo "<select name='columns[$i][fun]'>",
					optionlist([-1 => ""] + array_filter([lang('Functions') => $functions, lang('Aggregation') => $grouping]), $val["fun"]),
					"</select>",
					help_script_command("value && value.replace(/ |\$/, '(') + ')'", true),
					script("qsl('select').onchange = (event) => { " . ($key !== "" ? "" : " qsl('select, input:not(.remove)', event.target.parentNode).onchange();") . " };", ""),
					"($column)";
			} else {
				echo $column;
			}

			echo " <button class='button light remove jsonly' title='" . h(lang('Remove')) . "'>", icon("remove"), "</button>",
				script("qsl('#fieldset-select .remove').onclick = selectRemoveRow;", ""),
				"</div>\n";

			$i++;
		}

		echo "</div>", script("initSortable('#fieldset-select');"), "</fieldset>\n";
	}

	/**
	 * Prints search box in select.
	 *
	 * @param array $where result of selectSearchProcess()
	 * @param array $columns selectable columns
	 */
	function selectSearchPrint(array $where, array $columns, array $indexes) {
		print_fieldset("search", lang('Search'), $where);

		foreach ($indexes as $i => $index) {
			if ($index["type"] == "FULLTEXT") {
				echo "<div>(<i>" . implode("</i>, <i>", array_map('Adminer\h', $index["columns"])) . "</i>) AGAINST";
				echo "<input type='search' class='input' name='fulltext[$i]' value='" . h($_GET["fulltext"][$i]) . "'>";
				echo script("qsl('input').oninput = selectFieldChange;", "");
				echo checkbox("boolean[$i]", 1, isset($_GET["boolean"][$i]), "BOOL");
				echo "</div>\n";
			}
		}

		$change_next = "this.parentNode.firstChild.onchange();";
		foreach (array_merge((array) $_GET["where"], array(array())) as $i => $val) {
			if (!$val || ("$val[col]$val[val]" != "" && in_array($val["op"], $this->operators))) {
				echo "<div>",
					select_input(
						" name='where[$i][col]'",
						$columns,
						$val["col"],
						($val ? "selectFieldChange" : "selectAddRow"),
						"(" . lang('anywhere') . ")"
					),
					html_select("where[$i][op]", $this->operators, $val["op"], $change_next),
					"<input type='search' class='input' name='where[$i][val]' value='" . h($val["val"]) . "'>",
					script("mixin(qsl('input'), {oninput: function () { $change_next }, onkeydown: selectSearchKeydown, onsearch: selectSearchSearch});", ""),
					" <button class='button light remove jsonly' title='" . h(lang('Remove')) . "'>", icon("remove"), "</button>",
					script('qsl("#fieldset-search .remove").onclick = selectRemoveRow;', ""),
					"</div>\n";
			}
		}

		echo "</div></fieldset>\n";
	}

	/**
	 * Prints order box in select filter.
	 *
	 * @param array $order result of selectOrderProcess()
	 * @param array $columns selectable columns
	 */
	function selectOrderPrint(array $order, array $columns, array $indexes) {
		print_fieldset("sort", lang('Sort'), $order, true);

		$_GET["order"][""] = "";
		$i = 0;

		foreach ((array) $_GET["order"] as $key => $val) {
			if ($key != "" && $val == "") continue;

			echo "<div ", ($key != "" ? "" : "class='no-sort'"), ">",
				icon("handle", "handle jsonly"),
				select_input("name='order[$i]'", $columns, $val, $key !== "" ? "selectFieldChange" : "selectAddRow"),
				" ", checkbox("desc[$i]", 1, isset($_GET["desc"][$key]), lang('descending')),
				" <button class='button light remove jsonly' title='" . h(lang('Remove')), "'>", icon("remove"), "</button>",
				script('qsl("#fieldset-sort .remove").onclick = selectRemoveRow;', ""),
				"</div>\n";

			$i++;
		}

		echo "</div>", script("initSortable('#fieldset-sort');"), "</fieldset>\n";
	}

	/** Print limit box in select
	* @param string result of selectLimitProcess()
	* @return null
	*/
	function selectLimitPrint($limit) {
		echo "<fieldset><legend>" . lang('Limit') . "</legend><div>"; // <div> for easy styling
		echo "<input type='number' name='limit' class='input size' value='" . h($limit) . "'>";
		echo script("qsl('input').oninput = selectFieldChange;", "");
		echo "</div></fieldset>\n";
	}

	/** Print text length box in select
	* @param string result of selectLengthProcess()
	* @return null
	*/
	function selectLengthPrint($text_length) {
		if ($text_length !== null) {
			echo "<fieldset><legend>" . lang('Text length') . "</legend><div>";
			echo "<input type='number' name='text_length' class='input size' value='" . h($text_length) . "'>";
			echo "</div></fieldset>\n";
		}
	}

	/** Print action box in select
	* @param array
	* @return null
	*/
	function selectActionPrint($indexes) {
		echo "<fieldset><legend>" . lang('Action') . "</legend><div>";
		echo "<input type='submit' class='button' value='" . lang('Select') . "'>";
		echo " <span id='noindex' title='" . lang('Full table scan') . "'></span>";
		echo "<script" . nonce() . ">\n";
		echo "var indexColumns = ";
		$columns = array();
		foreach ($indexes as $index) {
			$current_key = reset($index["columns"]);
			if ($index["type"] != "FULLTEXT" && $current_key) {
				$columns[$current_key] = 1;
			}
		}
		$columns[""] = 1;
		foreach ($columns as $key => $val) {
			json_row($key);
		}
		echo ";\n";
		echo "selectFieldChange.call(gid('form')['select']);\n";
		echo "</script>\n";
		echo "</div></fieldset>\n";
	}

	/** Print command box in select
	* @return bool whether to print default commands
	*/
	function selectCommandPrint() {
		return !information_schema(DB);
	}

	/** Print import box in select
	* @return bool whether to print default import
	*/
	function selectImportPrint() {
		return !information_schema(DB);
	}

	/** Print extra text in the end of a select form
	* @param array fields holding e-mails
	* @param array selectable columns
	* @return null
	*/
	function selectEmailPrint($emailFields, $columns) {
	}

	/** Process columns box in select
	* @param array selectable columns
	* @param array
	* @return array (array(select_expressions), array(group_expressions))
	*/
	function selectColumnsProcess($columns, $indexes) {
		global $functions, $grouping;
		$select = array(); // select expressions, empty for *
		$group = array(); // expressions without aggregation - will be used for GROUP BY if an aggregation function is used
		foreach ((array) $_GET["columns"] as $key => $val) {
			if ($val["fun"] == "count" || ($val["col"] != "" && (!$val["fun"] || in_array($val["fun"], $functions) || in_array($val["fun"], $grouping)))) {
				$select[$key] = apply_sql_function($val["fun"], ($val["col"] != "" ? idf_escape($val["col"]) : "*"));
				if (!in_array($val["fun"], $grouping)) {
					$group[] = $select[$key];
				}
			}
		}
		return array($select, $group);
	}

	/** Process search box in select
	* @param array
	* @param array
	* @return array expressions to join by AND
	*/
	function selectSearchProcess($fields, $indexes) {
		global $driver;

		$return = [];

		foreach ($indexes as $i => $index) {
			if ($index["type"] == "FULLTEXT" && $_GET["fulltext"][$i] != "") {
				$return[] = "MATCH (" . implode(", ", array_map('Adminer\idf_escape', $index["columns"])) . ") AGAINST (" . q($_GET["fulltext"][$i]) . (isset($_GET["boolean"][$i]) ? " IN BOOLEAN MODE" : "") . ")";
			}
		}

		foreach ((array) $_GET["where"] as $where) {
			$col = $where["col"];
			$op = $where["op"];
			$val = $where["val"];

			if ("$col$val" != "" && in_array($op, $this->operators)) {
				$prefix = "";
				$cond = " $op";

				if (preg_match('~IN$~', $op)) {
					$in = process_length($val);
					$cond .= " " . ($in != "" ? $in : "(NULL)");
				} elseif ($op == "SQL") {
					$cond = " $val"; // SQL injection
				} elseif ($op == "LIKE %%") {
					$cond = " LIKE " . $this->processInput($fields[$col] ?? null, "%$val%");
				} elseif ($op == "ILIKE %%") {
					$cond = " ILIKE " . $this->processInput($fields[$col] ?? null, "%$val%");
				} elseif ($op == "FIND_IN_SET") {
					$prefix = "$op(" . q($val) . ", ";
					$cond = ")";
				} elseif (!preg_match('~NULL$~', $op)) {
					$cond .= " " . $this->processInput($fields[$col] ?? null, $val);
				}

				if ($col != "") {
					$return[] = $prefix . $driver->convertSearch(idf_escape($col), $where, $fields[$col]) . $cond;
				} else {
					// find anywhere
					$cols = array();
					foreach ($fields as $name => $field) {
						if (isset($field["privileges"]["where"])
                            && (preg_match('~^[-\d.' . (preg_match('~IN$~', $op) ? ',' : '') . ']+$~', $val) || !preg_match('~' . number_type() . '|bit~', $field["type"]))
							&& (!preg_match("~[\x80-\xFF]~", $val) || preg_match('~char|text|enum|set~', $field["type"]))
							&& (!preg_match('~date|timestamp~', $field["type"]) || preg_match('~^\d+-\d+-\d+~', $val))
							&& (!preg_match('~^elastic~', DRIVER) || $field["type"] != "boolean" || preg_match('~true|false~', $val)) // Elasticsearch needs boolean value properly formatted.
							&& (!preg_match('~^elastic~', DRIVER) || strpos($op, "regexp") === false || preg_match('~text|keyword~', $field["type"])) // Elasticsearch can use regexp only on text and keyword fields.
						) {
							$cols[] = $prefix . $driver->convertSearch(idf_escape($name), $where, $field) . $cond;
						}
					}
					$return[] = ($cols ? "(" . implode(" OR ", $cols) . ")" : "1 = 0");
				}
			}
		}

		return $return;
	}

	/** Process order box in select
	* @param array
	* @param array
	* @return array expressions to join by comma
	*/
	function selectOrderProcess($fields, $indexes) {
		$return = array();
		foreach ((array) $_GET["order"] as $key => $val) {
			if ($val != "") {
				$return[] = (preg_match('~^((COUNT\(DISTINCT |[A-Z0-9_]+\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\)|COUNT\(\*\))$~', $val) ? $val : idf_escape($val)) //! MS SQL uses []
					. (isset($_GET["desc"][$key]) ? " DESC" : "")
				;
			}
		}
		return $return;
	}

	/** Process limit box in select
	* @return string expression to use in LIMIT, will be escaped
	*/
	function selectLimitProcess() {
		return (isset($_GET["limit"]) ? $_GET["limit"] : "50");
	}

	/** Process length box in select
	* @return string number of characters to shorten texts, will be escaped
	*/
	function selectLengthProcess() {
		return (isset($_GET["text_length"]) ? $_GET["text_length"] : "100");
	}

	/** Process extras in select form
	* @param array AND conditions
	* @param array
	* @return bool true if processed, false to process other parts of form
	*/
	function selectEmailProcess($where, $foreignKeys) {
		return false;
	}

	/** Build SQL query used in select
	* @param array result of selectColumnsProcess()[0]
	* @param array result of selectSearchProcess()
	* @param array result of selectColumnsProcess()[1]
	* @param array result of selectOrderProcess()
	* @param int result of selectLimitProcess()
	* @param int index of page starting at zero
	* @return string empty string to use default query
	*/
	function selectQueryBuild($select, $where, $group, $order, $limit, $page) {
		return "";
	}

	/** Query printed after execution in the message
	* @param string executed query
	* @param string elapsed time
	* @param bool
	* @return string
	*/
	function messageQuery($query, $time, $failed = false) {
		global $jush, $driver;

		restart_session();

		$history = &get_session("queries");
		if (!isset($history[$_GET["db"]])) {
			$history[$_GET["db"]] = [];
		}

		if (strlen($query) > 1e6) {
			$query = preg_replace('~[\x80-\xFF]+$~', '', substr($query, 0, 1e6)) . "\n…"; // [\x80-\xFF] - valid UTF-8, \n - can end by one-line comment
		}

		$history[$_GET["db"]][] = [$query, time(), $time]; // not DB - $_GET["db"] is changed in database.inc.php //! respect $_GET["ns"]

		$supportSql = support("sql");
		$warnings = !$failed ? $driver->warnings() : null;

        $sqlId = "sql-" . count($history[$_GET["db"]]);
		$warningsId = "warnings-" . count($history[$_GET["db"]]);

		$return = " ";
		if ($warnings) {
			$return .= "<a href='#$warningsId' class='toggle'>" . lang('Warnings') . "</a>, ";
		}
		$return .= "<a href='#$sqlId' class='toggle'>" . lang('SQL command') . "</a>";
		$return .= " <span class='time'>" . @date("H:i:s") . "</span>\n"; // @ - time zone may be not set

		if ($warnings) {
			$return .= "<div id='$warningsId' class='warnings hidden'>\n$warnings</div>\n";
		}

		$return .= "<div id='$sqlId' class='hidden'>\n";
        $return .= "<pre><code class='jush-$jush'>" . shorten_utf8($query, 1000) . "</code></pre>\n";

        $return .= "<p class='links'>";
		if ($supportSql) {
			$return .= "<a href='" . h(str_replace("db=" . urlencode(DB), "db=" . urlencode($_GET["db"]), ME) . 'sql=&history=' . (count($history[$_GET["db"]]) - 1)) . "'>" . icon("edit") . lang('Edit') . "</a>";
		}
		if ($time) {
			$return .= " <span class='time'>($time)</span>";
		}
        $return .= "</p>\n";
		$return .= "</div>\n";

        return $return;
	}

	/** Print before edit form
	* @param string
	* @param array
	* @param mixed
	* @param bool
	* @return null
	*/
	function editRowPrint($table, $fields, $row, $update) {
	}

	/** Functions displayed in edit form
	* @param array single field from fields()
	* @return array
	*/
	function editFunctions($field) {
		global $edit_functions;
		$return = ($field["null"] ? "NULL/" : "");
		$update = isset($_GET["select"]) || where($_GET);
		foreach ($edit_functions as $key => $functions) {
			if (!$key || (!isset($_GET["call"]) && $update)) { // relative functions
				foreach ($functions as $pattern => $val) {
					if (!$pattern || preg_match("~$pattern~", $field["type"])) {
						$return .= "/$val";
					}
				}
			}
			if ($key && !preg_match('~set|blob|bytea|raw|file|bool~', $field["type"])) {
				$return .= "/SQL";
			}
		}
		if ($field["auto_increment"] && !$update) {
			$return = lang('Auto Increment');
		}
		return explode("/", $return);
	}

	/** Get options to display edit field
	* @param string table name
	* @param array single field from fields()
	* @param string attributes to use inside the tag
	* @param string
	* @return string custom input field or empty string for default
	*/
	function editInput($table, $field, $attrs, $value, $function) {
		if ($field["type"] == "enum") {
			return (isset($_GET["select"]) ? "<label><input type='radio'$attrs value='-1' checked><i>" . lang('original') . "</i></label> " : "")
				. ($field["null"] ? "<label><input type='radio'$attrs value=''" . ($value !== null || isset($_GET["select"]) ? "" : " checked") . "><i>NULL</i></label> " : "")
				. enum_input("radio", $attrs, $field, $value, 0) // 0 - empty
			;
		}
		return "";
	}

	/** Get hint for edit field
	* @param string table name
	* @param array single field from fields()
	* @param string
	* @return string
	*/
	function editHint($table, $field, $value) {
		return "";
	}

	/** Process sent input
	* @param ?array single field from fields()
	* @param string
	* @param string
	* @return string expression to use in a query
	*/
	function processInput(?array $field, $value, $function = "") {
		if ($function == "SQL") {
			return $value; //! SQL injection
		}
		if (!$field) {
			return q($value);
		}

		$name = $field["field"];
		$return = q($value);
		if (preg_match('~^(now|getdate|uuid)$~', $function)) {
			$return = "$function()";
		} elseif (preg_match('~^current_(date|timestamp)$~', $function)) {
			$return = $function;
		} elseif (preg_match('~^([+-]|\|\|)$~', $function)) {
			$return = idf_escape($name) . " $function $return";
		} elseif (preg_match('~^[+-] interval$~', $function)) {
			$return = idf_escape($name) . " $function " . (preg_match("~^(\\d+|'[0-9.: -]') [A-Z_]+\$~i", $value) ? $value : $return);
		} elseif (preg_match('~^(addtime|subtime|concat)$~', $function)) {
			$return = "$function(" . idf_escape($name) . ", $return)";
		} elseif (preg_match('~^(md5|sha1|password|encrypt)$~', $function)) {
			$return = "$function($return)";
		}
		return unconvert_field($field, $return);
	}

	/** Returns export output options
	* @return array
	*/
	function dumpOutput() {
		$return = array('file' => lang('save'), 'text' => lang('open'));
		if (function_exists('gzencode')) {
			$return['gz'] = 'gzip';
		}

		return $return;
	}

	/** Returns export format options
	* @return array empty to disable export
	*/
	function dumpFormat() {
		return array('sql' => 'SQL', 'csv' => 'CSV,', 'csv;' => 'CSV;', 'tsv' => 'TSV');
	}

	/** Export database structure
	* @param string
	* @return null prints data
	*/
	function dumpDatabase($db) {
	}

	/** Export table structure
	* @param string
	* @param string
	* @param int 0 table, 1 view, 2 temporary view table
	* @return null prints data
	*/
	function dumpTable($table, $style, $is_view = 0) {
		if ($_POST["format"] != "sql") {
			echo "\xef\xbb\xbf"; // UTF-8 byte order mark
			if ($style) {
				dump_csv(array_keys(fields($table)));
			}
		} else {
			if ($is_view == 2) {
				$fields = array();
				foreach (fields($table) as $name => $field) {
					$fields[] = idf_escape($name) . " $field[full_type]";
				}
				$create = "CREATE TABLE " . table($table) . " (" . implode(", ", $fields) . ")";
			} else {
				$create = create_sql($table, $_POST["auto_increment"], $style);
			}
			set_utf8mb4($create);
			if ($style && $create) {
				if ($style == "DROP+CREATE" || $is_view == 1) {
					echo "DROP " . ($is_view == 2 ? "VIEW" : "TABLE") . " IF EXISTS " . table($table) . ";\n";
				}
				if ($is_view == 1) {
					$create = remove_definer($create);
				}
				echo "$create;\n\n";
			}
		}
	}

	/** Export table data
	* @param string
	* @param string
	* @param string
	* @return null prints data
	*/
	function dumpData($table, $style, $query) {
		global $connection, $jush;
		$max_packet = ($jush == "sqlite" ? 0 : 1048576); // default, minimum is 1024
		if ($style) {
			if ($_POST["format"] == "sql") {
				if ($style == "TRUNCATE+INSERT") {
					echo truncate_sql($table) . ";\n";
				}
				$fields = fields($table);
			}
			$result = $connection->query($query, 1); // 1 - MYSQLI_USE_RESULT //! enum and set as numbers
			if ($result) {
				$insert = "";
				$buffer = "";
				$keys = array();
				$generatedKeys = array();
				$suffix = "";
				$fetch_function = ($table != '' ? 'fetch_assoc' : 'fetch_row');
				while ($row = $result->$fetch_function()) {
					if (!$keys) {
						$values = array();
						foreach ($row as $val) {
							$field = $result->fetch_field();
							if (!empty($fields[$field->name]['generated'])) {
								$generatedKeys[$field->name] = true;
								continue;
                            }
							$keys[] = $field->name;
							$key = idf_escape($field->name);
							$values[] = "$key = VALUES($key)";
						}
						$suffix = ($style == "INSERT+UPDATE" ? "\nON DUPLICATE KEY UPDATE " . implode(", ", $values) : "") . ";\n";
					}
					if ($_POST["format"] != "sql") {
						if ($style == "table") {
							dump_csv($keys);
							$style = "INSERT";
						}
						dump_csv($row);
					} else {
						if (!$insert) {
							$insert = "INSERT INTO " . table($table) . " (" . implode(", ", array_map('Adminer\idf_escape', $keys)) . ") VALUES";
						}
						foreach ($row as $key => $val) {
							if (isset($generatedKeys[$key])) {
								unset($row[$key]);
								continue;
                            }
							$field = $fields[$key];
							$row[$key] = ($val !== null
								? unconvert_field($field, preg_match(number_type(), $field["type"]) && !preg_match('~\[~', $field["full_type"]) && is_numeric($val) ? $val : q(($val === false ? 0 : $val)))
								: "NULL"
							);
						}
						$s = ($max_packet ? "\n" : " ") . "(" . implode(",\t", $row) . ")";
						if (!$buffer) {
							$buffer = $insert . $s;
						} elseif (strlen($buffer) + 4 + strlen($s) + strlen($suffix) < $max_packet) { // 4 - length specification
							$buffer .= ",$s";
						} else {
							echo $buffer . $suffix;
							$buffer = $insert . $s;
						}
					}
				}
				if ($buffer) {
					echo $buffer . $suffix;
				}
			} elseif ($_POST["format"] == "sql") {
				echo "-- " . str_replace("\n", " ", $connection->error) . "\n";
			}
		}
	}

	/** Set export filename
	* @param string
	* @return string filename without extension
	*/
	function dumpFilename($identifier) {
		return friendly_url($identifier != "" ? $identifier : (SERVER != "" ? SERVER : "localhost"));
	}

	/** Send headers for export
	* @param string
	* @param bool
	* @return string extension
	*/
	function dumpHeaders($identifier, $multi_table = false) {
		$output = $_POST["output"];
		$ext = (preg_match('~sql~', $_POST["format"]) ? "sql" : ($multi_table ? "tar" : "csv")); // multiple CSV packed to TAR
		header("Content-Type: " .
			($output == "gz" ? "application/x-gzip" :
			($ext == "tar" ? "application/x-tar" :
			($ext == "sql" || $output != "file" ? "text/plain" : "text/csv") . "; charset=utf-8"
		)));
		if ($output == "gz") {
			ob_start('ob_gzencode', 1e6);
		}
		return $ext;
	}

	/**
	 * Gets the path of the file for webserver load.
	 *
	 * @return string Path of the sql import file.
	 */
	function importServerPath() {
		return "adminer.sql";
	}

	/**
	 * Prints homepage.
	 *
	 * @return bool Whether to print default homepage.
	 */
	function homepage() {
		echo "<p id='top-links' class='links'>\n";

		if ($_GET["ns"] == "" && support("database")) {
			echo '<a href="', h(ME), 'database=">', icon("edit"), lang('Alter database'), "</a>\n";
		}
		if (support("scheme")) {
			echo "<a href='", h(ME), "scheme='>";
			if ($_GET["ns"] != "") {
				echo icon("edit"), lang('Alter schema');
			} else {
				echo icon("database-add"), lang('Create schema');
			}
			echo "</a>\n";
		}
		if ($_GET["ns"] !== "") {
			echo '<a href="', h(ME), 'schema=">', icon("schema"), lang('Database schema'), "</a>\n";
		}
		if (support("privileges")) {
			echo "<a href='", h(ME), "privileges='>", icon("users"), lang('Privileges'), "</a>\n";
		}

		echo "</p>\n";

		return true;
	}

	/** Prints navigation after Adminer title
	* @param string can be "auth" if there is no database connection, "db" if there is no database selected, "ns" with invalid schema
	* @return null
	*/
	function navigation($missing) {
		global $VERSION, $jush, $drivers, $connection;
?>

<h1>
	<?php echo $this->name(); ?>

	<?php if ($missing != "auth"): ?>
		<span class="version">
			<?php echo $VERSION; ?>
			<a href="https://github.com/adminerneo/adminerneo/releases"<?php echo target_blank(); ?> id="version">
				<?php echo (version_compare($VERSION, $_COOKIE["adminer_version"]) < 0 ? h($_COOKIE["adminer_version"]) : ""); ?>
			</a>
		</span>
		<?php
		if (!isset($_COOKIE["adminer_version"]) && version_compare(PHP_VERSION, 7.1) >= 0) {
			echo script("verifyVersion('" . js_escape(ME) . "', '" . get_token() . "');");
		}
		?>
    <?php endif; ?>
</h1>

<?php
		if ($missing == "auth") {
			$output = "";
			foreach ((array) $_SESSION["pwds"] as $vendor => $servers) {
				foreach ($servers as $server => $usernames) {
					foreach ($usernames as $username => $password) {
						if ($password !== null) {
							$dbs = $_SESSION["db"][$vendor][$server][$username];
							foreach (($dbs ? array_keys($dbs) : array("")) as $db) {
								$output .= "<li><a href='" . h(auth_url($vendor, $server, $username, $db)) . "'>"
									. h($drivers[$vendor])
									. ($username != "" || $server != "" ? " - " : "")
									. h($username)
									. ($username != "" && $server != "" ? "@" : "")
									. ($server != "" ? h($this->serverName($server)) : "")
									. ($db != "" ? h(" - $db") : "")
									. "</a></li>\n";
							}
						}
					}
				}
			}
			if ($output) {
				echo "<ul id='logins'>\n$output</ul>\n" . script("mixin(gid('logins'), {onmouseover: menuOver, onmouseout: menuOut});");
			}
		} else {
			$tables = array();
			if ($_GET["ns"] !== "" && !$missing && DB != "") {
				$connection->select_db(DB);
				$tables = table_status('', true);
			}
			echo script_src("../vendor/vrana/jush/modules/jush.js");
			echo script_src("../vendor/vrana/jush/modules/jush-textarea.js");
			echo script_src("../vendor/vrana/jush/modules/jush-txt.js");
			echo script_src("../vendor/vrana/jush/modules/jush-js.js");
			if (support("sql")) {
				echo script_src("../vendor/vrana/jush/modules/jush-$jush.js");
				?>
<script<?php echo nonce(); ?>>
<?php
				if ($tables) {
					$links = array();
					foreach ($tables as $table => $type) {
						$links[] = preg_quote($table, '/');
					}
					echo "var jushLinks = { $jush: [ '" . js_escape(ME) . (support("table") ? "table=" : "select=") . "\$&', /\\b(" . implode("|", $links) . ")\\b/g ] };\n";
					foreach (array("bac", "bra", "sqlite_quo", "mssql_bra") as $val) {
						echo "jushLinks.$val = jushLinks.$jush;\n";
					}
				}
				$server_info = $connection->server_info;
				?>
bodyLoad('<?php echo (is_object($connection) ? preg_replace('~^(\d\.?\d).*~s', '\1', $server_info) : ""); ?>'<?php echo (preg_match('~MariaDB~', $server_info) ? ", true" : ""); ?>);
</script>
<?php
			}

			$this->databasesPrint($missing);

			$actions = [];
			if (DB == "" || !$missing) {
				if (support("sql")) {
					$actions[] = "<a href='" . h(ME) . "sql='" . bold(isset($_GET["sql"]) && !isset($_GET["import"])) . ">" . icon("command") . lang('SQL command') . "</a>";
					$actions[] = "<a href='" . h(ME) . "import='" . bold(isset($_GET["import"])) . ">" . icon("import") . lang('Import') . "</a>";
				}
				if (support("dump")) {
					$actions[] = "<a href='" . h(ME) . "dump=" . urlencode($_GET["table"] ?? $_GET["select"]) . "' id='dump'" . bold(isset($_GET["dump"])) . ">" . icon("export") . lang('Export') . "</a>";
				}
			}
			if ($_GET["ns"] !== "" && !$missing && DB != "") {
				$actions[] = '<a href="' . h(ME) . 'create="' . bold($_GET["create"] === "") . ">" . icon("table-add") . lang('Create table') . "</a>\n";
			}
			if ($actions) {
				echo "<p class='links'>" . implode("\n", $actions) . "</p>";
			}

			if ($_GET["ns"] !== "" && !$missing && DB != "") {
				if ($tables) {
					$this->printTablesFilter();
					$this->tablesPrint($tables);
				} else {
					echo "<p class='message'>" . lang('No tables.') . "</p>\n";
				}
			}
		}
	}

	/**
	 * Prints databases select in menu.
	 *
	 * @param $missing string
	 * @return null
	 */
	function databasesPrint($missing) {
		global $adminer, $connection;

		$databases = $this->databases();
		if (DB && $databases && !in_array(DB, $databases)) {
			array_unshift($databases, DB);
		}

		echo "<form action=''><p id='dbs'>";
		hidden_fields_get();

		if ($databases) {
			echo "<select id='database-select' name='db'>" . optionlist(["" => lang('Database')] + $databases, DB) . "</select>"
				. script("mixin(gid('database-select'), {onmousedown: dbMouseDown, onchange: dbChange});");
		} else {
			echo "<input id='database-select' class='input' name='db' value='" . h(DB) . "' autocapitalize='off'>\n";
		}
		echo "<input type='submit' value='" . lang('Use') . "'" . ($databases ? " class='button hidden'" : "") . ">\n";

		if (support("scheme") && $missing != "db" && DB != "" && $connection->select_db(DB)) {
			echo "<br><select id='scheme-select' name='ns'>" . optionlist(["" => lang('Schema')] + $adminer->schemas(), $_GET["ns"]) . "</select>"
				. script("mixin(gid('scheme-select'), {onmousedown: dbMouseDown, onchange: dbChange});");

			if ($_GET["ns"] != "") {
				set_schema($_GET["ns"]);
			}
		}

		foreach (["import", "sql", "schema", "dump", "privileges"] as $val) {
			if (isset($_GET[$val])) {
				echo "<input type='hidden' name='$val' value=''>";
				break;
			}
		}

		echo "</p></form>\n";

		return null;
	}

	function printTablesFilter()
	{
		global $adminer;

		echo "<div class='tables-filter jsonly'>"
			. "<input id='tables-filter' class='input' autocomplete='off' placeholder='" . lang('Table') . "'>"
			. script("initTablesFilter(" . json_encode($adminer->database()) . ");")
			. "</div>\n";
	}

	/**
	 * Prints table list in menu.
	 *
	 * @param array $tables Result of table_status('', true)
	 * @return null
	 */
	function tablesPrint(array $tables) {
		echo "<ul id='tables'>" . script("mixin(gid('tables'), {onmouseover: menuOver, onmouseout: menuOut});");

		foreach ($tables as $table => $status) {
			$name = $this->tableName($status);
			if ($name != "") {
				echo '<li>',
					"<a href='", h(ME), "select=", urlencode($table), "' title='", lang('Select data'), "'>", icon("data"), "</a>";

				$active = in_array($table, [$_GET["table"], $_GET["select"], $_GET["create"], $_GET["indexes"], $_GET["foreign"], $_GET["trigger"]]);
				$class = is_view($status) ? "view" : "structure";

				if (support("table") || support("indexes")) {
					echo "<a href='", h(ME), 'table=', urlencode($table), "'", bold($active, $class),
						" title='", lang('Show structure'), "' data-main='true'>$name</a>";
				} else {
					echo "<span data-main='true'", bold($active, $class), ">$name</span>";
				}

				echo "</li>\n";
			}
		}

		echo "</ul>\n";

		return null;
	}

}
