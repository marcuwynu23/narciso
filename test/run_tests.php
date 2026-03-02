<?php

/**
 * Run the full test suite (PHPUnit).
 * Usage from project root:
 *   php test/run_tests.php
 *   vendor/bin/phpunit
 */

$root = dirname(__DIR__);
$autoload = $root . '/vendor/autoload.php';
if (!is_file($autoload)) {
	fprintf(STDERR, "Run: composer install\n");
	exit(1);
}
require $autoload;

$_SERVER['argv'] = array_merge(
	['phpunit', '--configuration', $root . DIRECTORY_SEPARATOR . 'phpunit.xml'],
	array_slice($argv ?? [], 1)
);
$_SERVER['argc'] = count($_SERVER['argv']);

PHPUnit\TextUI\Command::main();
