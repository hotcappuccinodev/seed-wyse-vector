<?php namespace System;

class Route {

	/**
	 * The route key, including request method and URI.
	 *
	 * @var string
	 */
	public $key;

	/**
	 * The route callback or array.
	 *
	 * @var mixed
	 */
	public $callback;

	/**
	 * The parameters that will passed to the route function.
	 *
	 * @var array
	 */
	public $parameters;

	/**
	 * Create a new Route instance.
	 *
	 * @param  string  $key
	 * @param  mixed   $callback
	 * @param  array   $parameters
	 * @return void
	 */
	public function __construct($key, $callback, $parameters = array())
	{
		$this->key = $key;
		$this->callback = $callback;
		$this->parameters = $parameters;
	}

	/**
	 * Execute the route function.
	 *
	 * @param  mixed     $route
	 * @param  array     $parameters
	 * @return mixed
	 */
	public function call()
	{
		$response = null;

		// ------------------------------------------------------------
		// If the route value is just a function, all we have to do
		// is execute the function! There are no filters to call.
		// ------------------------------------------------------------
		if (is_callable($this->callback))
		{
			$response = call_user_func_array($this->callback, $this->parameters);
		}
		// ------------------------------------------------------------
		// If the route value is an array, we'll need to check it for
		// any filters that may be attached.
		// ------------------------------------------------------------
		elseif (is_array($this->callback))
		{
			$response = isset($this->callback['before']) ? Filter::call($this->callback['before'], array(), true) : null;

			// ------------------------------------------------------------
			// We verify that the before filters did not return a response
			// Before filters can override the request cycle to make things
			// like authentication convenient to implement.
			// ------------------------------------------------------------
			if (is_null($response) and isset($this->callback['do']))
			{
				$response = call_user_func_array($this->callback['do'], $this->parameters);
			}
		}

		$response = Response::prepare($response);

		if (is_array($this->callback) and isset($this->callback['after']))
		{
			Filter::call($this->callback['after'], array($response));
		}

		return $response;
	}

}