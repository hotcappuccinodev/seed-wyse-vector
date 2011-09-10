<?php namespace Laravel;

abstract class Controller {

	/**
	 * A stub method that will be called before every request to the controller.
	 *
	 * If a value is returned by the method, it will be halt the request cycle
	 * and will be considered the response to the request.
	 *
	 * @return mixed
	 */
	public function before() {}

	/**
	 * Magic Method to handle calls to undefined functions on the controller.
	 *
	 * By default, the 404 response will be returned for an calls to undefined
	 * methods on the controller. However, this method may also be overridden
	 * and used as a pseudo-router by the controller.
	 */
	public function __call($method, $parameters)
	{
		return IoC::container()->resolve('laravel.response')->error('404');
	}

	/**
	 * Dynamically resolve items from the application IoC container.
	 *
	 * First, "laravel." will be prefixed to the requested item to see if there is
	 * a matching Laravel core class in the IoC container. If there is not, we will
	 * check for the item in the container using the name as-is.
	 *
	 * <code>
	 *		// Resolve the "laravel.input" instance from the IoC container
	 *		$input = $this->input;
	 *
	 *		// Resolve the "mailer" instance from the IoC container
	 *		$mongo = $this->mailer;
	 * </code>
	 *
	 */
	public function __get($key)
	{
		$container = IoC::container();

		if ($container->registered('laravel.'.$key))
		{
			return $container->resolve('laravel.'.$key);
		}
		elseif ($container->registered($key))
		{
			return $container->resolve($key);
		}

		throw new \Exception("Attempting to access undefined property [$key] on controller.");
	}

}