<?php namespace Laravel;

class Cache {

	/**
	 * All of the active cache drivers.
	 *
	 * @var Cache\Driver
	 */
	public static $drivers = array();

	/**
	 * Get a cache driver instance.
	 *
	 * If no driver name is specified, the default cache driver will be returned
	 * as defined in the cache configuration file.
	 *
	 * <code>
	 *		// Get the default cache driver
	 *		$driver = Cache::driver();
	 *
	 *		// Get the APC cache driver
	 *		$apc = Cache::driver('apc');
	 * </code>
	 *
	 * @param  string        $driver
	 * @return Cache\Driver
	 */
	public static function driver($driver = null)
	{
		if (is_null($driver)) $driver = Config::get('cache.driver');

		if ( ! array_key_exists($driver, static::$drivers))
		{
			switch ($driver)
			{
				case 'file':
					return static::$drivers[$driver] = new Cache\File;

				case 'memcached':
					return static::$drivers[$driver] = new Cache\Memcached;

				case 'apc':
					return static::$drivers[$driver] = new Cache\APC;

				default:
					throw new \Exception("Cache driver [$driver] is not supported.");
			}
		}

		return static::$drivers[$driver];
	}

	/**
	 * Pass all other methods to the default cache driver.
	 *
	 * Passing method calls to the driver instance provides a convenient API for the developer
	 * when always using the default cache driver.
	 *
	 * <code>
	 *		// Get an item from the default cache driver
	 *		$name = Cache::get('name');
	 * </code>
	 */
	public static function __callStatic($method, $parameters)
	{
		return call_user_func_array(array(static::driver(), $method), $parameters);
	}

}