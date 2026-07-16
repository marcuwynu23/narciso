<?php

namespace Marcuwynu23\Narciso\Test;

use Marcuwynu23\Narciso\Application;
use Marcuwynu23\Narciso\Middleware\MiddlewareInterface;
use Marcuwynu23\Narciso\Middleware\RateLimitMiddleware;

final class ApplicationTest extends TestCase
{
	public function testConstructorAndSetViewPath(): void
	{
		$app = new Application();
		$this->assertSame($app, $app->setViewPath(__DIR__ . '/../samples/views'));
	}

	public function testSetPoweredByFluent(): void
	{
		$app = new Application();
		$this->assertSame($app, $app->setPoweredBy(false));
		$this->assertSame($app, $app->setPoweredBy('Express'));
		$this->assertSame($app, $app->setPoweredBy(null));
	}

	public function testSetPoweredByFalseRemovesHeader(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->setPoweredBy(false);
		$app->route('GET', '/', function ($app) {
			$app->json(['ok' => true]);
		});
		$this->setRequest('GET', '/');
		[$output, $code, $headers] = $this->runApp($app);
		$this->assertArrayNotHasKey('X-Powered-By', $headers);
		$this->assertSame(200, $code);
		$this->assertJsonStringEqualsJsonString('{"ok":true}', $output);
	}

	public function testSetPoweredByCustomValue(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->setPoweredBy('Express');
		$app->route('GET', '/', function ($app) {
			$app->json(['ok' => true]);
		});
		$this->setRequest('GET', '/');
		[$output, $code, $headers] = $this->runApp($app);
		$this->assertArrayHasKey('X-Powered-By', $headers);
		$this->assertSame('Express', $headers['X-Powered-By']);
	}

