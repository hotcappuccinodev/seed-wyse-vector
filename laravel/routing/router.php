<?php namespace Laravel\Routing; use Laravel\Request;

class Delegate {

	/**
	 * The destination of the route delegate.
	 *
	 * @var string
	 */
	public $destination;

	/**
	 * Create a new route delegate instance.
	 *
	 * @param  string  $destination
	 * @return void
	 */
	public function __construct($destination)
	{
		$this->destination = $destination;
	}

}

class Router {

	/**
	 * The route loader instance.
	 *
	 * @var Loader
	 */
	public $loader;

	/**
	 * The named routes that have been found so far.
	 *
	 * @var array
	 */
	protected $names = array();

	/**
	 * The path the application controllers.
	 *
	 * @var string
	 */
	protected $controllers;

	/**
	 * Create a new router for a request method and URI.
	 *
	 * @param  Loader  $loader
	 * @param  string  $controllers
	 * @return void
	 */
	public function __construct(Loader $loader, $controllers)
	{
		$this->loader = $loader;
		$this->controllers = $controllers;
	}

	/**
	 * Find a route by name.
	 *
	 * The returned array will be identical the array defined in the routes.php file.
	 *
	 * @param  string  $name
	 * @return array
	 */
	public function find($name)
	{
		// First we will check the cache of route names. If we have already found the given route,
		// we will simply return that route from the cache to improve performance.
		if (array_key_exists($name, $this->names)) return $this->names[$name];

		// Spin through every route defined for the application searching for a route that has
		// a name matching the name passed to the method. If the route is found, it will be
		// cached in the array of named routes and returned.
		foreach ($this->loader->everything() as $key => $value)
		{
			if (is_array($value) and isset($value['name']) and $value['name'] === $name)
			{
				return $this->names[$name] = array($key => $value);
			}
		}
	}

	/**
	 * Search the routes for the route matching a request method and URI.
	 *
	 * @param  string   $method
	 * @param  string   $uri
	 * @return Route
	 */
	public function route($method, $uri)
	{
		$routes = $this->loader->load($uri);

		// Put the request method and URI in route form. Routes begin with
		// the request method and a forward slash.
		$destination = $method.' /'.trim($uri, '/');

		// Check for a literal route match first. If we find one, there is
		// no need to spin through all of the routes.
		if (isset($routes[$destination]))
		{
			return Request::$route = new Route($destination, $routes[$destination], array());
		}

		foreach ($routes as $keys => $callback)
		{
			// Only check routes that have multiple URIs or wildcards.
			// Other routes would have been caught by the check for literal matches.
			if (strpos($keys, '(') !== false or strpos($keys, ',') !== false )
			{
				foreach (explode(', ', $keys) as $key)
				{
					// Append the provided formats to the route as an optional regular expression.
					if ( ! is_null($formats = $this->provides($callback))) $key .= '(\.('.implode('|', $formats).'))?';

					if (preg_match('#^'.$this->translate_wildcards($key).'$#', $destination))
					{
						return Request::$route = new Route($keys, $callback, $this->parameters($destination, $key));
					}
				}				
			}
		}

		return Request::$route = $this->route_to_controller($method, $uri, $destination);
	}

	/**
	 * Attempt to find a controller for the incoming request.
	 *
	 * @param  string  $method
	 * @param  string  $uri
	 * @param  string  $destination
	 * @return Route
	 */
	protected function route_to_controller($method, $uri, $destination)
	{
		// If the request is to the root of the application, an ad-hoc route will be generated
		// to the home controller's "index" method, making it the default controller method.
		if ($uri === '/') return new Route($method.' /', 'home@index');

		$segments = explode('/', trim($uri, '/'));

		if ( ! is_null($key = $this->controller_key($segments)))
		{
			// Create the controller name for the current request. This controller
			// name will be returned by the anonymous route we will create. Instead
			// of using directory slashes, dots will be used to specify the controller
			// location with the controllers directory.
			$controller = implode('.', array_slice($segments, 0, $key));

			// Now that we have the controller path and name, we can slice the controller
			// section of the URI from the array of segments.
			$segments = array_slice($segments, $key);

			// Extract the controller method from the URI segments. If no more segments
			// are remaining after slicing off the controller, the "index" method will
			// be used as the default controller method.
			$method = (count($segments) > 0) ? array_shift($segments) : 'index';

			return new Route($destination, $controller.'@'.$method, $segments);
		}
	}

	/**
	 * Search the controllers for the application and determine if an applicable
	 * controller exists for the current request.
	 *
	 * If a controller is found, the array key for the controller name in the URI
	 * segments will be returned by the method, otherwise NULL will be returned.
	 * The deepest possible matching controller will be considered the controller
	 * that should handle the request.
	 *
	 * @param  array  $segments
	 * @return int
	 */
	protected function controller_key($segments)
	{
		foreach (array_reverse($segments, true) as $key => $value)
		{
			if (file_exists($path = $this->controllers.implode('/', array_slice($segments, 0, $key + 1)).EXT))
			{
				return $key + 1;
			}
		}
	}

	/**
	 * Get the request formats for which the route provides responses.
	 *
	 * @param  mixed  $callback
	 * @return array
	 */
	protected function provides($callback)
	{
		if (is_array($callback) and isset($callback['provides']))
		{
			return (is_string($provides = $callback['provides'])) ? explode('|', $provides) : $provides;
		}
	}

	/**
	 * Translate route URI wildcards into actual regular expressions.
	 *
	 * @param  string  $key
	 * @return string
	 */
	protected function translate_wildcards($key)
	{
		$replacements = 0;

		// For optional parameters, first translate the wildcards to their
		// regex equivalent, sans the ")?" ending. We will add the endings
		// back on after we know how many replacements we made.
		$key = str_replace(array('/(:num?)', '/(:any?)'), array('(?:/([0-9]+)', '(?:/([a-zA-Z0-9\.\-_]+)'), $key, $replacements);

		$key .= ($replacements > 0) ? str_repeat(')?', $replacements) : '';

		return str_replace(array(':num', ':any'), array('[0-9]+', '[a-zA-Z0-9\.\-_]+'), $key);
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
	protected function parameters($uri, $route)
	{
		return array_values(array_intersect_key(explode('/', $uri), preg_grep('/\(.+\)/', explode('/', $route))));	
	}	

}