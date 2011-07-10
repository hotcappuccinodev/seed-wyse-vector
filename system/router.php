<?php namespace System;

class Router {

	/**
	 * All of the loaded routes.
	 *
	 * @var array
	 */
	public static $routes;

	/**
	 * Search a set of routes for the route matching a method and URI.
	 *
	 * @param  string  $method
	 * @param  string  $uri
	 * @return Route
	 */
	public static function route($method, $uri)
	{
		if (is_null(static::$routes))
		{
			static::$routes = static::load($uri);
		}

		// Put the request method and URI in route form. 
		// Routes begin with the request method and a forward slash.
		$uri = $method.' /'.trim($uri, '/');

		// Is there an exact match for the request?
		if (isset(static::$routes[$uri]))
		{
			return Request::$route = new Route($uri, static::$routes[$uri]);
		}

		foreach (static::$routes as $keys => $callback)
		{
			// Only check routes that have multiple URIs or wildcards.
			// Other routes would have been caught by the check for literal matches.
			if (strpos($keys, '(') !== false or strpos($keys, ',') !== false )
			{
				foreach (explode(', ', $keys) as $key)
				{
					if (preg_match('#^'.$key = static::translate_wildcards($key).'$#', $uri))
					{
						return Request::$route = new Route($keys, $callback, static::parameters($uri, $key));
					}
				}				
			}
		}
	}

	/**
	 * Load the appropriate route file for the request URI.
	 *
	 * @param  string  $uri
	 * @return array
	 */
	public static function load($uri)
	{
		return (is_dir(APP_PATH.'routes')) ? static::load_from_directory($uri) : require APP_PATH.'routes'.EXT;
	}

	/**
	 * Load the appropriate route file from the routes directory.
	 *
	 * @param  string  $uri
	 * @return array
	 */
	private static function load_from_directory($uri)
	{
		// If it exists, The "home" routes file is loaded for every request. This allows
		// for "catch-all" routes such as http://example.com/username...
		$home = (file_exists($path = APP_PATH.'routes/home'.EXT)) ? require $path : array();

		if ($uri == '')
		{
			return $home;
		}

		$segments = explode('/', $uri);

		return (file_exists($path = APP_PATH.'routes/'.$segments[0].EXT)) ? array_merge(require $path, $home) : $home;
	}

	/**
	 * Translate route URI wildcards to regular expressions.
	 *
	 * @param  string  $key
	 * @return string
	 */
	private static function translate_wildcards($key)
	{
		return str_replace(':num', '[0-9]+', str_replace(':any', '[a-zA-Z0-9\-_]+', $key));
	}

	/**
	 * Extract the parameters from a URI based on a route URI.
	 *
	 * Any route segment wrapped in parentheses is considered a parameter.
	 *
	 * @param  string  $uri
	 * @param  string  $route
	 * @return array
	 */
	public static function parameters($uri, $route)
	{
		return array_values(array_intersect_key(explode('/', $uri), preg_grep('/\(.+\)/', explode('/', $route))));	
	}	

}