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
define('ROUTE_PATH',      APP_PATH.'routes/');
define('SESSION_PATH',    STORAGE_PATH.'sessions/');
define('SYS_CONFIG_PATH', SYS_PATH.'config/');
define('SYS_LANG_PATH',   SYS_PATH.'language/');
define('VIEW_PATH',       APP_PATH.'views/');

// --------------------------------------------------------------
// Load the configuration manager and its dependencies.
// --------------------------------------------------------------
require SYS_PATH.'facades'.EXT;
require SYS_PATH.'config'.EXT;
require SYS_PATH.'arr'.EXT;

// --------------------------------------------------------------
// Bootstrap the IoC container.
// --------------------------------------------------------------
require SYS_PATH.'container'.EXT;

$dependencies = require SYS_CONFIG_PATH.'container'.EXT;

if (file_exists($path = CONFIG_PATH.'container'.EXT))
{
	$dependencies = array_merge($dependencies, require $path);
}

$env = (isset($_SERVER['LARAVEL_ENV'])) ? $_SERVER['LARAVEL_ENV'] : null;

if ( ! is_null($env) and file_exists($path = CONFIG_PATH.$env.'/container'.EXT))
{
	$dependencies = array_merge($dependencies, require $path);
}

$container = new Container($dependencies);

IoC::$container = $container;

// --------------------------------------------------------------
// Register the auto-loader on the auto-loader stack.
// --------------------------------------------------------------
spl_autoload_register(array($container->resolve('laravel.loader'), 'load'));

// --------------------------------------------------------------
// Set the application environment configuration option.
// --------------------------------------------------------------
$container->resolve('laravel.config')->set('application.env', $env);

// --------------------------------------------------------------
// Define some convenient global functions.
// --------------------------------------------------------------
function e($value)
{
	return IoC::container()->resolve('laravel.html')->entities($value);
}

function __($key, $replacements = array(), $language = null)
{
	return IoC::container()->resolve('laravel.lang')->line($key, $replacements, $language);
}