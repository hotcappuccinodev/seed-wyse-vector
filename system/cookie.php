<?php namespace System;

class Cookie {

	/**
	 * The cookie name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * The cookie value.
	 *
	 * @var mixed
	 */
	public $value;

	/**
	 * The number of minutes the cookie should live.
	 *
	 * @var int
	 */
	public $lifetime = 0;

	/**
	 * The path for which the cookie is available.
	 *
	 * @var string
	 */
	public $path = '/';

	/**
	 * The domain for which the cookie is available.
	 *
	 * @var string
	 */
	public $domain = null;

	/**
	 * Indicates if the cookie should only be sent over HTTPS.
	 *
	 * @var bool
	 */
	public $secure = false;

	/**
	 * Create a new Cookie instance.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function __construct($name, $value = null)
	{
		$this->name = $name;
		$this->value = $value;
	}

	/**
	 * Create a new Cookie instance.
	 *
	 * @param  string  $name
	 * @return Cookie
	 */
	public static function make($name, $value = null)
	{
		return new static($name, $value);
	}

	/**
	 * Send the current cookie instance to the user's machine.
	 *
	 * @return bool
	 */
	public function send()
	{
		if (is_null($this->name))
		{
			throw new \Exception("Error sending cookie. The cookie does not have a name.");
		}

		return static::put($this->name, $this->value, $this->lifetime, $this->path, $this->domain, $this->secure);
	}

	/**
	 * Determine if a cookie exists.
	 *
	 * @param  string  $name
	 * @return bool
	 */
	public static function has($name)
	{
		foreach (func_get_args() as $key)
		{
			if (is_null(static::get($key)))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the value of a cookie.
	 *
	 * @param  string  $name
	 * @param  mixed   $default
	 * @return string
	 */
	public static function get($name, $default = null)
	{
		return (array_key_exists($name, $_COOKIE)) ? $_COOKIE[$name] : $default;
	}

	/**
	 * Set a "permanent" cookie. The cookie will last 5 years.
	 *
	 * @param  string   $name
	 * @param  string   $value
	 * @param  string   $path
	 * @param  string   $domain
	 * @param  bool     $secure
	 * @return bool
	 */
	public static function forever($name, $value, $path = '/', $domain = null, $secure = false)
	{
		return static::put($name, $value, 2628000, $path, $domain, $secure);
	}

	/**
	 * Set the value of a cookie.
	 *
	 * @param  string   $name
	 * @param  string   $value
	 * @param  int      $minutes
	 * @param  string   $path
	 * @param  string   $domain
	 * @param  bool     $secure
	 * @return bool
	 */
	public static function put($name, $value, $minutes = 0, $path = '/', $domain = null, $secure = false)
	{
		if ($minutes < 0)
		{
			unset($_COOKIE[$name]);
		}

		return setcookie($name, $value, ($minutes != 0) ? time() + ($minutes * 60) : 0, $path, $domain, $secure);
	}

	/**
	 * Delete a cookie.
	 *
	 * @param  string  $name
	 * @return bool
	 */
	public static function forget($name)
	{
		return static::put($key, null, -60);
	}

}