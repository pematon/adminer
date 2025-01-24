<?php

namespace Adminer;

/** Link system tables (in mysql and information_schema databases) by foreign keys
* @link https://www.adminer.org/plugins/#use
* @author Jakub Vrana, https://www.vrana.cz/
* @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
*/
class AdminerForeignSystem {

	function foreignKeys($table) {
		if ((DRIVER == "server" || DRIVER == "mysql") && DB == "mysql") {
			switch ($table) {
				case "columns_priv": return [["table" => "user", "source" => ["Host", "User"], "target" => ["Host", "User"]]];
				case "db": return [["table" => "user", "source" => ["Host", "User"], "target" => ["Host", "User"]]];
				case "help_category": return [["table" => "help_category", "source" => ["parent_category_id"], "target" => ["help_category_id"]]];
				case "help_relation": return [["table" => "help_topic", "source" => ["help_topic_id"], "target" => ["help_topic_id"]], ["table" => "help_keyword", "source" => ["help_keyword_id"], "target" => ["help_keyword_id"]]];
				case "help_topic": return [["table" => "help_category", "source" => ["help_category_id"], "target" => ["help_category_id"]]];
				case "procs_priv": return [["table" => "user", "source" => ["Host", "User"], "target" => ["Host", "User"]], ["table" => "proc", "source" => ["Db", "Routine_name"], "target" => ["db", "name"]]];
				case "tables_priv": return [["table" => "user", "source" => ["Host", "User"], "target" => ["Host", "User"]]];
				case "time_zone_name": return [["table" => "time_zone", "source" => ["Time_zone_id"], "target" => ["Time_zone_id"]]];
				case "time_zone_transition": return [["table" => "time_zone", "source" => ["Time_zone_id"], "target" => ["Time_zone_id"]], ["table" => "time_zone_transition_type", "source" => ["Time_zone_id", "Transition_type_id"], "target" => ["Time_zone_id", "Transition_type_id"]]];
				case "time_zone_transition_type": return [["table" => "time_zone", "source" => ["Time_zone_id"], "target" => ["Time_zone_id"]]];
			}
		} elseif (DB == "information_schema") {
			$schemata = ["table" => "SCHEMATA", "source" => ["TABLE_CATALOG", "TABLE_SCHEMA"], "target" => ["CATALOG_NAME", "SCHEMA_NAME"]];
			$tables = ["table" => "TABLES", "source" => ["TABLE_CATALOG", "TABLE_SCHEMA", "TABLE_NAME"], "target" => ["TABLE_CATALOG", "TABLE_SCHEMA", "TABLE_NAME"]];
			$columns = ["table" => "COLUMNS", "source" => ["TABLE_CATALOG", "TABLE_SCHEMA", "TABLE_NAME", "COLUMN_NAME"], "target" => ["TABLE_CATALOG", "TABLE_SCHEMA", "TABLE_NAME", "COLUMN_NAME"]];
			$character_sets = ["table" => "CHARACTER_SETS", "source" => ["CHARACTER_SET_NAME"], "target" => ["CHARACTER_SET_NAME"]];
			$collations = ["table" => "COLLATIONS", "source" => ["COLLATION_NAME"], "target" => ["COLLATION_NAME"]];
			$routine_charsets = [["source" => ["CHARACTER_SET_CLIENT"]] + $character_sets, ["source" => ["COLLATION_CONNECTION"]] + $collations, ["source" => ["DATABASE_COLLATION"]] + $collations];
			switch ($table) {
				case "CHARACTER_SETS": return [["source" => ["DEFAULT_COLLATE_NAME"]] + $collations];
				case "COLLATIONS": return [$character_sets];
				case "COLLATION_CHARACTER_SET_APPLICABILITY": return [$collations, $character_sets];
				case "COLUMNS": return [$schemata, $tables, $character_sets, $collations];
				case "COLUMN_PRIVILEGES": return [$schemata, $tables, $columns];
				case "TABLES": return [$schemata, ["source" => ["TABLE_COLLATION"]] + $collations];
				case "SCHEMATA": return [["source" => ["DEFAULT_CHARACTER_SET_NAME"]] + $character_sets, ["source" => ["DEFAULT_COLLATION_NAME"]] + $collations];
				case "EVENTS": return array_merge([["source" => ["EVENT_CATALOG", "EVENT_SCHEMA"]] + $schemata], $routine_charsets);
				case "FILES": return [$schemata, $tables];
				case "KEY_COLUMN_USAGE": return [["source" => ["CONSTRAINT_CATALOG", "CONSTRAINT_SCHEMA"]] + $schemata, $schemata, $tables, $columns, ["source" => ["TABLE_CATALOG", "REFERENCED_TABLE_SCHEMA"]] + $schemata, ["source" => ["TABLE_CATALOG", "REFERENCED_TABLE_SCHEMA", "REFERENCED_TABLE_NAME"]] + $tables, ["source" => ["TABLE_CATALOG", "REFERENCED_TABLE_SCHEMA", "REFERENCED_TABLE_NAME", "REFERENCED_COLUMN_NAME"]] + $columns];
				case "PARTITIONS": return [$schemata, $tables];
				case "REFERENTIAL_CONSTRAINTS": return [["source" => ["CONSTRAINT_CATALOG", "CONSTRAINT_SCHEMA"]] + $schemata, ["source" => ["UNIQUE_CONSTRAINT_CATALOG", "UNIQUE_CONSTRAINT_SCHEMA"]] + $schemata, ["source" => ["CONSTRAINT_CATALOG", "CONSTRAINT_SCHEMA", "TABLE_NAME"]] + $tables, ["source" => ["CONSTRAINT_CATALOG", "CONSTRAINT_SCHEMA", "REFERENCED_TABLE_NAME"]] + $tables];
				case "ROUTINES": return array_merge([["source" => ["ROUTINE_CATALOG", "ROUTINE_SCHEMA"]] + $schemata], $routine_charsets);
				case "SCHEMA_PRIVILEGES": return [$schemata];
				case "STATISTICS": return [$schemata, $tables, $columns, ["source" => ["TABLE_CATALOG", "INDEX_SCHEMA"]] + $schemata];
				case "TABLE_CONSTRAINTS": return [["source" => ["CONSTRAINT_CATALOG", "CONSTRAINT_SCHEMA"]] + $schemata, ["source" => ["CONSTRAINT_CATALOG", "TABLE_SCHEMA"]] + $schemata, ["source" => ["CONSTRAINT_CATALOG", "TABLE_SCHEMA", "TABLE_NAME"]] + $tables];
				case "TABLE_PRIVILEGES": return [$schemata, $tables];
				case "TRIGGERS": return array_merge([["source" => ["TRIGGER_CATALOG", "TRIGGER_SCHEMA"]] + $schemata, ["source" => ["EVENT_OBJECT_CATALOG", "EVENT_OBJECT_SCHEMA"]] + $schemata, ["source" => ["EVENT_OBJECT_CATALOG", "EVENT_OBJECT_SCHEMA", "EVENT_OBJECT_TABLE"]] + $tables], $routine_charsets);
				case "VIEWS": return [$schemata];
			}
		}
	}

}
