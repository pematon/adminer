<?php

namespace Adminer;

abstract class AdminerBase
{
	/** @var Config */
	protected $config;

	public function __construct(array $config = [])
	{
		$this->config = new Config($config);
	}

	public function getConfig(): Config
	{
		return $this->config;
	}

	public abstract function setOperators(?array $operators, ?string $likeOperator, ?string $regexpOperator): void;

	public abstract function removeOperator(string $operator): void;

	public abstract function getOperators(): ?array;

	public abstract function getLikeOperator(): ?string;

	public abstract function getRegexpOperator(): ?string;

	public abstract function name();

	public abstract function credentials();

	public abstract function connectSsl();

	public abstract function permanentLogin($create = false);

	public abstract function bruteForceKey();

	public abstract function serverName($server);

	public abstract function database();

	/**
	 * Returns cached list of databases.
	 */
	function databases($flush = true): array
	{
		$databases = get_databases($flush);

		if ($hiddenList = $this->config->getHiddenDatabases()) {
			$databases = array_filter($databases, function (string $name) use ($hiddenList) {
				return !in_array($name, $hiddenList);
			});
		}

		return $databases;
	}

	/**
	 * Returns list of schemas.
	 */
	function schemas(): array
	{
		$schemas = schemas();

		if ($hiddenList = $this->config->getHiddenDatabases()) {
			$schemas = array_filter($schemas, function (string $name) use ($hiddenList) {
				return !in_array($name, $hiddenList);
			});
		}

		return $schemas;
	}

	public abstract function queryTimeout();

	public abstract function headers();

	public abstract function csp();

	public abstract function head();

	/**
	 * Returns URLs of the CSS files.
	 *
	 * @return string[]
	 */
	function css(): array
	{
		$urls = $this->getConfig()->getCssUrls();

		$filename = "adminer.css";
		if (file_exists($filename)) {
			$urls[] = "$filename?v=" . filemtime($filename);
		}

		return $urls;
	}

	public abstract function loginForm();

	public abstract function loginFormField($name, $heading, $value);

	public abstract function login($login, $password);

	public abstract function tableName($tableStatus);

	public abstract function fieldName($field, $order = 0);

	public abstract function selectLinks($tableStatus, $set = "");

	public abstract function foreignKeys($table);

	public abstract function backwardKeys($table, $tableName);

	public abstract function backwardKeysPrint($backwardKeys, $row);

	public abstract function selectQuery($query, $start, $failed = false);

	public abstract function sqlCommandQuery($query);

	public abstract function rowDescription($table);

	public abstract function rowDescriptions($rows, $foreignKeys);

	public abstract function selectLink($val, $field);

	public abstract function selectVal($val, $link, $field, $original);

	public abstract function editVal($val, $field);

	public abstract function tableStructurePrint($fields);

	public abstract function tablePartitionsPrint($partition_info);

	public abstract function tableIndexesPrint($indexes);

	public abstract function selectColumnsPrint(array $select, array $columns);

	public abstract function selectSearchPrint(array $where, array $columns, array $indexes);

	public abstract function selectOrderPrint(array $order, array $columns, array $indexes);

	public abstract function selectLimitPrint(?int $limit): void;

	public abstract function selectLengthPrint($text_length);

	public abstract function selectActionPrint($indexes);

	public abstract function selectCommandPrint();

	public abstract function selectImportPrint();

	public abstract function selectEmailPrint($emailFields, $columns);

	public abstract function selectColumnsProcess($columns, $indexes);

	public abstract function selectSearchProcess($fields, $indexes);

	public abstract function selectOrderProcess($fields, $indexes);

	/**
	 * Processed limit box in select.
	 *
	 * @return ?int Expression to use in LIMIT, will be escaped.
	 */
	public function selectLimitProcess(): ?int
	{
		if (!isset($_GET["limit"])) {
			return $this->config->getRecordsPerPage();
		}

		return $_GET["limit"] != "" ? (int)$_GET["limit"] : null;
	}

	public abstract function selectLengthProcess();

	public abstract function selectEmailProcess($where, $foreignKeys);

	public abstract function messageQuery($query, $time, $failed = false);

	public abstract function editRowPrint($table, $fields, $row, $update);

	public abstract function editFunctions($field);

	public abstract function editInput($table, $field, $attrs, $value, $function);

	public abstract function editHint($table, $field, $value);

	public abstract function processInput(?array $field, $value, $function = "");

	public abstract function dumpOutput();

	public abstract function dumpFormat();

	public abstract function dumpDatabase($db);

	public abstract function dumpTable($table, $style, $is_view = 0);

	public abstract function dumpData($table, $style, $query);

	public abstract function dumpFilename($identifier);

	public abstract function dumpHeaders($identifier, $multi_table = false);

	public abstract function importServerPath();

	public abstract function homepage();

	public abstract function navigation($missing);

	public abstract function databasesPrint($missing);

	public function printTablesFilter() {
		echo "<div class='tables-filter jsonly'>"
			. "<input id='tables-filter' autocomplete='off' placeholder='" . lang('Table') . "' class='input'>"
			. script("initTablesFilter(" . json_encode($this->database()) . ");")
			. "</div>\n";
	}

	public abstract function tablesPrint(array $tables);

	public abstract function foreignColumn($foreignKeys, $column): ?array;
}