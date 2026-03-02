<?php
/**
 * Narciso samples — index
 *
 * When you run: php -S localhost:8080 -t samples
 * Opening http://localhost:8080/ shows this page with links to each sample.
 */
$samples = [
	['file' => '01_basic_routing.php',          'title' => 'Basic routing',              'desc' => 'GET/POST, path params, json(), 404'],
	['file' => '02_middlewares.php',            'title' => 'Middlewares',                'desc' => 'use() with callable and MiddlewareInterface'],
	['file' => '03_security_cors_rate_limit.php','title' => 'Security, CORS, rate limit','desc' => 'useSecurityHeaders, useCors, useRateLimit'],
	['file' => '04_database.php',               'title' => 'Database',                  'desc' => 'handleDatabase (SQLite :memory:)'],
	['file' => '05_api_json_xml.php',           'title' => 'API JSON/XML',               'desc' => 'sendAPI, ?format=xml, getPreferredApiFormat'],
	['file' => '06_views_render.php',           'title' => 'Views & render',             'desc' => 'setViewPath, render(), redirect()'],
	['file' => '07_technology_signature.php',  'title' => 'Technology signature',       'desc' => 'setPoweredBy (hide or change X-Powered-By)'],
	['file' => '08_full_application.php',      'title' => 'Full application',           'desc' => 'All features combined'],
];
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Narciso samples</title>
	<style>
		body { font-family: system-ui, sans-serif; max-width: 640px; margin: 2rem auto; padding: 0 1rem; }
		h1 { font-size: 1.5rem; }
		ul { list-style: none; padding: 0; }
		li { margin: 0.75rem 0; padding: 0.75rem; background: #f5f5f5; border-radius: 6px; }
		a { color: #0066cc; text-decoration: none; font-weight: 500; }
		a:hover { text-decoration: underline; }
		.desc { color: #555; font-size: 0.9rem; margin-top: 0.25rem; }
	</style>
</head>
<body>
	<h1>Narciso samples</h1>
	<p>Choose a sample to run. Each file is a standalone entry point.</p>
	<ul>
		<?php foreach ($samples as $s): ?>
		<li>
			<a href="/<?php echo htmlspecialchars($s['file']); ?>"><?php echo htmlspecialchars($s['title']); ?></a>
			<div class="desc"><?php echo htmlspecialchars($s['desc']); ?></div>
		</li>
		<?php endforeach; ?>
	</ul>
</body>
</html>
