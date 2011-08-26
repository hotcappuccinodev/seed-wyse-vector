<?php namespace Laravel;

// --------------------------------------------------------------
// Define the PHP file extension.
// --------------------------------------------------------------
define('EXT', '.php');

// --------------------------------------------------------------
// Define the core framework paths.
// --------------------------------------------------------------
define('APP_PATH',     realpath($application).'/');
define('BASE_PATH',    realpath(str_replace('laravel', '', $laravel)).'/');
define('PACKAGE_PATH', realpath($packages).'/');
define('PUBLIC_PATH',  realpath($public).'/');
define('STORAGE_PATH', realpath($storage).'/');
define('SYS_PATH',     realpath($laravel).'/');

unset($laravel, $application, $config, $packages, $public, $storage);

// --------------------------------------------------------------
// Define various other framework paths.
// --------------------------------------------------------------
define('CACHE_PATH',      STORAGE_PATH.'cache/');
define('CONFIG_PATH',     APP_PATH.'config/');
define('CONTROLLER_PATH', APP_PATH.'controllers/');
define('DATABASE_PATH',   STORAGE_PATH.'database/');
define('LANG_PATH',       APP_PATH.'language/');
define('SCRIPT_PATH',     PUBLIC_PATH.'js/');
define('SESSION_PATH',    STORAGE_PATH.'sessions/');
define('STYLE_PATH',      PUBLIC_PATH.'css/');
define('SYS_CONFIG_PATH', SYS_PATH.'config/');
define('SYS_LANG_PATH',   SYS_PATH.'language/');
define('VIEW_PATH',       APP_PATH.'views/');

// --------------------------------------------------------------
// Load the classes used by the auto-loader.
// --------------------------------------------------------------
require SYS_PATH.'loader'.EXT;
require SYS_PATH.'config'.EXT;
require SYS_PATH.'arr'.EXT;

// --------------------------------------------------------------
// Register the auto-loader.
// --------------------------------------------------------------
Loader::bootstrap(Config::get('aliases'), array(APP_PATH.'libraries/', APP_PATH.'models/'));

spl_autoload_register(array('Laravel\\Loader', 'load'));

// --------------------------------------------------------------
// Bootstrap the IoC container.
// --------------------------------------------------------------
require SYS_PATH.'ioc'.EXT;

IoC::bootstrap(Config::get('container'));

// --------------------------------------------------------------
// Set the error reporting and display levels.
// --------------------------------------------------------------
error_reporting(E_ALL | E_STRICT);

ini_set('display_errors', 'Off');

// --------------------------------------------------------------
// Register the error / exception handlers.
// --------------------------------------------------------------
$error_dependencies = function()
{
	require_once SYS_PATH.'exception/handler'.EXT;
	require_once SYS_PATH.'exception/examiner'.EXT;
	require_once SYS_PATH.'file'.EXT;
};

set_exception_handler(function($e) use ($error_dependencies)
{
	call_user_func($error_dependencies);

	Exception\Handler::make(new Exception\Examiner($e, new File))->handle();
});

set_error_handler(function($number, $error, $file, $line) use ($error_dependencies)
{
	call_user_func($error_dependencies);

	$e = new \ErrorException($error, $number, 0, $file, $line);

	Exception\Handler::make(new Exception\Examiner($e, new File))->handle();
});

register_shutdown_function(function() use ($error_dependencies)
{
	if ( ! is_null($error = error_get_last()))
	{
		call_user_func($error_dependencies);

		$e = new \ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']);

		Exception\Handler::make(new Exception\Examiner($e, new File))->handle();
	}	
});

// --------------------------------------------------------------
// Set the default timezone.
// --------------------------------------------------------------
date_default_timezone_set(Config::get('application.timezone'));

// --------------------------------------------------------------
// Load all of the core routing and response classes.
// --------------------------------------------------------------
require SYS_PATH.'input'.EXT;
require SYS_PATH.'request'.EXT;
require SYS_PATH.'response'.EXT;
require SYS_PATH.'routing/route'.EXT;
require SYS_PATH.'routing/router'.EXT;
require SYS_PATH.'routing/handler'.EXT;

// --------------------------------------------------------------
// Initialize the request instance for the request.
// --------------------------------------------------------------
$request = new Request($_SERVER);

IoC::container()->instance('laravel.request', $request);

// --------------------------------------------------------------
// Hydrate the input for the current request.
// --------------------------------------------------------------
$request->input = new Input($request->method(), $request->is_spoofed(), $_GET, $_POST, $_FILES, new Cookie($_COOKIE));

// --------------------------------------------------------------
// Load the session.
// --------------------------------------------------------------
if (Config::get('session.driver') != '')
{
	Session\Manager::driver()->start($request->input->cookies->get('laravel_session'));
}

// --------------------------------------------------------------
// Load the packages that are in the auto-loaded packages array.
// --------------------------------------------------------------
if (count(Config::get('application.packages')) > 0)
{
	require SYS_PATH.'package'.EXT;

	Package::load(Config::get('application.packages'));
}

// --------------------------------------------------------------
// Route the request and get the response from the route.
// --------------------------------------------------------------
$route = IoC::container()->resolve('laravel.routing.router')->route();

$response = ( ! is_null($route)) ? IoC::container()->resolve('laravel.routing.handler')->handle($route) : new Error('404');

// --------------------------------------------------------------
// Stringify the response.
// --------------------------------------------------------------
$response->content = $response->render();

// --------------------------------------------------------------
// Close the session.
// --------------------------------------------------------------
if (Config::get('session.driver') != '')
{
	$driver = Session\Manager::driver();

	$driver->flash('laravel_old_input', $request->input->get());

	$driver->close($request->input->cookies);

	if ($driver instanceof Session\Sweeper and mt_rand(1, 100) <= 2)
	{
		$driver->sweep(time() - (Config::get('session.lifetime') * 60));
	}
}

// --------------------------------------------------------------
// Send the response to the browser.
// --------------------------------------------------------------
$response->send();