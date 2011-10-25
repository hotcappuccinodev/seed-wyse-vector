<?php namespace Laravel;

class URI {

	/**
	 * The request URI for the current request.
	 *
	 * @var string
	 */
	protected $uri;

	/**
	 * The $_SERVER global array for the current request.
	 *
	 * @var array
	 */
	protected $server;

	/**
	 * Create a new instance of the URI class.
	 *
	 * @param  array  $server
	 * @return void
	 */
	public function __construct($server)
	{
		$this->server = $server;
	}

	/**
	 * Get the request URI for the current request.
	 *
	 * If the request is to the root of the application, a single forward slash
	 * will be returned. Otherwise, the URI will be returned with all leading
	 * and trailing slashes removed.
	 *
	 * @return string
	 */
	public function get()
	{
		if ( ! is_null($this->uri)) return $this->uri;

		$uri = parse_url($this->server['REQUEST_URI'], PHP_URL_PATH);

		return $this->uri = $this->format($this->clean($uri));
	}

	/**
	 * Remove extraneous information from the given request URI.
	 *
	 * The application URL will be removed, as well as the application index file
	 * and the request format. None of these things are used when routing the
	 * request to a closure or controller, so they can be safely removed.
	 *
	 * @param  string  $uri
	 * @return string
	 */
	protected function clean($uri)
	{
		$uri = $this->remove($uri, parse_url(Config::$items['application']['url'], PHP_URL_PATH));

		if (($index = '/'.Config::$items['application']['index']) !== '/')
		{
			$uri = $this->remove($uri, $index);
		}

		return rtrim($uri, '.'.Request::format($uri));
	}

	/**
	 * Remove a string from the beginning of a URI.
	 *
	 * @param  string   $uri
	 * @param  string   $remove
	 * @return string
	 */
	protected function remove($uri, $remove)
	{
		return (strpos($uri, $remove) === 0) ? substr($uri, strlen($remove)) : $uri;
	}

	/**
	 * Format the URI for use throughout the framework.
	 *
	 * @param  string  $uri
	 * @return string
	 */
	protected function format($uri)
	{
		return (($uri = trim($uri, '/')) !== '') ? $uri : '/';
	}

}