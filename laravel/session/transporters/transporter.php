<?php namespace Laravel\Session\Transporters;

interface Transporter {

	/**
	 * Get the session identifier for the request.
	 *
	 * @return string
	 */
	public function get();

	/**
	 * Store the session identifier for the request.
	 *
	 * @param  string  $id
	 * @param  array   $config
	 * @return void
	 */
	public function put($id, $config);

}