<?php
/**
 * Sample: API response as JSON or XML (legacy)
 *
 * Features:
 * - sendAPI($data, $options) — respond as JSON or XML
 * - Auto-detect format from ?format=json|xml or Accept header
 * - Options: format, statusCode, root (XML root tag), xmlItemName (list items)
 * - getPreferredApiFormat() — returns 'json' or 'xml'
 * - arrayToXml() — public helper to convert array to XML string
 *
 * Try:
 *   GET /           → JSON (default)
 *   GET /?format=xml → XML
 *   GET /?format=json → JSON
 *   Or send header: Accept: application/xml
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Marcuwynu23\Narciso\Application;

$app = new Application();
$app->setViewPath(__DIR__ . '/views');

$app->route('GET', '/', function ($app) {
	$data = [
		'message' => 'Use ?format=xml for XML (legacy) or ?format=json for JSON',
		'users'   => [
			['id' => 1, 'name' => 'Alice'],
			['id' => 2, 'name' => 'Bob'],
		],
	];
	// Auto-detect from query or Accept header
	$app->sendAPI($data);
});

$app->route('GET', '/05_api_json_xml.php', function ($app) {
	$data = ['sample' => '05', 'format_detected' => $app->getPreferredApiFormat()];
	$app->sendAPI($data);
});

// Force XML with custom root and item name
$app->route('GET', '/xml-only', function ($app) {
	$app->sendAPI(
		['items' => [['id' => 1], ['id' => 2]]],
		['format' => 'xml', 'root' => 'data', 'xmlItemName' => 'item']
	);
});

// Custom status code
$app->route('GET', '/created', function ($app) {
	$app->sendAPI(['id' => 99, 'created' => true], ['format' => 'json', 'statusCode' => 201]);
});

$app->run();
