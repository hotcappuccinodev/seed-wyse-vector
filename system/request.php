<?php namespace System;

class Request {

	/**
	 * Get the request URI.
	 *
	 * @return string
	 */
	public static function uri()
	{
		// -------------------------------------------------------
		// If the PATH_INFO is available, use it.
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
		// -------------------------------------------------------
		// Neither PATH_INFO or REQUEST_URI are available.
		// -------------------------------------------------------
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
	 * Check the request URI.
	 *
	 * @param  mixed $uri
	 * @return bool
	 */
	public static function is($uri)
	{
		if (is_array($uri))
		{
			return (in_array(static::uri(), $uri)) ? true : false;
		}
		else
		{
			return (static::uri() == $uri) ? true : false;
		}
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