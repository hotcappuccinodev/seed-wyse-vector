<?php namespace Laravel\Routing;

use Closure;
use Laravel\Arr;

class Route {

	/**
	 * The route key, including request method and URI.
	 *
	 * @var string
	 */
	public $key;

	/**
	 * The URIs the route responds to.
	 *
	 * @var array
	 */
	public $uris;

	/**
	 * The route callback or array.
	 *
	 * @var mixed
	 */
	public $callback;

	/**
	 * The parameters that will passed to the route callback.
	 *
	 * @var array
	 */
	public $parameters;

	/**
	 * Create a new Route instance.
	 *
	 * @param  string   $key
	 * @param  mixed    $callback
	 * @param  array    $parameters
	 * @return void
	 */
	public function __construct($key, $callback, $parameters = array())
	{
		$this->key = $key;
		$this->callback = $callback;
		$this->parameters = $parameters;

		// Extract each URI out of the route key. Since the route key has the request
		// method, we will extract the method off of the string. If the URI points to
		// the root of the application, a single forward slash will be returned.
		// Otherwise, the leading slash will be removed.
		foreach (explode(', ', $key) as $segment)
		{
			$this->uris[] = ($segment = (substr($segment, strpos($segment, ' ') + 1)) !== '/') ? trim($segment, '/') : $segment;
		}

		// The route callback must be either a Closure, an array, or a string. Closures
		// obviously handle the requests to the route. An array can contain filters, as
		// well as a Closure to handle requests to the route. A string, delegates control
		// of the request to a controller method.
		if ( ! $this->callback instanceof \Closure and ! is_array($this->callback) and ! is_string($this->callback))
		{
			throw new \Exception('Invalid route defined for URI ['.$this->key.']');
		}
	}

	/**
	 * Call the route closure.
	 *
	 * @return mixed
	 */
	public function call()
	{
		// If the value defined for a route is a Closure, we simply call the closure with the
		// route's parameters and return the response.
		if ($this->callback instanceof Closure)
		{
			return call_user_func_array($this->callback, $this->parameters);
		}

		// Otherwise, we will assume the route is an array and will return the first value with
		// a key of "do", or the first instance of a Closure. If the value is a string, the route
		// is delegating the responsibility for handling the request to a controller.
		elseif (is_array($this->callback))
		{
			return Arr::first($this->callback, function($key, $value) {return $key == 'do' or $value instanceof Closure;});
		}

		// If a value defined for a route is a string, it means the route is delegating control
		// of the request to a controller. If that is the case, we will simply return the string
		// for the route caller to parse and delegate.
		elseif (is_string($this->callback))
		{
			return $this->callback;
		}
	}

	/**
	 * Get an array of filter names defined for the route.
	 *
	 * @param  string  $name
	 * @return array
	 */
	public function filters($name)
	{
		return (is_array($this->callback) and isset($this->callback[$name])) ? explode(', ', $this->callback[$name]) : array();
	}

	/**
	 * Determine if the route has a given name.
	 *
	 * @param  string  $name
	 * @return bool
	 */
	public function is($name)
	{
		return (is_array($this->callback) and isset($this->callback['name'])) ? $this->callback['name'] === $name : false;
	}

	/**
	 * Determine if the route handles a given URI.
	 *
	 * @param  string  $uri
	 * @return bool
	 */
	public function handles($uri)
	{
		return in_array($uri, $this->uris);
	}

	/**
	 * Magic Method to handle dynamic method calls to determine the name of the route.
	 */
	public function __call($method, $parameters)
	{
		if (strpos($method, 'is_') === 0) { return $this->is(substr($method, 3)); }
	}

}