<?php namespace Laravel;

// --------------------------------------------------------------
// Define the framework constants.
// --------------------------------------------------------------
require 'bootstrap/constants.php';

// --------------------------------------------------------------
// Load the application and the core application components.
// --------------------------------------------------------------
require SYS_PATH.'bootstrap/core'.EXT;

// --------------------------------------------------------------
// Set the error reporting and display levels.
// --------------------------------------------------------------
error_reporting(E_ALL | E_STRICT);

ini_set('display_errors', 'Off');

// --------------------------------------------------------------
// Register the error / exception handlers.
// --------------------------------------------------------------
set_exception_handler(function($e) use ($application)
{
	call_user_func($application->config->get('error.handler'), $e);
});

set_error_handler(function($number, $error, $file, $line) use ($application)
{
	$exception = new \ErrorException($error, $number, 0, $file, $line);

	call_user_func($application->config->get('error.handler'), $exception);
});

register_shutdown_function(function() use ($application)
{
	if ( ! is_null($error = error_get_last()))
	{
		$exception = new \ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']);

		call_user_func($application->config->get('error.handler'), $exception);
	}	
});

// --------------------------------------------------------------
// Set the default timezone.
// --------------------------------------------------------------
date_default_timezone_set($application->config->get('application.timezone'));

// --------------------------------------------------------------
// Initialize the request instance for the request.
// --------------------------------------------------------------
$application->request = new Request($_SERVER, $application->config->get('application.url'));

$application->container->instance('laravel.request', $application->request);

// --------------------------------------------------------------
// Hydrate the input for the current request.
// --------------------------------------------------------------
$input = array();

if ($application->request->method == 'GET')
{
	$input = $_GET;
}
elseif ($application->request->method == 'POST')
{
	$input = $_POST;
}
elseif ($application->request->method == 'PUT' or $application->request->method == 'DELETE')
{
	($application->request->spoofed) ? $input = $_POST : parse_str(file_get_contents('php://input'), $input);
}

$application->input = new Input($input, $_FILES, new Cookie($_COOKIE));

$application->container->instance('laravel.input', $application->input);

// --------------------------------------------------------------
// Load the cache manager.
// --------------------------------------------------------------
$application->cache = new Cache\Manager($application->container, $application->config->get('cache.driver'));

$application->container->instance('laravel.cache.manager', $application->cache);

// --------------------------------------------------------------
// Load the database manager.
// --------------------------------------------------------------
if ($application->config->get('database.autoload'))
{
	$connections = $application->config->get('database.connections');

	$application->database = new Database\Manager($connections, $application->config->get('database.default'));

	$application->container->instance('laravel.database.manager', $application->database);

	unset($connections);
}

// --------------------------------------------------------------
// Load the session and session manager.
// --------------------------------------------------------------
if ($application->config->get('session.driver') !== '')
{
	$application->session = Session\Manager::driver($application->container, $application->config->get('session.driver'));

	$application->container->instance('laravel.session.driver', $application->session);

	$application->session->start($application->input->cookies->get('laravel_session'), $application->config->get('session.lifetime'));
}

// --------------------------------------------------------------
// Load the packages that are in the auto-loaded packages array.
// --------------------------------------------------------------
$packages = $application->config->get('application.packages');

if (count($packages) > 0)
{
	$application->package->load($packages);
}

unset($packages);

// --------------------------------------------------------------
// Route the request and get the response from the route.
// --------------------------------------------------------------
$route = $application->container->resolve('laravel.router')->route();

if ( ! is_null($route))
{
	$route->filters = require APP_PATH.'filters'.EXT;

	$response = $route->call($application);
}
else
{
	$response = new Error('404');
}

// --------------------------------------------------------------
// Stringify the response.
// --------------------------------------------------------------
$response->content = $response->render();

// --------------------------------------------------------------
// Close the session.
// --------------------------------------------------------------
if ( ! is_null($application->session))
{
	$application->session->close($application->input, $application->config->get('session'));
}

// --------------------------------------------------------------
// Send the response to the browser.
// --------------------------------------------------------------
$response->send();