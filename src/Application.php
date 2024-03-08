<?php

namespace Marcuwynu23\Narciso;

final class Application
{
	private $viewPath;
	public $db;
	public function __construct()
	{
		$this->serverLog("Narciso Application. ");
	}

	public function serverLog($content)
	{
		error_log(print_r($content, true));
	}
	public function setViewPath($path)
	{
		$this->viewPath = $path;
	}

	public function handleSession($name = "Narciso")
	{
		session_name($name);
		session_start();
	}
	public function handleCORS()
	{
		if (isset($_SERVER['HTTP_ORIGIN'])) {
			header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
			header('Access-Control-Allow-Credentials: true');
			header('Access-Control-Max-Age: 86400');
		}
		// Access-Control headers are received during OPTIONS requests
		if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
				header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
				header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

			exit(0);
		}
	}

	public function handleDatabase($config)
	{
		$type = $config["type"] ?? "mysql";
		$host = $config["host"] ?? "localhost";
		$user = $config["user"] ?? "user";
		$password = $config["password"] ?? "";
		$database = $config["database"] ?? "test";

		if ($type == "mysql") {
			$db = new \mysqli($host, $user, $password, $database);
			if ($db->connect_error) {
				die('Connect Error (' . $db->connect_errno . ') ' . $db->connect_error);
			}
			$this->db = $db;
		} else if ($type == "sqlite") {
			$db = new \SQLite3($database);
			$this->db = $db;
		}
	}
	public function requestPost()
	{
		$content = file_get_contents('php://input');
		$data = json_decode($content, true);
		return $data;
	}


	public function route($method, $route, $callback)
	{
		if ($_SERVER['REQUEST_METHOD'] == $method && $_SERVER['REQUEST_URI'] == $route) {
			$callback();
		}
	}
	public function render($view, $data = [])
	{
		extract($data);
		require_once $this->viewPath . $view . '.php';
	}

	public function redirect($url)
	{
		header("Location: $url");
	}

	public function json($data)
	{
		header("Content-Type: application/json");
		echo json_encode($data);
	}
}