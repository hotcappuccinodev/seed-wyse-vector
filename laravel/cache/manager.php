<?php namespace Laravel\Cache;

use Laravel\Container;

class Manager {

	/**
	 * All of the active cache drivers.
	 *
	 * @var Cache\Driver
	 */
	public $drivers = array();

	/**
	 * The application IoC container.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * The default cache driver.
	 *
	 * @var string
	 */
	private $default;

	/**
	 * Create a new cache manager instance.
	 *
	 * @param  Container  $container
	 * @return void
	 */
	public function __construct(Container $container, $default)
	{
		$this->default = $default;
		$this->container = $container;
	}

	/**
	 * Get a cache driver instance.
	 *
	 * If no driver name is specified, the default cache driver will be returned
	 * as defined in the cache configuration file.
	 *
	 * @param  string        $driver
	 * @return Cache\Driver
	 */
	public function driver($driver = null)
	{
		if (is_null($driver)) $driver = $this->default;

		if ( ! array_key_exists($driver, $this->drivers))
		{
			if ( ! $this->container->registered('laravel.cache.'.$driver))
			{
				throw new \Exception("Cache driver [$driver] is not supported.");
			}

			return $this->drivers[$driver] = $this->container->resolve('laravel.cache.'.$driver);
		}

		return $this->drivers[$driver];
	}

	/**
	 * Pass all other methods to the default cache driver.
	 *
	 * Passing method calls to the driver instance provides a convenient API for the developer
	 * when always using the default cache driver.
	 */
	public function __call($method, $parameters)
	{
		return call_user_func_array(array($this->driver(), $method), $parameters);
	}

}