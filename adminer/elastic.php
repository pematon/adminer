<?php

use Adminer\AdminerLoginPasswordLess;
use Adminer\Pluginer;

function create_adminer(): Pluginer
{
	include "../plugins/Pluginer.php";
	include "../plugins/login-password-less.php";

	return new Pluginer([
		// Attention! Inline the result of password_hash() so that the password is not visible in source codes.
		new AdminerLoginPasswordLess(password_hash("YOUR_PASSWORD_HERE", PASSWORD_DEFAULT)),
	], [
		"colorVariant" => "green",
	]);
}

include "index.php";