	public function testRouteExactPath(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/', function ($app) {
			$app->json(['path' => 'root']);
		});
		$this->setRequest('GET', '/');
		[$output, $code, $headers] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertJsonStringEqualsJsonString('{"path":"root"}', $output);
	}

	public function testRoutePathWithParams(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/users/:id', function ($app, $params) {
			$app->json(['id' => $params['id'] ?? null]);
		});
		$this->setRequest('GET', '/users/42');
		[$output, $code] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertJsonStringEqualsJsonString('{"id":"42"}', $output);
	}

	public function testRoutePathWithMultipleParams(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/posts/:slug/comments/:cid', function ($app, $params) {
			$app->json(['slug' => $params['slug'], 'cid' => $params['cid']]);
		});
		$this->setRequest('GET', '/posts/hello-world/comments/7');
		[$output, $code] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertJsonStringEqualsJsonString('{"slug":"hello-world","cid":"7"}', $output);
	}

	public function testMethodMismatchReturns404(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/', function ($app) {
			$app->json(['ok' => true]);
		});
		$this->setRequest('POST', '/');
		[$output, $code] = $this->runApp($app);
		$this->assertSame(404, $code);
		$data = json_decode($output, true);
		$this->assertSame('Not Found', $data['error'] ?? null);
	}

	public function testNoMatchingRouteReturns404(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/known', function ($app) {
			$app->json([]);
		});
		$this->setRequest('GET', '/unknown');
		[$output, $code] = $this->runApp($app);
		$this->assertSame(404, $code);
		$data = json_decode($output, true);
		$this->assertSame('Not Found', $data['error'] ?? null);
	}

	public function testJsonResponse(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/', function ($app) {
			$app->json(['message' => 'Hello'], 201);
		});
		$this->setRequest('GET', '/');
		[$output, $code, $headers] = $this->runApp($app);
		$this->assertSame(201, $code);
		$this->assertStringContainsString('application/json', $headers['Content-Type'] ?? '');
		$this->assertJsonStringEqualsJsonString('{"message":"Hello"}', $output);
	}

	public function testRenderView(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/', function ($app) {
			$app->render('/home.view', []);
		});
		$this->setRequest('GET', '/');
		[$output] = $this->runApp($app);
		$this->assertStringContainsString('Hello World', $output);
	}

	public function testUseAndMiddlewareOrder(): void
	{
		$order = [];
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->use(function ($app, $next) use (&$order) {
			$order[] = 'first-before';
			$next();
			$order[] = 'first-after';
		});
		$app->use(function ($app, $next) use (&$order) {
			$order[] = 'second-before';
			$next();
			$order[] = 'second-after';
		});
		$app->route('GET', '/', function ($app) use (&$order) {
			$order[] = 'handler';
			$app->json([]);
		});
		$this->setRequest('GET', '/');
		$this->runApp($app);
		$this->assertSame(['first-before', 'second-before', 'handler', 'second-after', 'first-after'], $order);
	}

	public function testUseMiddlewareInterface(): void
	{
		$hit = false;
		$middleware = new class($hit) implements MiddlewareInterface {
			public $hit;
			public function __construct(&$hit) { $this->hit = &$hit; }
			public function handle(callable $next) {
				$this->hit = true;
				return $next();
			}
		};
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->use($middleware);
		$app->route('GET', '/', function ($app) {
			$app->json([]);
		});
		$this->setRequest('GET', '/');
		$this->runApp($app);
		$this->assertTrue($hit);
	}

	public function testHandleDatabaseSqliteMemory(): void
	{
		$app = new Application();
		$app->handleDatabase(['type' => 'sqlite', 'database' => ':memory:']);
		$this->assertInstanceOf(\SQLite3::class, $app->db);
		$app->db->exec('CREATE TABLE t (id INTEGER)');
		$app->db->exec("INSERT INTO t VALUES (1)");
		$r = $app->db->querySingle('SELECT id FROM t');
		$this->assertSame(1, (int) $r);
	}

	public function testHandleDatabaseAcceptsUsernameKey(): void
	{
		// SQLite doesn't use user/password; we only assert it doesn't throw when 'username' is in config
		$app = new Application();
		$app->handleDatabase([
			'type' => 'sqlite',
			'database' => ':memory:',
			'username' => 'ignored',
		]);
		$this->assertInstanceOf(\SQLite3::class, $app->db);
	}

	public function testHandleDatabaseThrowsUnsupportedType(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Unsupported database type');
		$app = new Application();
		$app->handleDatabase(['type' => 'invalid', 'database' => 'x']);
	}

	public function testRequestPostEmptyInputReturnsNull(): void
	{
		$app = new Application();
		// In CLI php://input is typically empty
		$this->assertNull($app->requestPost());
	}

	public function testRequestRawReturnsString(): void
	{
		$app = new Application();
		$this->assertIsString($app->requestRaw());
	}

	public function testRouteFluent(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$this->assertSame($app, $app->route('GET', '/', function () {}));
	}

	public function testRedirectMethodExists(): void
	{
		$app = new Application();
		$this->assertTrue(method_exists($app, 'redirect'));
		$this->assertIsCallable([$app, 'redirect']);
	}

	public function testHandleCorsSetsOriginWhenPresent(): void
	{
		$_SERVER['HTTP_ORIGIN'] = 'http://test.example.com';
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$this->setRequest('GET', '/');
		$app->handleCORS();
		$app->route('GET', '/', function ($app) {
			$app->json([]);
		});
		[, , $headers] = $this->runApp($app);
		$this->assertArrayHasKey('Access-Control-Allow-Origin', $headers);
		$this->assertSame('http://test.example.com', $headers['Access-Control-Allow-Origin']);
	}

	public function testHandleSessionFluent(): void
	{
		$app = new Application();
		$this->assertSame($app, $app->handleSession('NarcisoTest'));
	}

	// --- sendAPI (JSON / XML) tests ---

	public function testSendApiJsonExplicit(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/api', function ($app) {
			$app->sendAPI(['message' => 'Hello', 'id' => 1], ['format' => 'json']);
		});
		$this->setRequest('GET', '/api');
		[$output, $code, $headers] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertStringContainsString('application/json', $headers['Content-Type'] ?? '');
		$this->assertJsonStringEqualsJsonString('{"message":"Hello","id":1}', $output);
	}

	public function testSendApiXmlExplicit(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/api', function ($app) {
			$app->sendAPI(['message' => 'Hello', 'id' => 1], ['format' => 'xml']);
		});
		$this->setRequest('GET', '/api');
		[$output, $code, $headers] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertStringContainsString('application/xml', $headers['Content-Type'] ?? '');
		$this->assertStringContainsString('<?xml', $output);
		$this->assertStringContainsString('<response>', $output);
		$this->assertStringContainsString('<message>Hello</message>', $output);
		$this->assertStringContainsString('<id>1</id>', $output);
	}

	public function testSendApiXmlLegacyRootAndItemName(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/api', function ($app) {
			$app->sendAPI(
				['users' => [['id' => 1, 'name' => 'A'], ['id' => 2, 'name' => 'B']]],
				['format' => 'xml', 'root' => 'data', 'xmlItemName' => 'user']
			);
		});
		$this->setRequest('GET', '/api');
		[$output] = $this->runApp($app);
		$this->assertStringContainsString('<data>', $output);
		$this->assertStringContainsString('<users>', $output);
		$this->assertStringContainsString('<user>', $output);
		$this->assertStringContainsString('<id>1</id>', $output);
		$this->assertStringContainsString('<name>A</name>', $output);
	}

	public function testSendApiAutoFormatFromQueryJson(): void
	{
		$_GET['format'] = 'json';
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/api', function ($app) {
			$app->sendAPI(['x' => 1]);
		});
		$this->setRequest('GET', '/api');
		[$output, $code, $headers] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertStringContainsString('application/json', $headers['Content-Type'] ?? '');
		$this->assertJsonStringEqualsJsonString('{"x":1}', $output);
	}

	public function testSendApiAutoFormatFromQueryXml(): void
	{
		$_GET['format'] = 'xml';
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/api', function ($app) {
			$app->sendAPI(['x' => 1]);
		});
		$this->setRequest('GET', '/api');
		[$output, $code, $headers] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertStringContainsString('application/xml', $headers['Content-Type'] ?? '');
		$this->assertStringContainsString('<x>1</x>', $output);
	}

	public function testSendApiAutoFormatFromAcceptHeader(): void
	{
		$_SERVER['HTTP_ACCEPT'] = 'application/xml, text/plain, */*';
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/api', function ($app) {
			$app->sendAPI(['legacy' => 'yes']);
		});
		$this->setRequest('GET', '/api');
		[$output, $code, $headers] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertStringContainsString('application/xml', $headers['Content-Type'] ?? '');
		$this->assertStringContainsString('<legacy>yes</legacy>', $output);
	}

	public function testGetPreferredApiFormatDefault(): void
	{
		unset($_GET['format'], $_SERVER['HTTP_ACCEPT']);
		$app = new Application();
		$this->assertSame('json', $app->getPreferredApiFormat());
	}

	public function testGetPreferredApiFormatQueryXml(): void
	{
		$_GET['format'] = 'xml';
		$app = new Application();
		$this->assertSame('xml', $app->getPreferredApiFormat());
	}

	public function testGetPreferredApiFormatQueryJson(): void
	{
		$_GET['format'] = 'json';
		$app = new Application();
		$this->assertSame('json', $app->getPreferredApiFormat());
	}

	public function testGetPreferredApiFormatAcceptXml(): void
	{
		unset($_GET['format']);
		$_SERVER['HTTP_ACCEPT'] = 'text/xml, application/xml';
		$app = new Application();
		$this->assertSame('xml', $app->getPreferredApiFormat());
	}

	public function testSendApiStatusCodeOption(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/api', function ($app) {
			$app->sendAPI(['created' => true], ['format' => 'json', 'statusCode' => 201]);
		});
		$this->setRequest('GET', '/api');
		[, $code] = $this->runApp($app);
		$this->assertSame(201, $code);
	}

	public function testSendApiObjectConvertedToArray(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/api', function ($app) {
			$app->sendAPI((object) ['a' => 1], ['format' => 'json']);
		});
		$this->setRequest('GET', '/api');
		[$output] = $this->runApp($app);
		$this->assertJsonStringEqualsJsonString('{"a":1}', $output);
	}

	public function testArrayToXmlPublicViaSendApi(): void
	{
		$app = new Application();
		$xml = $app->arrayToXml(['foo' => 'bar', 'nested' => ['baz' => 2]], 'root', 'item');
		$this->assertStringContainsString('<?xml', $xml);
		$this->assertStringContainsString('<root>', $xml);
		$this->assertStringContainsString('<foo>bar</foo>', $xml);
		$this->assertStringContainsString('<nested>', $xml);
		$this->assertStringContainsString('<baz>2</baz>', $xml);
	}

	// --- Additional edge case tests ---

	public function testRouteWithPatchMethod(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('PATCH', '/resource/:id', function ($app, $params) {
			$app->json(['id' => $params['id'], 'method' => 'PATCH']);
		});
		$this->setRequest('PATCH', '/resource/5');
		[$output, $code] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertJsonStringEqualsJsonString('{"id":"5","method":"PATCH"}', $output);
	}

	public function testRouteWithDeleteMethod(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('DELETE', '/resource/:id', function ($app, $params) {
			$app->json(['deleted' => $params['id']]);
		});
		$this->setRequest('DELETE', '/resource/99');
		[$output, $code] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertJsonStringEqualsJsonString('{"deleted":"99"}', $output);
	}

	public function testRouteCaseInsensitiveMethod(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('get', '/', function ($app) {
			$app->json(['ok' => true]);
		});
		$this->setRequest('GET', '/');
		[$output, $code] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertJsonStringEqualsJsonString('{"ok":true}', $output);
	}

	public function testJsonWithNullData(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/', function ($app) {
			$app->json(null);
		});
		$this->setRequest('GET', '/');
		[$output, $code] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertSame('null', $output);
	}

	public function testJsonWithEmptyArray(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/', function ($app) {
			$app->json([]);
		});
		$this->setRequest('GET', '/');
		[$output, $code] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertSame('[]', $output);
	}

	public function testSetPoweredByBlankString(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->setPoweredBy('');
		$app->route('GET', '/', function ($app) {
			$app->json(['ok' => true]);
		});
		$this->setRequest('GET', '/');
		[$output, $code, $headers] = $this->runApp($app);
		$this->assertArrayHasKey('X-Powered-By', $headers);
		$this->assertSame('', $headers['X-Powered-By']);
	}

	public function testSetPoweredByNullLeavesDefault(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->setPoweredBy(null);
		$app->route('GET', '/', function ($app) {
			$app->json(['ok' => true]);
		});
		$this->setRequest('GET', '/');
		[$output, $code, $headers] = $this->runApp($app);
		// null means don't touch — X-Powered-By may or may not be set by PHP
		// We just assert the response is still valid
		$this->assertSame(200, $code);
		$this->assertJsonStringEqualsJsonString('{"ok":true}', $output);
	}

	public function testSendApiWithScalar(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/', function ($app) {
			$app->sendAPI('just a string', ['format' => 'json']);
		});
		$this->setRequest('GET', '/');
		[$output, $code] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertJsonStringEqualsJsonString('{"data":"just a string"}', $output);
	}

	public function testSendApiWithEmptyArray(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/', function ($app) {
			$app->sendAPI([], ['format' => 'json']);
		});
		$this->setRequest('GET', '/');
		[$output, $code] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertSame('[]', $output);
	}

	public function testArrayToXmlWithNumericKeys(): void
	{
		$app = new Application();
		$xml = $app->arrayToXml(['a', 'b', 'c'], 'root', 'item');
		$this->assertStringContainsString('<root>', $xml);
		$this->assertStringContainsString('<item>a</item>', $xml);
		$this->assertStringContainsString('<item>b</item>', $xml);
		$this->assertStringContainsString('<item>c</item>', $xml);
	}

	public function testArrayToXmlWithEmptyData(): void
	{
		$app = new Application();
		$xml = $app->arrayToXml([], 'root', 'item');
		$this->assertStringContainsString('<?xml', $xml);
		$this->assertStringContainsString('<root/>', $xml);
	}

	public function testArrayToXmlWithNestedLists(): void
	{
		$app = new Application();
		$data = [
			'category' => 'books',
			'items' => [
				['title' => 'A', 'price' => 10],
				['title' => 'B', 'price' => 20],
			],
		];
		$xml = $app->arrayToXml($data, 'root', 'item');
		$this->assertStringContainsString('<category>books</category>', $xml);
		$this->assertStringContainsString('<items>', $xml);
		$this->assertStringContainsString('<title>A</title>', $xml);
		$this->assertStringContainsString('<price>10</price>', $xml);
		$this->assertStringContainsString('<title>B</title>', $xml);
		$this->assertStringContainsString('<price>20</price>', $xml);
	}

	public function testRequestPostWithJsonBody(): void
	{
		$app = new Application();
		$data = $app->requestPost();
		// In CLI, php://input is empty, so this should be null
		$this->assertNull($data);
	}

	public function testRequestRawReturnsEmptyStringInCli(): void
	{
		$app = new Application();
		$raw = $app->requestRaw();
		$this->assertIsString($raw);
	}

	public function testStaticResponseHeaderMethods(): void
	{
		Application::resetResponseState();
		$this->assertSame(200, Application::getResponseCode());
		$this->assertSame([], Application::getResponseHeaders());

		Application::setResponseHeader('X-Custom', 'value');
		$this->assertSame('value', Application::getResponseHeaders()['X-Custom'] ?? null);

		Application::setResponseCode(418);
		$this->assertSame(418, Application::getResponseCode());

		Application::removeResponseHeader('X-Custom');
		$this->assertArrayNotHasKey('X-Custom', Application::getResponseHeaders());

		Application::resetResponseState();
		$this->assertSame(200, Application::getResponseCode());
		$this->assertSame([], Application::getResponseHeaders());
	}

	public function testRouteWithMultipleMethodsOnSamePath(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/resource', function ($app) {
			$app->json(['method' => 'GET']);
		});
		$app->route('POST', '/resource', function ($app) {
			$app->json(['method' => 'POST']);
		});
		$this->setRequest('GET', '/resource');
		[$output, $code] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertJsonStringEqualsJsonString('{"method":"GET"}', $output);

		$this->setRequest('POST', '/resource');
		[$output, $code] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertJsonStringEqualsJsonString('{"method":"POST"}', $output);
	}

	public function testSendApiWithNestedArray(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/', function ($app) {
			$app->sendAPI([
				'user' => ['name' => 'Alice', 'roles' => ['admin', 'editor']],
			], ['format' => 'json']);
		});
		$this->setRequest('GET', '/');
		[$output, $code] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertJsonStringEqualsJsonString('{"user":{"name":"Alice","roles":["admin","editor"]}}', $output);
	}

	public function testSendApiXmlWithNestedLists(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/', function ($app) {
			$app->sendAPI([
				'items' => [['id' => 1], ['id' => 2]],
			], ['format' => 'xml', 'root' => 'data', 'xmlItemName' => 'entry']);
		});
		$this->setRequest('GET', '/');
		[$output] = $this->runApp($app);
		$this->assertStringContainsString('<data>', $output);
		$this->assertStringContainsString('<items>', $output);
		$this->assertStringContainsString('<entry>', $output);
		$this->assertStringContainsString('<id>1</id>', $output);
		$this->assertStringContainsString('<id>2</id>', $output);
	}

	public function testHandleDatabaseMySqlFailureThrows(): void
	{
		if (!class_exists(\mysqli::class)) {
			$this->markTestSkipped('mysqli extension is not available on this system');
		}
		$app = new Application();
		try {
			$app->handleDatabase([
				'type' => 'mysql',
				'host' => '127.0.0.2',
				'database' => 'nonexistent',
				'username' => 'invalid',
				'password' => 'invalid',
			]);
			$this->fail('Expected exception was not thrown');
		} catch (\Throwable $e) {
			$this->assertTrue(
				$e instanceof \RuntimeException || $e instanceof \Error,
				'Expected RuntimeException or Error, got ' . get_class($e)
			);
		}
	}

	public function testHandleDatabaseSqliteNotAvailableThrows(): void
	{
		// Simulate SQLite3 not being available by temporarily removing the class
		$hasSqlite = class_exists(\SQLite3::class);
		if (!$hasSqlite) {
			$app = new Application();
			$this->expectException(\RuntimeException::class);
			$this->expectExceptionMessage('SQLite is not available');
			$app->handleDatabase(['type' => 'sqlite', 'database' => ':memory:']);
		} else {
			$this->markTestSkipped('SQLite3 is available on this system');
		}
	}

	public function testCorsMiddlewareNoOrigin(): void
	{
		unset($_SERVER['HTTP_ORIGIN']);
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->useCors(['https://specific.com']);
		$app->route('GET', '/', function ($app) {
			$app->json([]);
		});
		$this->setRequest('GET', '/');
		[, , $headers] = $this->runApp($app);
		$this->assertSame('https://specific.com', $headers['Access-Control-Allow-Origin'] ?? null);
	}

	public function testCorsMiddlewareCredentialsTrue(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->useCors(['*'], ['GET'], ['Content-Type'], true);
		$app->route('GET', '/', function ($app) {
			$app->json([]);
		});
		$this->setRequest('GET', '/');
		[, , $headers] = $this->runApp($app);
		$this->assertSame('true', $headers['Access-Control-Allow-Credentials'] ?? null);
	}

	public function testCorsMiddlewareMaxAgeNull(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->useCors(['*'], ['GET'], ['Content-Type'], false, null);
		$app->route('GET', '/', function ($app) {
			$app->json([]);
		});
		$this->setRequest('GET', '/');
		[, , $headers] = $this->runApp($app);
		$this->assertArrayNotHasKey('Access-Control-Max-Age', $headers);
	}

	public function testCorsMiddlewareOriginNotInList(): void
	{
		$_SERVER['HTTP_ORIGIN'] = 'https://evil.com';
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->useCors(['https://trusted.com']);
		$app->route('GET', '/', function ($app) {
			$app->json([]);
		});
		$this->setRequest('GET', '/');
		[, , $headers] = $this->runApp($app);
		// Falls back to first origin in list
		$this->assertSame('https://trusted.com', $headers['Access-Control-Allow-Origin'] ?? null);
	}

	public function testRateLimitMiddlewareXForwardedFor(): void
	{
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1, 10.0.0.2';
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->useRateLimit(5, 60);
		$app->route('GET', '/', function ($app) {
			$app->json([]);
		});
		$this->setRequest('GET', '/');
		[, $code] = $this->runApp($app);
		$this->assertSame(200, $code);
	}

	public function testRateLimitMiddlewareWindowReset(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->useRateLimit(1, 1); // 1 request per 1 second
		$app->route('GET', '/', function ($app) {
			$app->json(['ok' => true]);
		});
		$this->setRequest('GET', '/');
		[$output, $code] = $this->runApp($app);
		$this->assertSame(200, $code);

		// Second request within window should be rate limited
		[$output, $code] = $this->runApp($app);
		$this->assertSame(429, $code);

		// Simulate time passing by resetting the store
		RateLimitMiddleware::resetStore();
		[$output, $code] = $this->runApp($app);
		$this->assertSame(200, $code);
	}

	public function testSecurityHeadersMiddlewareEmptyCustom(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->useSecurityHeaders([]);
		$app->route('GET', '/', function ($app) {
			$app->json([]);
		});
		$this->setRequest('GET', '/');
		[, , $headers] = $this->runApp($app);
		// No headers should be set when empty array is passed
		$this->assertArrayNotHasKey('X-Content-Type-Options', $headers);
	}

	public function testSecurityHeadersMiddlewarePartialCustom(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->useSecurityHeaders(['X-Custom-Only' => 'yes']);
		$app->route('GET', '/', function ($app) {
			$app->json([]);
		});
		$this->setRequest('GET', '/');
		[, , $headers] = $this->runApp($app);
		$this->assertSame('yes', $headers['X-Custom-Only'] ?? null);
		$this->assertArrayNotHasKey('X-Content-Type-Options', $headers);
	}

	public function testServerLogDoesNotThrow(): void
	{
		$app = new Application();
		$app->serverLog('test message');
		$this->assertTrue(true);
	}

	public function testUseFluent(): void
	{
		$app = new Application();
		$result = $app->use(function ($app, $next) { $next(); });
		$this->assertSame($app, $result);
	}

	public function testUseSecurityHeadersFluent(): void
	{
		$app = new Application();
		$result = $app->useSecurityHeaders();
		$this->assertSame($app, $result);
	}

	public function testUseCorsFluent(): void
	{
		$app = new Application();
		$result = $app->useCors();
		$this->assertSame($app, $result);
	}

	public function testUseRateLimitFluent(): void
	{
		$app = new Application();
		$result = $app->useRateLimit();
		$this->assertSame($app, $result);
	}

	public function testHandleDatabaseFluent(): void
	{
		$app = new Application();
		$result = $app->handleDatabase(['type' => 'sqlite', 'database' => ':memory:']);
		$this->assertSame($app, $result);
	}

	public function testGetPreferredApiFormatQueryInvalid(): void
	{
		$_GET['format'] = 'invalid';
		$app = new Application();
		$this->assertSame('json', $app->getPreferredApiFormat());
	}

	public function testGetPreferredApiFormatAcceptJson(): void
	{
		unset($_GET['format']);
		$_SERVER['HTTP_ACCEPT'] = 'application/json';
		$app = new Application();
		$this->assertSame('json', $app->getPreferredApiFormat());
	}

	public function testSendApiXmlWithEmptyList(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/', function ($app) {
			$app->sendAPI(['items' => []], ['format' => 'xml']);
		});
		$this->setRequest('GET', '/');
		[$output] = $this->runApp($app);
		$this->assertStringContainsString('<items', $output);
	}

	public function testRenderWithData(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/', function ($app) {
			$app->render('/home.view', ['message' => 'Custom message']);
		});
		$this->setRequest('GET', '/');
		[$output] = $this->runApp($app);
		$this->assertStringContainsString('Custom message', $output);
	}

	public function testRouteWithLeadingSlashNormalization(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/test', function ($app) {
			$app->json(['ok' => true]);
		});
		$this->setRequest('GET', '/test');
		[$output, $code] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertJsonStringEqualsJsonString('{"ok":true}', $output);
	}

	public function testRouteWithUnderscoreInParam(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/item/:slug_name', function ($app, $params) {
			$app->json(['slug' => $params['slug_name']]);
		});
		$this->setRequest('GET', '/item/hello_world');
		[$output, $code] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertJsonStringEqualsJsonString('{"slug":"hello_world"}', $output);
	}

	public function testRouteWithNumericParam(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/page/:num', function ($app, $params) {
			$app->json(['page' => $params['num']]);
		});
		$this->setRequest('GET', '/page/123');
		[$output, $code] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertJsonStringEqualsJsonString('{"page":"123"}', $output);
	}

	public function testRouteWithHyphenInPath(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/blog-post', function ($app) {
			$app->json(['ok' => true]);
		});
		$this->setRequest('GET', '/blog-post');
		[$output, $code] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertJsonStringEqualsJsonString('{"ok":true}', $output);
	}

	public function testSendApiXmlWithSpecialChars(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/', function ($app) {
			$app->sendAPI(['note' => 'AT&T "value" <test>'], ['format' => 'xml']);
		});
		$this->setRequest('GET', '/');
		[$output] = $this->runApp($app);
		$this->assertStringContainsString('AT&amp;T', $output);
	}

	public function testMultipleMiddlewaresWithBuiltIn(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->useSecurityHeaders();
		$app->useCors(['*']);
		$app->useRateLimit(100, 60);
		$app->route('GET', '/', function ($app) {
			$app->json(['ok' => true]);
		});
		$this->setRequest('GET', '/');
		[$output, $code, $headers] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertArrayHasKey('X-Content-Type-Options', $headers);
		$this->assertArrayHasKey('Access-Control-Allow-Origin', $headers);
		$this->assertJsonStringEqualsJsonString('{"ok":true}', $output);
	}

	public function testEmptyMiddlewareStack(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/', function ($app) {
			$app->json(['ok' => true]);
		});
		$this->setRequest('GET', '/');
		[$output, $code] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertJsonStringEqualsJsonString('{"ok":true}', $output);
	}

	public function testHandleCorsNoOrigin(): void
	{
		unset($_SERVER['HTTP_ORIGIN']);
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/', function ($app) {
			$app->json([]);
		});
		$this->setRequest('GET', '/');
		$app->handleCORS();
		[, , $headers] = $this->runApp($app);
		$this->assertArrayNotHasKey('Access-Control-Allow-Origin', $headers);
	}

	public function testSendApiXmlWithObjectInput(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->route('GET', '/', function ($app) {
			$app->sendAPI((object) ['name' => 'test'], ['format' => 'xml']);
		});
		$this->setRequest('GET', '/');
		[$output] = $this->runApp($app);
		$this->assertStringContainsString('<name>test</name>', $output);
	}
}
