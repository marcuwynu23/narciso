<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Home — Narciso sample</title>
</head>
<body>
	<h1>Home</h1>
	<?php if (!empty($message)): ?>
		<p><?php echo htmlspecialchars($message); ?></p>
	<?php endif; ?>
	<p>Hello World! Rendered from <code>views/home.view.php</code>.</p>
</body>
</html>
