<?php namespace Laravel;

class IoC {

	/**
	 * The active container instance.
	 *
	 * @var Container
	 */
	public static $container;

	/**
	 * Bootstrap the global IoC instance.
	 *
	 * @return void
	 */
	public static function bootstrap()
	{
		Config::load('container');

		static::$container = new Container(Config::$items['container']);
	}

	/**
	 * Get the active container instance.
	 *
	 * @return Container
	 */
	public static function container()
	{
		return static::$container;
	}

	/**
	 * Resolve a core Laravel class from the container.
	 *
	 * <code>
	 *		// Resolve the "laravel.router" class from the container
	 *		$input = IoC::core('router');
	 *
	 *		// Equivalent resolution using the "resolve" method
	 *		$input = IoC::resolve('laravel.router');
	 *
	 *		// Pass an array of parameters to the resolver
	 *		$input = IoC::core('router', array('test'));
	 * </code>
	 *
	 * @param  string  $name
	 * @param  array   $parameters
	 * @return mixed
	 */
	public static function core($name, $parameters = array())
	{
		return static::$container->core($name, $parameters);
	}

	/**
	 * Magic Method for calling methods on the active container instance.
	 *
	 * <code>
	 *		// Call the "resolve" method on the active container
	 *		$instance = IoC::resolve('laravel.routing.router');
	 *
	 *		// Call the "instance" method on the active container
	 *		IoC::instance('mailer', new Mailer);
	 * </code>
	 */
	public static function __callStatic($method, $parameters)
	{
		return call_user_func_array(array(static::$container, $method), $parameters);
	}

}

class Container {

	/**
	 * The resolved singleton instances.
	 *
	 * @var array
	 */
	public $singletons = array();

	/**
	 * The registered dependencies.
	 *
	 * @var array
	 */
	protected $registry = array();

	/**
	 * Create a new IoC container instance.
	 *
	 * @param  array  $registry
	 * @return void
	 */
	public function __construct($registry = array())
	{
		$this->registry = $registry;
	}

	/**
	 * Register an object and its resolver.
	 *
	 * The IoC container instance is always passed to the resolver, allowing the
	 * nested resolution of other objects from the container.
	 *
	 * <code>
	 *		// Register an object and its resolver
	 *		IoC::container()->register('mailer', function($c) {return new Mailer;});
	 * </code>
	 *
	 * @param  string   $name
	 * @param  Closure  $resolver
	 * @return void
	 */
	public function register($name, $resolver, $singleton = false)
	{
		$this->registry[$name] = array('resolver' => $resolver, 'singleton' => $singleton);
	}

	/**
	 * Determine if an object has been registered in the container.
	 *
	 * @param  string  $name
	 * @return bool
	 */
	public function registered($name)
	{
		return array_key_exists($name, $this->registry);
	}

	/**
	 * Register an object as a singleton.
	 *
	 * Singletons will only be instantiated the first time they are resolved.
	 * On subsequent requests for the object, the original instance will be returned.
	 *
	 * @param  string   $name
	 * @param  Closure  $resolver
	 * @return void
	 */
	public function singleton($name, $resolver)
	{
		$this->register($name, $resolver, true);
	}

	/**
	 * Register an instance as a singleton.
	 *
	 * This method allows you to register an already existing object instance
	 * with the container to be managed as a singleton instance.
	 *
	 * <code>
	 *		// Register an instance as a singleton in the container
	 *		IoC::container()->instance('mailer', new Mailer);
	 * </code>
	 *
	 * @param  string  $name
	 * @param  mixed   $instance
	 * @return void
	 */
	public function instance($name, $instance)
	{
		$this->singletons[$name] = $instance;
	}

	/**
	 * Resolve a core Laravel class from the container.
	 *
	 * <code>
	 *		// Resolve the "laravel.router" class from the container
	 *		$input = IoC::container()->core('router');
	 *
	 *		// Equivalent resolution using the "resolve" method
	 *		$input = IoC::container()->resolve('laravel.router');
	 *
	 *		// Pass an array of parameters to the resolver
	 *		$input = IoC::container()->core('router', array('test'));
	 * </code>
	 *
	 * @param  string  $name
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function core($name, $parameters = array())
	{
		return $this->resolve("laravel.{$name}", $parameters);
	}

	/**
	 * Resolve an object instance from the container.
	 *
	 * <code>
	 *		// Get an instance of the "mailer" object registered in the container
	 *		$mailer = IoC::container()->resolve('mailer');
	 *
	 *		// Pass an array of parameters to the resolver
	 *		$mailer = IoC::container()->resolve('mailer', array('test'));
	 * </code>
	 *
	 * @param  string  $name
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function resolve($name, $parameters = array())
	{
		if (array_key_exists($name, $this->singletons)) return $this->singletons[$name];

		if ( ! $this->registered($name))
		{
			throw new \Exception("Error resolving [$name]. No resolver has been registered in the container.");
		}

		$object = call_user_func($this->registry[$name]['resolver'], $this, $parameters);

		if (isset($this->registry[$name]['singleton']) and $this->registry[$name]['singleton'])
		{
			return $this->singletons[$name] = $object;
		}

		return $object;
	}

}