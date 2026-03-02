<?php

namespace Marcuwynu23\Narciso\Test;

use Marcuwynu23\Narciso\Application;
use Marcuwynu23\Narciso\Middleware\MiddlewareInterface;

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
		$app->handleCORS();
		$app->route('GET', '/', function ($app) {
			$app->json([]);
		});
		$this->setRequest('GET', '/');
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
}
