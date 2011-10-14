<?php namespace Laravel\Routing;

use Closure;
use Laravel\Arr;
use Laravel\Response;

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

		// Extract each URI from the route key. Since the route key has the
		// request method, we will extract that from the string. If the URI
		// points to the root of the application, a single forward slash
		// will be returned.
		if (strpos($key, ', ') === false)
		{
			$this->uris = array($this->extract($this->key));
		}
		else
		{
			$this->uris = array_map(array($this, 'extract'), explode(', ', $key));
		}

		if ( ! $callback instanceof Closure and ! is_array($callback) and ! is_string($callback))
		{
			throw new \Exception('Invalid route defined for URI ['.$this->key.']');
		}
	}

	/**
	 * Retrieve the URI from a given route destination.
	 *
	 * If the request is to the root of the application, a single slash
	 * will be returned, otherwise the leading slash will be removed.
	 *
	 * @param  string  $segment
	 * @return string
	 */
	protected function extract($segment)
	{
		$segment = substr($segment, strpos($segment, ' ') + 1);

		return ($segment !== '/') ? trim($segment, '/') : $segment;
	}

	/**
	 * Call a given route and return the route's response.
	 *
	 * @return Response
	 */
	public function call()
	{
		// Since "before" filters can halt the request cycle, we will return
		// any response from the before filters. Allowing filters to halt the
		// request cycle makes tasks like authorization convenient.
		$before = array_merge(array('before'), $this->filters('before'));

		if ( ! is_null($response = $this->filter($before, array(), true)))
		{
			return $response;
		}

		if ( ! is_null($response = $this->response()))
		{
			if ($response instanceof Delegate)
			{
				return $response;
			}

			$filters = array_merge($this->filters('after'), array('after'));

			Filter::run($filters, array($response));

			return $response;
		}
		else
		{
			return Response::error('404');
		}
	}

	/**
	 * Call the closure defined for the route, or get the route delegator.
	 *
	 * Note that this method differs from the "call" method in that it does
	 * not resolve the controller or prepare the response. Delegating to
	 * controller's is handled by the "call" method.
	 *
	 * @return mixed
	 */
	protected function response()
	{
		if ($this->callback instanceof Closure)
		{
			return call_user_func_array($this->callback, $this->parameters);
		}
		// If the route is an array we will return the first value with a
		// key of "delegate", or the first instance of a Closure. If the
		// value is a string, the route is delegating the responsibility
		// for handling the request to a controller.
		elseif (is_array($this->callback))
		{
			$callback = Arr::first($this->callback, function($key, $value)
			{
				return $key == 'delegate' or $value instanceof Closure;
			});

			if ($callback instanceof Closure)
			{
				return call_user_func_array($callback, $this->parameters);
			}
			else
			{
				return new Delegate($callback);
			}
		}
		elseif (is_string($this->callback))
		{
			return new Delegate($this->callback);
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
		if (is_array($this->callback) and isset($this->callback[$name]))
		{
			return (array) $this->callback[$name];
		}

		return array();
	}

	/**
	 * Determine if the route has a given name.
	 *
	 * @param  string  $name
	 * @return bool
	 */
	public function is($name)
	{
		return is_array($this->callback) and Arr::get($this->callback, 'name') === $name;
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
		if (strpos($method, 'is_') === 0) return $this->is(substr($method, 3));
	}

}