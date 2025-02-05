<?php

use Adminer\Adminer;

function create_adminer(): Adminer
{
	include "../plugins/Pluginer.php";

	$config = [
		"colorVariant" => "green",

		// Disable verifying custom default password.
		"defaultPasswordHash" => "",

		// Warning! Inline the result of password_hash() so that the password is not visible in the source code.
//		"defaultPasswordHash" => password_hash("YOUR_PASSWORD_HERE", PASSWORD_DEFAULT),
	];

	return new Adminer($config);
}

include "index.php";
