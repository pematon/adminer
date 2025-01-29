<?php

namespace Adminer;

use DateTime;
use Exception;
use MongoDB\BSON;
use MongoDB\Driver;

add_driver("mongo", "MongoDB (alpha)");

if (isset($_GET["mongo"])) {
	define("DRIVER", "mongo");

	if (class_exists('MongoDB\Driver\Manager')) {
		class Min_DB {
			var $extension = "MongoDB", $server_info = MONGODB_VERSION, $affected_rows, $error, $last_id;
			/** @var Driver\Manager */
			var $_link;
			var $_db, $_db_name;

			function connect($uri, $options) {

				$this->_link = new Driver\Manager($uri, $options);
				$this->executeCommand('admin', ['ping' => 1]);
			}

			function executeCommand($db, $command) {
				try {
					return $this->_link->executeCommand($db, new Driver\Command($command));
				} catch (Exception $e) {
					$this->error = $e->getMessage();
					return [];
				}
			}

			function executeBulkWrite($namespace, $bulk, $counter) {
				try {
					$results = $this->_link->executeBulkWrite($namespace, $bulk);
					$this->affected_rows = $results->$counter();
					return true;
				} catch (Exception $e) {
					$this->error = $e->getMessage();
					return false;
				}
			}

			function query($query) {
				return false;
			}

			function select_db($database) {
				$this->_db_name = $database;
				return true;
			}

			function quote($string) {
				return $string;
			}
		}

		class Min_Result {
			var $num_rows, $_rows = [], $_offset = 0, $_charset = [];

			function __construct($result) {
				foreach ($result as $item) {
					$row = [];
					foreach ($item as $key => $val) {
						if (is_a($val, 'MongoDB\BSON\Binary')) {
							$this->_charset[$key] = 63;
						}
						$row[$key] =
							(is_a($val, 'MongoDB\BSON\ObjectID') ? 'MongoDB\BSON\ObjectID("' . "$val\")" :
							(is_a($val, 'MongoDB\BSON\UTCDatetime') ? $val->toDateTime()->format('Y-m-d H:i:s') :
							(is_a($val, 'MongoDB\BSON\Binary') ? $val->getData() : //! allow downloading
							(is_a($val, 'MongoDB\BSON\Regex') ? "$val" :
							(is_object($val) || is_array($val) ? json_encode($val, 256) : // 256 = JSON_UNESCAPED_UNICODE
							$val // MongoMinKey, MongoMaxKey
						)))));
					}
					$this->_rows[] = $row;
					foreach ($row as $key => $val) {
						if (!isset($this->_rows[0][$key])) {
							$this->_rows[0][$key] = null;
						}
					}
				}
				$this->num_rows = count($this->_rows);
			}

			function fetch_assoc() {
				$row = current($this->_rows);
				if (!$row) {
					return $row;
				}
				$return = [];
				foreach ($this->_rows[0] as $key => $val) {
					$return[$key] = $row[$key];
				}
				next($this->_rows);
				return $return;
			}

			function fetch_row() {
				$return = $this->fetch_assoc();
				if (!$return) {
					return $return;
				}
				return array_values($return);
			}

			function fetch_field() {
				$keys = array_keys($this->_rows[0]);
				$name = $keys[$this->_offset++];
				return (object) [
					'name' => $name,
					'charsetnr' => $this->_charset[$name],
				];
			}

		}


		class Min_Driver extends Min_SQL {
			public $primary = "_id";

			function select($table, $select, $where, $group, $order = [], ?int $limit = 1, $page = 0, $print = false) {
				global $connection;
				$select = ($select == ["*"]
					? []
					: array_fill_keys($select, 1)
				);
				if (count($select) && !isset($select['_id'])) {
					$select['_id'] = 0;
				}
				$where = where_to_query($where);
				$sort = [];
				foreach ($order as $val) {
					$val = preg_replace('~ DESC$~', '', $val, 1, $count);
					$sort[$val] = ($count ? -1 : 1);
				}
				$limit = min(200, max(1, (int) $limit));
				$skip = $page * $limit;
				try {
					return new Min_Result($connection->_link->executeQuery("$connection->_db_name.$table", new Driver\Query($where, ['projection' => $select, 'limit' => $limit, 'skip' => $skip, 'sort' => $sort])));
				} catch (Exception $e) {
					$connection->error = $e->getMessage();
					return false;
				}
			}

			function update($table, $set, $queryWhere, $limit = 0, $separator = "\n") {
				global $connection;
				$db = $connection->_db_name;
				$where = sql_query_where_parser($queryWhere);
				$bulk = new Driver\BulkWrite([]);
				if (isset($set['_id'])) {
					unset($set['_id']);
				}
				$removeFields = [];
				foreach ($set as $key => $value) {
					if ($value == 'NULL') {
						$removeFields[$key] = 1;
						unset($set[$key]);
					}
				}
				$update = ['$set' => $set];
				if (count($removeFields)) {
					$update['$unset'] = $removeFields;
				}
				$bulk->update($where, $update, ['upsert' => false]);
				return $connection->executeBulkWrite("$db.$table", $bulk, 'getModifiedCount');
			}

			function delete($table, $queryWhere, $limit = 0) {
				global $connection;
				$db = $connection->_db_name;
				$where = sql_query_where_parser($queryWhere);
				$bulk = new Driver\BulkWrite([]);
				$bulk->delete($where, ['limit' => $limit]);
				return $connection->executeBulkWrite("$db.$table", $bulk, 'getDeletedCount');
			}

			function insert($table, $set) {
				global $connection;
				$db = $connection->_db_name;
				$bulk = new Driver\BulkWrite([]);
				if ($set['_id'] == '') {
					unset($set['_id']);
				}
				$bulk->insert($set);
				return $connection->executeBulkWrite("$db.$table", $bulk, 'getInsertedCount');
			}
		}

		function get_databases($flush) {
			global $connection;
			$return = [];
			foreach ($connection->executeCommand('admin', ['listDatabases' => 1]) as $dbs) {
				foreach ($dbs->databases as $db) {
					$return[] = $db->name;
				}
			}
			return $return;
		}

		function count_tables($databases) {
			$return = [];
			return $return;
		}

		function tables_list() {
			global $connection;
			$collections = [];
			foreach ($connection->executeCommand($connection->_db_name, ['listCollections' => 1]) as $result) {
				$collections[$result->name] = 'table';
			}
			return $collections;
		}

		function drop_databases($databases) {
			return false;
		}

		function indexes($table, $connection2 = null) {
			global $connection;
			$return = [];
			foreach ($connection->executeCommand($connection->_db_name, ['listIndexes' => $table]) as $index) {
				$descs = [];
				$columns = [];
				foreach (get_object_vars($index->key) as $column => $type) {
					$descs[] = ($type == -1 ? '1' : null);
					$columns[] = $column;
				}
				$return[$index->name] = [
					"type" => ($index->name == "_id_" ? "PRIMARY" : (isset($index->unique) ? "UNIQUE" : "INDEX")),
					"columns" => $columns,
					"lengths" => [],
					"descs" => $descs,
				];
			}
			return $return;
		}

		function fields($table) {
			global $driver;
			$fields = fields_from_edit();
			if (!$fields) {
				$result = $driver->select($table, ["*"], null, null, [], 10);
				if ($result) {
					while ($row = $result->fetch_assoc()) {
						foreach ($row as $key => $val) {
							$row[$key] = null;
							$fields[$key] = [
								"field" => $key,
								"type" => "string",
								"null" => ($key != $driver->primary),
								"auto_increment" => ($key == $driver->primary),
								"privileges" => [
									"insert" => 1,
									"select" => 1,
									"update" => 1,
									"where" => 1,
									"order" => 1,
								],
							];
						}
					}
				}
			}
			return $fields;
		}

		function found_rows($table_status, $where) {
			global $connection;
			$where = where_to_query($where);
			$toArray = $connection->executeCommand($connection->_db_name, ['count' => $table_status['Name'], 'query' => $where])->toArray();
			return $toArray[0]->n;
		}

		function sql_query_where_parser($queryWhere) {
			$queryWhere = preg_replace('~^\s*WHERE\s*~', "", $queryWhere);
			while ($queryWhere[0] == "(") {
				$queryWhere = preg_replace('~^\((.*)\)$~', "$1", $queryWhere);
			}

			$wheres = explode(' AND ', $queryWhere);
			$wheresOr = explode(') OR (', $queryWhere);
			$where = [];
			foreach ($wheres as $whereStr) {
				$where[] = trim($whereStr);
			}
			if (count($wheresOr) == 1) {
				$wheresOr = [];
			} elseif (count($wheresOr) > 1) {
				$where = [];
			}
			return where_to_query($where, $wheresOr);
		}

		function where_to_query($whereAnd = [], $whereOr = []) {
			global $adminer;
			$data = [];
			foreach (['and' => $whereAnd, 'or' => $whereOr] as $type => $where) {
				if (is_array($where)) {
					foreach ($where as $expression) {
						list($col, $op, $val) = explode(" ", $expression, 3);
						if ($col == "_id" && preg_match('~^(MongoDB\\\\BSON\\\\ObjectID)\("(.+)"\)$~', $val, $match)) {
							list(, $class, $val) = $match;
							$val = new $class($val);
						}
						if (!in_array($op, $adminer->getOperators())) {
							continue;
						}
						if (preg_match('~^\(f\)(.+)~', $op, $match)) {
							$val = (float) $val;
							$op = $match[1];
						} elseif (preg_match('~^\(date\)(.+)~', $op, $match)) {
							$dateTime = new DateTime($val);
							$val = new BSON\UTCDatetime($dateTime->getTimestamp() * 1000);
							$op = $match[1];
						}
						switch ($op) {
							case '=':
								$op = '$eq';
								break;
							case '!=':
								$op = '$ne';
								break;
							case '>':
								$op = '$gt';
								break;
							case '<':
								$op = '$lt';
								break;
							case '>=':
								$op = '$gte';
								break;
							case '<=':
								$op = '$lte';
								break;
							case 'regex':
								$op = '$regex';
								break;
							default:
								continue 2;
						}
						if ($type == 'and') {
							$data['$and'][] = [$col => [$op => $val]];
						} elseif ($type == 'or') {
							$data['$or'][] = [$col => [$op => $val]];
						}
					}
				}
			}
			return $data;
		}
	}

	function table($idf) {
		return $idf;
	}

	function idf_escape($idf) {
		return $idf;
	}

	function table_status($name = "", $fast = false) {
		$return = [];
		foreach (tables_list() as $table => $type) {
			$return[$table] = ["Name" => $table];
			if ($name == $table) {
				return $return[$table];
			}
		}
		return $return;
	}

	function create_database($db, $collation) {
		return true;
	}

	function last_id() {
		global $connection;
		return $connection->last_id;
	}

	function error() {
		global $connection;
		return h($connection->error);
	}

	function collations() {
		return [];
	}

	function logged_user() {
		global $adminer;
		$credentials = $adminer->credentials();
		return $credentials[1];
	}

	function connect() {
		global $adminer;
		$connection = new Min_DB;
		list($server, $username, $password) = $adminer->credentials();

		if ($server == "") {
			$server = "localhost:27017";
		}

		$options = [];
		if ($username . $password != "") {
			$options["username"] = $username;
			$options["password"] = $password;
		}
		$db = $adminer->database();
		if ($db != "") {
			$options["db"] = $db;
		}
		if (($auth_source = getenv("MONGO_AUTH_SOURCE"))) {
			$options["authSource"] = $auth_source;
		}
		$connection->connect("mongodb://$server", $options);
		if ($connection->error) {
			return $connection->error;
		}
		return $connection;
	}

	function alter_indexes($table, $alter) {
		global $connection;
		foreach ($alter as $val) {
			list($type, $name, $set) = $val;
			if ($set == "DROP") {
				$return = $connection->_db->command(["deleteIndexes" => $table, "index" => $name]);
			} else {
				$columns = [];
				foreach ($set as $column) {
					$column = preg_replace('~ DESC$~', '', $column, 1, $count);
					$columns[$column] = ($count ? -1 : 1);
				}
				$return = $connection->_db->selectCollection($table)->ensureIndex($columns, [
					"unique" => ($type == "UNIQUE"),
					"name" => $name,
					//! "sparse"
				]);
			}
			if ($return['errmsg']) {
				$connection->error = $return['errmsg'];
				return false;
			}
		}
		return true;
	}

	function support($feature) {
		return preg_match("~database|indexes|descidx~", $feature);
	}

	function db_collation($db, $collations) {
	}

	function information_schema() {
	}

	function is_view($table_status) {
	}

	function convert_field($field) {
	}

	function unconvert_field(array $field, $return) {
		return $return;
	}

	function foreign_keys($table) {
		return [];
	}

	function fk_support($table_status) {
	}

	function engines() {
		return [];
	}

	function alter_table($table, $name, $fields, $foreign, $comment, $engine, $collation, $auto_increment, $partitioning) {
		global $connection;
		if ($table == "") {
			$connection->_db->createCollection($name);
			return true;
		}
	}

	function drop_tables($tables) {
		global $connection;
		foreach ($tables as $table) {
			$response = $connection->_db->selectCollection($table)->drop();
			if (!$response['ok']) {
				return false;
			}
		}
		return true;
	}

	function truncate_tables($tables) {
		global $connection;
		foreach ($tables as $table) {
			$response = $connection->_db->selectCollection($table)->remove();
			if (!$response['ok']) {
				return false;
			}
		}
		return true;
	}

	function driver_config() {
		return [
			'possible_drivers' => ["mongodb"],
			'jush' => "mongo",
			'operators' => [
				"=",
				"!=",
				">",
				"<",
				">=",
				"<=",
				"regex",
				"(f)=",
				"(f)!=",
				"(f)>",
				"(f)<",
				"(f)>=",
				"(f)<=",
				"(date)=",
				"(date)!=",
				"(date)>",
				"(date)<",
				"(date)>=",
				"(date)<=",
			],
			'operator_like' => "LIKE %%", // TODO: LIKE operator is not listed in operators.
			'operator_regexp' => 'regex',
			'functions' => [],
			'grouping' => [],
			'edit_functions' => [["json"]],
		];
	}
}
