<?php

namespace Adminer;

class Config
{
	/** @var array */
	private $config;

	public function __construct(array $config)
	{
		if (($config["hiddenDatabases"] ?? null) == "__system") {
			$config["hiddenDatabases"] = [
				"mysql", "information_schema", "performance_schema", "sys", // MySQL
				"template1", // PostgreSQL
				"INFORMATION_SCHEMA", "system" // Clickhouse
			];
		}

		if (($config["hiddenSchemas"] ?? null) == "__system") {
			$config["hiddenSchemas"] = [
				"information_schema", "pg_catalog", "pg_toast", "pg_temp_*", "pg_toast_temp_*" // PostgreSQL
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

	public function getHiddenSchemas(): array
	{
		return $this->config["hiddenSchemas"] ?? [];
	}
}
