<?php

use Adminer\AdminerLoginPasswordLess;
use Adminer\Pluginer;

function create_adminer(): Pluginer
{
	include "../plugins/Pluginer.php";
	include "../plugins/login-password-less.php";

	class CustomAdminer extends Pluginer
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

	return new CustomAdminer([
		// TODO: inline the result of password_hash() so that the password is not visible in source codes
		new AdminerLoginPasswordLess(password_hash("YOUR_PASSWORD_HERE", PASSWORD_DEFAULT)),
	]);
}

include "index.php";
