<?php

use Adminer\Adminer;

function create_adminer(): Adminer
{
	include "../plugins/Pluginer.php";

	class CustomAdminer extends Adminer
	{
		function loginFormField($name, $heading, $value)
		{
			return parent::loginFormField($name, $heading, str_replace('value="mysql"', 'value="sqlite"', $value));
		}

		function database()
		{
			return "PATH_TO_YOUR_SQLITE_HERE";
		}
	}

	$config = [
		"colorVariant" => "green",
		// Warning! Inline the result of password_hash() so that the password is not visible in the source code.
		"defaultPasswordHash" => password_hash("YOUR_PASSWORD_HERE", PASSWORD_DEFAULT),
	];

	return new CustomAdminer($config);
}

include "index.php";
