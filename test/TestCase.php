<?php

namespace Marcuwynu23\Narciso\Test;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base test case: backs up and restores $_SERVER, output buffer, and response state.
 */
abstract class TestCase extends BaseTestCase
{
	/** @var array backup of $_SERVER */
	private $serverBackup = [];

	/** @var int|null output buffer level before test */
	private $obLevel;

	protected function setUp(): void
	{
		parent::setUp();
		$this->serverBackup = $_SERVER;
		$this->obLevel = ob_get_level();
	}

	protected function tearDown(): void
	{
		$_SERVER = $this->serverBackup;
		while (ob_get_level() > $this->obLevel) {
			ob_end_clean();
		}
		parent::tearDown();
	}

	/** Set request method and URI for run() */
	protected function setRequest(string $method = 'GET', string $uri = '/'): void
	{
		$_SERVER['REQUEST_METHOD'] = $method;
		$_SERVER['REQUEST_URI'] = $uri;
		$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
	}

	/** Run app and return [output, statusCode, headers]. Captures exit from redirect by running in isolated output buffer. */
	protected function runApp(\Marcuwynu23\Narciso\Application $app): array
	{
		ob_start();
		$headers = [];
		$prev = null;
		if (function_exists('xdebug_get_headers')) {
			$prev = @ini_get('xdebug.trigger_error');
			@ini_set('xdebug.trigger_error', '0');
		}
		try {
			$app->run();
		} catch (\Throwable $e) {
			ob_end_clean();
			if ($prev !== null) {
				@ini_restore('xdebug.trigger_error');
			}
			throw $e;
		}
		$output = ob_get_clean();
		if ($prev !== null) {
			@ini_restore('xdebug.trigger_error');
		}
		$code = http_response_code();
		$list = headers_list();
		foreach ($list as $h) {
			$parts = explode(':', $h, 2);
			$headers[trim($parts[0])] = isset($parts[1]) ? trim($parts[1]) : '';
		}
		return [$output, $code ?: 200, $headers];
	}
}
