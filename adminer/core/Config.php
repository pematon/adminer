<?php

namespace Adminer;

class Config
{
	/** @var array */
	private $config;

	public function __construct(array $config)
	{
		if (isset($config["hiddenDatabases"]) && $config["hiddenDatabases"] == "__system") {
			$config["hiddenDatabases"] = [
				"mysql", "information_schema", "performance_schema", "sys", // MySQL
				"template1", "pg_catalog", "pg_toast" // PostgreSQL
			];
		}

		if (isset($config["hiddenSchemas"]) && $config["hiddenSchemas"] == "__system") {
			$config["hiddenSchemas"] = [
				"information_schema", // PostgreSQL
			];
		}

		$this->config = $config;
	}

	public function getTheme(): string
	{
		return $this->config["theme"] ?? "default";
	}

	/**
	 * @return string[]
	 */
	public function getCssUrls(): array
	{
		return $this->config["cssUrls"] ?? [];
	}

	/**
	 * @return string[]
	 */
	public function getJsUrls(): array
	{
		return $this->config["jsUrls"] ?? [];
	}

	public function isVersionVerificationEnabled(): bool
	{
		return $this->config["versionVerification"] ?? true;
	}

	public function getHiddenDatabases(): array
	{
		return $this->config["hiddenDatabases"] ?? [];
	}
}
