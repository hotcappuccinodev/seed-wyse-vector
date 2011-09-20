<?php namespace Laravel;

// --------------------------------------------------------------
// Bootstrap the core framework components.
// --------------------------------------------------------------
require 'core.php';

// --------------------------------------------------------------
// Get an instance of the configuration manager.
// --------------------------------------------------------------
$config = $container->resolve('laravel.config');

set_exception_handler(function($e) use ($config)
{
	call_user_func($config->get('error.handler'), $e);
});

set_error_handler(function($number, $error, $file, $line) use ($config)
{
	$exception = new \ErrorException($error, $number, 0, $file, $line);

	call_user_func($config->get('error.handler'), $exception);
});

register_shutdown_function(function() use ($config)
{
	if ( ! is_null($error = error_get_last()))
	{
		$exception = new \ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']);

		call_user_func($config->get('error.handler'), $exception);
	}	
});

// --------------------------------------------------------------
// Set the error reporting and display levels.
// --------------------------------------------------------------
error_reporting(-1);

ini_set('display_errors', 'Off');

// --------------------------------------------------------------
// Set the default timezone.
// --------------------------------------------------------------
date_default_timezone_set($config->get('application.timezone'));

// --------------------------------------------------------------
// Load the session and session manager.
// --------------------------------------------------------------
if ($config->get('session.driver') !== '')
{
	$session = $container->resolve('laravel.session.manager');

	$container->instance('laravel.session', $session->payload($config->get('session')));
}

// --------------------------------------------------------------
// Route the request and get the response from the route.
// --------------------------------------------------------------
$route = $container->resolve('laravel.routing.router')->route($container->resolve('laravel.request'));

if ( ! is_null($route))
{
	$response = $container->resolve('laravel.routing.caller')->call($route);
}
else
{
	$response = $container->resolve('laravel.response')->error('404');
}

// --------------------------------------------------------------
// Stringify the response.
// --------------------------------------------------------------
$response->content = $response->render();

// --------------------------------------------------------------
// Close the session and write the session cookie.
// --------------------------------------------------------------
if (isset($session))
{
	$session->close($container->resolve('laravel.session'), $config->get('session'));
}

// --------------------------------------------------------------
// Send the queued cookies to the browser.
// --------------------------------------------------------------
$container->resolve('laravel.cookie')->send();

// --------------------------------------------------------------
// Send the response to the browser.
// --------------------------------------------------------------
$response->send();