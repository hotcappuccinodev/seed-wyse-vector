<?php namespace Laravel;

require 'constants.php';

/**
 * Load the classes that can't be resolved through the auto-loader.
 * These are typically classes that are used by the auto-loader or
 * configuration classes, and therefore cannot be auto-loaded.
 */
require SYS_PATH.'arr'.EXT;
require SYS_PATH.'config'.EXT;
require SYS_PATH.'loader'.EXT;

/**
 * Load some core configuration files by default so we don't have to
 * let them fall through the Config parser. This will allow us to
 * load these files faster for each request.
 */
Config::load('application');
Config::load('container');
Config::load('session');

/**
 * Bootstrap the application inversion of control (IoC) container.
 * The container provides the convenient resolution of objects and
 * their dependencies, allowing for flexibility and testability
 * within the framework and application.
 */
require SYS_PATH.'container'.EXT;

$container = new Container(Config::$items['container']);

IoC::$container = $container;

unset($container);

/**
 * Register the application auto-loader. The auto-loader is responsible
 * for the lazy-loading of all of the Laravel core classes, as well as
 * the developer created libraries and models.
 */
spl_autoload_register(array('Laravel\\Loader', 'load'));

Loader::$aliases = Config::$items['application']['aliases'];

/**
 * Define a few convenient global functions. These functions primarily
 * exists to provide a short way of accessing functions commonly used
 * in views, allowing the reduction of code noise.
 */
function e($value)
{
	return HTML::entities($value);
}

function __($key, $replacements = array(), $language = null)
{
	return Lang::line($key, $replacements, $language);
}