<?php

namespace Adminer;

if (isset($_GET["file"])) {
	load_compiled_file($_GET["file"]);
}

function load_compiled_file(string $filename)
{
	if ($filename == "") {
		http_response_code(404);
		exit;
	}

	if ($_SERVER["HTTP_IF_MODIFIED_SINCE"]) {
		http_response_code(304);
		exit;
	}

	header("Expires: " . gmdate("D, d M Y H:i:s", time() + 365 * 24 * 60 * 60) . " GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: immutable");

	switch (pathinfo($filename, PATHINFO_EXTENSION)) {
		case "css":
			header("Content-Type: text/css; charset=utf-8");
			break;
		case "js":
			header("Content-Type: text/javascript; charset=utf-8");
			break;
		case "ico":
			header("Content-Type: image/x-icon");
			break;
		case "png":
			header("Content-Type: image/png");
			break;
		case "svg":
			header("Content-Type: image/svg+xml");
			break;
	}

	$data = read_compiled_file($filename); // !compile: get compiled file
	if (!$data) {
		http_response_code(404);
		exit;
	}

	echo lzw_decompress(base64_decode($data));
	exit;
}
