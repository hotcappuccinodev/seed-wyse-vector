<?php namespace Laravel\Session;

use Closure;
use Laravel\Str;
use Laravel\Config;
use Laravel\Cookie;
use Laravel\Session\Drivers\Driver;
use Laravel\Session\Drivers\Sweeper;

if (Config::$items['application']['key'] === '')
{
	throw new \Exception("An application key is required to use sessions.");
}

class Manager {

	/**
	 * The session array that is stored by the driver.
	 *
	 * @var array
	 */
	public $session;

	/**
	 * Indicates if the session already exists in storage.
	 *
	 * @var bool
	 */
	protected $exists = true;

	/**
	 * Start the session handling for the current request.
	 *
	 * @param  Driver  $driver
	 * @param  string  $id
	 * @return void
	 */
	public function __construct(Driver $driver, $id)
	{
		if ( ! is_null($id))
		{
			$this->session = $driver->load($id);
		}

		if (is_null($this->session) or $this->invalid())
		{
			$this->exists = false;

			$this->session = array('id' => Str::random(40), 'data' => array());
		}

		if ( ! $this->has('csrf_token'))
		{
			// A CSRF token is stored in every session. The token is used by the
			// Form class and the "csrf" filter to protect the application from
			// cross-site request forgery attacks. The token is simply a long,
			// random string which should be posted with each request.
			$this->put('csrf_token', Str::random(40));
		}
	}

	/**
	 * Deteremine if the session payload instance is valid.
	 *
	 * The session is considered valid if it exists and has not expired.
	 *
	 * @return bool
	 */
	protected function invalid()
	{
		$lifetime = Config::$items['session']['lifetime'];

		return (time() - $this->session['last_activity']) > ($lifetime * 60);
	}

	/**
	 * Determine if session handling has been started for the request.
	 *
	 * @return bool
	 */
	public function started()
	{
		return is_array($this->session);
	}

	/**
	 * Determine if the session or flash data contains an item.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function has($key)
	{
		return ( ! is_null($this->get($key)));
	}

	/**
	 * Get an item from the session.
	 *
	 * The session flash data will also be checked for the requested item.
	 *
	 * <code>
	 *		// Get an item from the session
	 *		$name = Session::get('name');
	 *
	 *		// Return a default value if the item doesn't exist
	 *		$name = Session::get('name', 'Taylor');
	 * </code>
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		foreach (array($key, ':old:'.$key, ':new:'.$key) as $possibility)
		{
			if (array_key_exists($possibility, $this->session['data']))
			{
				return $this->session['data'][$possibility];
			}
		}

		return ($default instanceof Closure) ? call_user_func($default) : $default;
	}

	/**
	 * Write an item to the session.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function put($key, $value)
	{
		$this->session['data'][$key] = $value;
	}

	/**
	 * Write an item to the session flash data.
	 *
	 * Flash data only exists for the next request to the application.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function flash($key, $value)
	{
		$this->put(':new:'.$key, $value);
	}

	/**
	 * Keep all of the session flash data from expiring at the end of the request.
	 *
	 * @return void
	 */
	public function reflash()
	{
		$flash = array();

		foreach ($this->session['data'] as $key => $value)
		{
			if (strpos($key, ':old:') === 0)
			{
				$flash[] = str_replace(':old:', '', $key);
			}
		}

		$this->keep($flash);
	}

	/**
	 * Keep a session flash item from expiring at the end of the request.
	 *
	 * @param  string|array  $key
	 * @return void
	 */
	public function keep($keys)
	{
		foreach ((array) $keys as $key)
		{
			$this->flash($key, $this->get($key));
		}
	}

	/**
	 * Remove an item from the session data.
	 *
	 * @param  string  $key
	 * @return Driver
	 */
	public function forget($key)
	{
		unset($this->session['data'][$key]);
	}

	/**
	 * Remove all of the items from the session.
	 *
	 * @return void
	 */
	public function flush()
	{
		$this->session['data'] = array();
	}

	/**
	 * Assign a new, random ID to the session.
	 *
	 * @return void
	 */
	public function regenerate()
	{
		$this->session['id'] = Str::random(40);

		$this->exists = false;
	}

	/**
	 * Get the CSRF token that is stored in the session data.
	 *
	 * @return string
	 */
	public function token()
	{
		return $this->get('csrf_token');
	}

	/**
	 * Store the session payload in storage.
	 *
	 * @param  Driver  $driver
	 * @return void
	 */
	public function save(Driver $driver)
	{
		$this->session['last_activity'] = time();

		$this->age();

		$config = Config::$items['session'];

		$driver->save($this->session, $config, $this->exists);

		$this->cookie();

		// Some session drivers implement the Sweeper interface, meaning that they
		// must clean up expired sessions manually. If the driver is a sweeper, we
		// need to determine if garbage collection should be run for the request.
		// Since garbage collection can be expensive, the probability of it
		// occuring is controlled by the "sweepage" configuration option.
		if ($driver instanceof Sweeper and (mt_rand(1, $config['sweepage'][1]) <= $config['sweepage'][0]))
		{
			$driver->sweep(time() - ($config['lifetime'] * 60));
		}
	}

	/**
	 * Age the session flash data.
	 *
	 * Session flash data is only available during the request in which it
	 * was flashed, and the request after that. To "age" the data, we will
	 * remove all of the :old: items and re-address the new items.
	 *
	 * @return void
	 */
	protected function age()
	{
		foreach ($this->session['data'] as $key => $value)
		{
			if (strpos($key, ':old:') === 0)
			{
				$this->forget($key);
			}
		}

		// Now that all of the "old" keys have been removed from the session data,
		// we can re-address all of the newly flashed keys to have old addresses.
		// The array_combine method uses the first array for keys, and the second
		// array for values to construct a single array from both.
		$keys = str_replace(':new:', ':old:', array_keys($this->session['data']));

		$this->session['data'] = array_combine($keys, array_values($this->session['data']));
	}

	/**
	 * Send the session ID cookie to the browser.
	 *
	 * @return void
	 */
	protected function cookie()
	{
		$config = Config::$items['session'];

		extract($config, EXTR_SKIP);

		$minutes = ( ! $expire_on_close) ? $lifetime : 0;

		Cookie::put($cookie, $this->session['id'], $minutes, $path, $domain, $secure);	
	}

}