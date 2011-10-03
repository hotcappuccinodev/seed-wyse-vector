<?php namespace Laravel;

/**
 * Bootstrap the core framework components like the IoC container,
 * configuration class, and the class auto-loader. Once this file
 * has run, the framework is essentially ready for use.
 */
require 'bootstrap/core.php';

/**
 * Register the framework error handling methods and set the
 * error_reporting levels. This file will register handlers
 * for exceptions, errors, and shutdown.
 */
require SYS_PATH.'bootstrap/errors'.EXT;

/**
 * Set the application's default timezone.
 */
date_default_timezone_set(Config::get('application.timezone'));

/**
 * Load the session and session manager instance. The session
 * payload will be registered in the IoC container as an instance
 * so it can be retrieved easily throughout the application.
 */
if (Config::get('session.driver') !== '')
{
	$session = $container->core('session.manager');

	$container->instance('laravel.session', $session->payload(Config::get('session')));
}

/**
 * Resolve the incoming request instance from the IoC container
 * and route the request to the proper route in the application.
 * If a route is found, the route will be called with the current
 * requst instance. If no route is found, the 404 response will
 * be returned to the browser.
 */
require SYS_PATH.'request'.EXT;
require SYS_PATH.'routing/router'.EXT;
require SYS_PATH.'routing/route'.EXT;
require SYS_PATH.'routing/loader'.EXT;
require SYS_PATH.'routing/caller'.EXT;

$request = $container->core('request');

list($method, $uri) = array($request->method(), $request->uri());

$route = $container->core('routing.router')->route($request, $method, $uri);

if ( ! is_null($route))
{
	$response = $container->core('routing.caller')->call($route);
}
else
{
	$response = Response::error('404');
}

/**
 * Stringify the response. We need to force the response to be
 * stringed before closing the session, since the developer may
 * be using the session within their views, so we cannot age
 * the session data until the view is rendered.
 */
$response->content = $response->render();

/**
 * Close the session and write the active payload to persistent
 * storage. The input for the current request is also flashed
 * to the session so it will be available for the next request
 * via the Input::old method.
 */
if (isset($session))
{
	$flash = array(Input::old_input => $container->core('input')->get());

	$session->close($container->core('session'), Config::get('session'), $flash);
}

/**
 * Finally, we can send the response to the browser.
 */
$response->send();