<?php

/** Allow switching designs
* @link https://www.adminer.org/plugins/#use
* @author Jakub Vrana, https://www.vrana.cz/
* @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
*/
class AdminerDesigns {
	/** @access protected */
	var $designs;

	/**
	 * @param array $designs URL in key, name in value. If not provided, designs will be auto-discovered in ./designs folder.
	 */
	function __construct($designs = []) {
		$this->designs = $designs;

		// Discover available designs.
		if (empty($this->designs)) {
			$directories = array_map('basename', glob('./designs/*', GLOB_ONLYDIR));
			foreach ($directories as $directory) {
				$this->designs['./designs/' . $directory . '/adminer.css'] = ucfirst($directory);
			}
		}
	}

	function headers() {
		if (isset($_POST["design"]) && verify_token()) {
			restart_session();
			$_SESSION["design"] = $_POST["design"];
			redirect($_SERVER["REQUEST_URI"]);
		}
	}

	function css() {
		$return = array();
		if (array_key_exists($_SESSION["design"], $this->designs)) {
			$return[] = $_SESSION["design"];
		}

		return $return;
	}

	function navigation($missing) {
		echo "<form action='' method='post' style='position: fixed; bottom: .5em; right: .5em;'>";
		if (!$this->designs) {
			echo "<div class='message' style='margin-right: 0; cursor: help' title='Copy designs from source code in \"designs\" directory (for example designs/ng9/adminer.css) or deactivate AdminerDesigns plugin.'>No designs available!</div>";
		} else {
			echo html_select("design", array("" => "(design)") + $this->designs, $_SESSION["design"], "this.form.submit();");
		}
		echo '<input type="hidden" name="token" value="' . get_token() . '">';
		echo "</form>\n";
	}

}
