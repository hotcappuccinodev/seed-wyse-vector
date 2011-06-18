<?php namespace System;

class Request {

	/**
	 * The request URI.
	 *
	 * @var string
	 */
	private static $uri;

	/**
	 * Get the request URI.
	 *
	 * @return string
	 */
	public static function uri()
	{
		if ( ! is_null(static::$uri))
		{
			return static::$uri;
		}

		// -------------------------------------------------------
		// Use the PATH_INFO variable if it is available.
		// -------------------------------------------------------
		if (isset($_SERVER['PATH_INFO']))
		{
			$uri = $_SERVER['PATH_INFO'];
		}
		// -------------------------------------------------------
		// No PATH_INFO? Let's try REQUEST_URI.
		// -------------------------------------------------------
		elseif (isset($_SERVER['REQUEST_URI']))
		{
			$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

			if ($uri === false)
			{
				throw new \Exception("Malformed request URI. Request terminated.");
			}
		}
		else
		{
			throw new \Exception('Unable to determine the request URI.');
		}

		// -------------------------------------------------------
		// Remove the application URL.
		// -------------------------------------------------------
		$base_url = parse_url(Config::get('application.url'), PHP_URL_PATH);

		if (strpos($uri, $base_url) === 0)
		{
			$uri = (string) substr($uri, strlen($base_url));
		}

		// -------------------------------------------------------
		// Remove the application index and any extra slashes.
		// -------------------------------------------------------
		$uri = trim(str_replace('/index.php', '', $uri), '/');

		// -------------------------------------------------------
		// If the requests is to the root of the application, we
		// always return a single forward slash.
		// -------------------------------------------------------
		return ($uri == '') ? '/' : Str::lower($uri);
	}

	/**
	 * Check the URI against a string or set of strings.
	 *
	 * @return bool
	 */
	public static function is()
	{
		$parameters = func_get_args();

		// -------------------------------------------------------
		// If any of the parameters match the URI, return true.
		// -------------------------------------------------------
		if (count($parameters) > 1)
		{
			return in_array(static::uri(), $parameters);
		}

		if (count($parameters) === 1)
		{
			return static::uri() == $parameters[0];
		}

		return false;
	}

	/**
	 * Get the request method.
	 *
	 * @return string
	 */
	public static function method()
	{
		// --------------------------------------------------------------
		// The method can be spoofed using a POST variable, allowing HTML
		// forms to simulate PUT and DELETE requests.
		// --------------------------------------------------------------
		return Arr::get($_POST, 'REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
	}

	/**
	 * Get the requestor's IP address.
	 *
	 * @return string
	 */
	public static function ip()
	{
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
		{
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		elseif (isset($_SERVER['HTTP_CLIENT_IP']))
		{
			return $_SERVER['HTTP_CLIENT_IP'];
		}
		elseif (isset($_SERVER['REMOTE_ADDR']))
		{
			return $_SERVER['REMOTE_ADDR'];
		}
	}

	/**
	 * Determine if the request is using HTTPS.
	 *
	 * @return bool
	 */
	public static function is_secure()
	{
		return (static::protocol() == 'https');
	}

	/**
	 * Get the HTTP protocol for the request.
	 *
	 * @return string
	 */
	public static function protocol()
	{
		return (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
	}

	/**
	 * Determine if the request is an AJAX request.
	 *
	 * @return bool
	 */
	public static function is_ajax()
	{
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and Str::lower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
	}

}