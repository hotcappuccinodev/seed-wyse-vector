<?php

class Base_Controller extends Controller {

	public function __construct()
	{
		parent::__construct();

		$class = get_called_class();
		switch($class)
		{
			case 'Home_Controller':
				$this->filter('before', 'nonauth');
				break;

			case 'User_Controller':
				$this->filter('before', 'nonauth')->only(array('authenticate'));
				$this->filter('before', 'auth')->only(array('logout'));
				break;

			default:
				$this->filter('before', 'auth');
				break;

		}
	}

	/**
	 * Catch-all method for requests that can't be matched.
	 *
	 * @param  string    $method
	 * @param  array     $parameters
	 * @return Response
	 */
	public function __call($method, $parameters)
	{
		return Response::error('404');
	}

}