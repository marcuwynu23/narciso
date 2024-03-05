<div align="center">

# Narciso

[![GitHub license](https://img.shields.io/github/license/marcuwynu23/narciso)](https://github.com/marcuwynu23/narciso/blob/main/LICENSE)
[![GitHub stars](https://img.shields.io/github/stars/marcuwynu23/narciso)](https://github.com/marcuwynu23/narciso/stargazers)
[![GitHub issues](https://img.shields.io/github/issues/marcuwynu23/narciso)](https://github.com/marcuwynu23/narciso/issues)

</div>
Narciso is a lightweight web library built on top of native PHP, designed to simplify and enhance web development tasks. It provides developers with a set of tools and functionalities to streamline common web development processes such as handling HTTP requests, managing sessions, accessing databases, and generating dynamic content.



packagist: https://packagist.org/packages/marcuwynu23/narciso

## Inspiration

Narciso draws its inspiration from the story of Narcissus in Greek mythology. In the myth, Narcissus is a figure known for his beauty, but also for his tragic demise due to his obsession with his own reflection. Similarly, Narciso emphasizes the importance of self-reflection and introspection in web development, encouraging developers to build elegant and efficient applications while acknowledging that perfection is not always attainable. It recognizes that not all frameworks are flawless, but aims to cultivate an appreciation for the craftsmanship and beauty of well-crafted software, despite its imperfections.

Additionally, personally I developed this project to learn the web core of PHP native. Through Narciso, I aimed to **deepen my understanding of web development principles and practices**, honing my skills in building robust and secure web applications using PHP's native capabilities.

Furthermore, as an artistic movement, this project is inspired by the contemporary cultural phenomenon of narcissism and its implications on society. By addressing themes related to self-absorption and self-obsession, Narciso seeks to raise awareness about mental health issues and promote a more balanced approach to technology and self-expression.

## Features

- **HTTP Request Handling**: Easily handle HTTP requests and extract data from them.
- **Session Management**: Manage user sessions with ease, including session name customization.
- **Cross-Origin Resource Sharing (CORS)**: Implement CORS headers for handling cross-origin requests.
- **Database Access**: Connect to MySQL or SQLite databases and perform database operations securely.
- **Routing**: Define routes and callbacks for handling different HTTP methods and URIs.
- **View Rendering**: Render view files with data passed from the application.
- **Redirection**: Redirect users to different URLs within the application.
- **JSON Response**: Send JSON responses with appropriate content-type headers.

## Installation

You can install Narciso via Composer. Add the following to your `composer.json` file:

```json
{
  "require": {
    "marcuwynu23/narciso": "^0.0.2"
  }
}
```

or using composer commandline:

```sh
composer require marcuwynu23/narciso
```

Then run composer install to install the library.

## Usage

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';


use Marcuwynu23\Narciso\Application;


$app = new Application();

$app->setViewPath(__DIR__ . '/views');
$app->handleSession();
$app->handleCORS();

$app->handleDatabase([
	'type' => 'mysql',
	'host' => 'localhost',
	'database' => 'northwind',
	'username' => 'user',
	'password' => 'user',
]);



$app->route('GET', '/', function () use ($app) {
	return $app->render('/home/index.view');
});



$app->route('GET', '/json', function () use ($app) {
	return $app->json(['message' => 'Hello World']);
});

```

## Contributing

Contributions are welcome! If you have any suggestions, improvements, or new guides to add, feel free to contribute to this repository.

---

Happy coding!
