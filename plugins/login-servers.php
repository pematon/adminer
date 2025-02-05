<?php

namespace Adminer;

/** Display constant list of servers in login form
* @link https://www.adminer.org/plugins/#use
* @author Jakub Vrana, https://www.vrana.cz/
* @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
*/
class AdminerLoginServers {
	/** @access protected */
	var $servers;

	/** Set supported servers
	* @param array array($description => array("server" => , "driver" => "mysql|pgsql|sqlite|..."))
	*/
	function __construct($servers) {
		$this->servers = $servers;
		if ($_POST["auth"]) {
			$key = $_POST["auth"]["server"];
			$_POST["auth"]["driver"] = $this->servers[$key]["driver"];
		}
	}

	public function getCredentials(): ?array
	{
		return [$this->servers[SERVER]["server"], $_GET["username"], get_password()];
	}

	public function authenticate(string $username, string $password)
	{
		if (!$this->servers[SERVER]) {
			return false;
		}

		return null;
	}

	function loginFormField($name, $heading, $value) {
		if ($name == 'driver') {
			return '';
		} elseif ($name == 'server') {
			return $heading . "<select name='auth[server]'>" . optionlist(array_keys($this->servers), SERVER) . "</select>\n";
		}
	}

}
