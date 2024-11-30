<?php

namespace Adminer;

class Config
{
	/** @var array */
	private $config;

	public function __construct(array $config)
	{
		$this->config = $config;
	}

	public function getTheme(): string
	{
		return $this->config["theme"] ?? "default";
	}
}
