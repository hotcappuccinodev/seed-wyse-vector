<?php namespace Laravel\Cache;

use Laravel\Config;

/**
 * Wrap the APC functions in a class that can be injected into driver.
 * Since the APC functions are global, the driver is untestable without
 * injecting a wrapper around them.
 */
class APC_Engine {

	/**
	 * Get an item from the APC cache.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function get($key)
	{
		return apc_fetch($key);
	}

	/**
	 * Store an item in the APC cache.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @param  int     $minutes
	 * @return void
	 */
	public function put($key, $value, $seconds)
	{
		apc_store($key, $value, $seconds);
	}

	/**
	 * Delete an item from the APC cache.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function forget($key)
	{
		apc_delete($key);
	}

}

class APC extends Driver {

	/**
	 * The APC Engine instance.
	 *
	 * @var APC_Engine
	 */
	private $apc;

	/**
	 * Create a new APC cache driver instance.
	 *
	 * @param  APC_Engine  $apc
	 * @return void
	 */
	public function __construct(APC_Engine $apc)
	{
		$this->apc = $apc;
	}

	/**
	 * Determine if an item exists in the cache.
	 *
	 * <code>
	 *		// Determine if the "name" item exists in the cache
	 *		$exists = Cache::driver()->has('name');
	 * </code>
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function has($key)
	{
		return ( ! is_null($this->get($key)));
	}

	/**
	 * Retrieve an item from the cache driver.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	protected function retrieve($key)
	{
		return ( ! is_null($cache = $this->apc->get(Config::get('cache.key').$key))) ? $cache : null;
	}

	/**
	 * Write an item to the cache for a given number of minutes.
	 *
	 * <code>
	 *		// Write the "name" item to the cache for 30 minutes
	 *		Cache::driver()->put('name', 'Fred', 30);
	 * </code>
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @param  int     $minutes
	 * @return void
	 */
	public function put($key, $value, $minutes)
	{
		$this->apc->put(Config::get('cache.key').$key, $value, $minutes * 60);
	}

	/**
	 * Delete an item from the cache.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function forget($key)
	{
		$this->apc->forget(Config::get('cache.key').$key);
	}

}