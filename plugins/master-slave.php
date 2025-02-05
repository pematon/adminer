<?php

namespace Adminer;

/** Execute writes on master and reads on slave
* @link https://www.adminer.org/plugins/#use
* @author Jakub Vrana, https://www.vrana.cz/
* @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
*/
class AdminerMasterSlave {
	private $masters = [];

	/**
	* @param array ($slave => $master)
	*/
	function __construct($masters) {
		$this->masters = $masters;
	}

	public function getCredentials(): ?array
	{
		if ($_POST && isset($this->masters[SERVER])) {
			return [$this->masters[SERVER], $_GET["username"], get_session("pwds")];
		}

		return null;
	}

	public function authenticate(string $username, string $password)
	{
		if (!$_POST && isset($_SESSION["master"])) {
			connection()->query("DO MASTER_POS_WAIT('" . q($_SESSION["master"]['File']) . "', " . $_SESSION["master"]["Position"] . ")");
			$_SESSION["master"] = null;
		}

		return null;
	}

	function messageQuery($query, $time, $failed = false) {
		//! doesn't work with sql.inc.php
		$connection = connection();
		$result = $connection->query('SHOW MASTER STATUS');
		if ($result) {
			restart_session();
			$_SESSION["master"] = $result->fetch_assoc();
		}
	}

}
