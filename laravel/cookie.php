<?php namespace Laravel;

class Cookie {

	/**
	 * The cookies for the current request.
	 *
	 * @var array
	 */
	protected $cookies;

	/**
	 * Create a new cookie manager instance.
	 *
	 * @param  array  $cookies
	 * @return void
	 */
	public function __construct(&$cookies)
	{
		$this->cookies =& $cookies;
	}

	/**
	 * Determine if a cookie exists.
	 *
	 * @param  string  $name
	 * @return bool
	 */
	public function has($name)
	{
		return ! is_null($this->get($name));
	}

	/**
	 * Get the value of a cookie.
	 *
	 * @param  string  $name
	 * @param  mixed   $default
	 * @return string
	 */
	public function get($name, $default = null)
	{
		return Arr::get($_COOKIE, $name, $default);
	}

	/**
	 * Set a "permanent" cookie. The cookie will last 5 years.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  string  $path
	 * @param  string  $domain
	 * @param  bool    $secure
	 * @param  bool    $http_only
	 * @return bool
	 */
	public function forever($name, $value, $path = '/', $domain = null, $secure = false, $http_only = false)
	{
		return $this->put($name, $value, 2628000, $path, $domain, $secure, $http_only);
	}

	/**
	 * Set the value of a cookie. 
	 *
	 * If a negative number of minutes is specified, the cookie will be deleted.
	 *
	 * Note: This method's signature is very similar to the PHP setcookie method.
	 *       However, you simply need to pass the number of minutes for which you
	 *       wish the cookie to be valid. No funky time calculation is required.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  int     $minutes
	 * @param  string  $path
	 * @param  string  $domain
	 * @param  bool    $secure
	 * @param  bool    $http_only
	 * @return bool
	 */
	public function put($name, $value, $minutes = 0, $path = '/', $domain = null, $secure = false, $http_only = false)
	{
		if (headers_sent()) return false;

		if ($minutes < 0) unset($_COOKIE[$name]);

		// Since PHP needs the cookie lifetime in seconds, we will calculate it here.
		// A "0" lifetime means the cookie expires when the browser closes.
		$time = ($minutes !== 0) ? time() + ($minutes * 60) : 0;

		return setcookie($name, $value, $time, $path, $domain, $secure, $http_only);
	}

	/**
	 * Delete a cookie.
	 *
	 * @param  string  $name
	 * @return bool
	 */
	public function forget($name)
	{
		return $this->put($name, null, -60);
	}

}