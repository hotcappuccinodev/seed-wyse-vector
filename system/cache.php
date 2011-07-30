<?php namespace System;

class Cache {

	/**
	 * The active cache drivers.
	 *
	 * @var Cache\Driver
	 */
	public static $drivers = array();

	/**
	 * All of the items retrieved by the cache drivers.
	 *
	 * @var array
	 */
	public static $items = array();

	/**
	 * Get a cache driver instance. If no driver name is specified, the default
	 * cache driver will be returned as defined in the cache configuration file.
	 *
	 * Note: Cache drivers are managed as singleton instances.
	 *
	 * @param  string  $driver
	 * @return Cache\Driver
	 */
	public static function driver($driver = null)
	{
		if (is_null($driver))
		{
			$driver = Config::get('cache.driver');
		}

		if ( ! array_key_exists($driver, static::$drivers))
		{
			switch ($driver)
			{
				case 'file':
					static::$drivers[$driver] = new Cache\File;
					break;

				case 'memcached':
					static::$drivers[$driver] = new Cache\Memcached;
					break;

				case 'apc':
					static::$drivers[$driver] = new Cache\APC;
					break;

				default:
					throw new \Exception("Cache driver [$driver] is not supported.");
			}
		}

		return static::$drivers[$driver];
	}

	/**
	 * Get an item from the cache.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @param  string  $driver
	 * @return mixed
	 */	
	public static function get($key, $default = null, $driver = null)
	{
		if (isset(static::$items[$driver][$key]))
		{
			return static::$items[$driver][$key];
		}

		if (is_null($item = static::driver($driver)->get($key)))
		{
			return is_callable($default) ? call_user_func($default) : $default;
		}

		return static::$items[$driver][$key] = $item;
	}

	/**
	 * Get an item from the cache. If the item doesn't exist in the cache, store
	 * the default value in the cache and return it.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @param  int     $minutes
	 * @param  string  $driver
	 * @return mixed
	 */
	public static function remember($key, $default, $minutes, $driver = null)
	{
		if ( ! is_null($item = static::get($key)))
		{
			return $item;
		}

		$default = is_callable($default) ? call_user_func($default) : $default;

		static::driver($driver)->put($key, $default, $minutes);

		return $default;
	}

	/**
	 * Pass all other methods to the default driver.
	 *
	 * Passing method calls to the driver instance provides a better API for the
	 * developer. For instance, instead of saying Cache::driver()->foo(), we can
	 * now just say Cache::foo().
	 */
	public static function __callStatic($method, $parameters)
	{
		return call_user_func_array(array(static::driver(), $method), $parameters);
	}

}