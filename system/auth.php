<?php namespace System;

class Auth {

	/**
	 * The current user of the application.
	 *
	 * @var object
	 */
	public static $user;

	/**
	 * The key used to store the user ID in the session.
	 *
	 * @var string
	 */
	private static $key = 'laravel_user_id';

	/**
	 * Determine if the current user of the application is authenticated.
	 *
	 * @return bool
	 */
	public static function check()
	{
		return ( ! is_null(static::user()));
	}

	/**
	 * Get the current user of the application.
	 *
	 * The user will be loaded using the user ID stored in the session.
	 *
	 * @return object
	 */
	public static function user()
	{
		if (Config::get('session.driver') == '')
		{
			throw new \Exception("You must specify a session driver before using the Auth class.");
		}

		$model = static::model();

		if (is_null(static::$user) and Session::has(static::$key))
		{
			static::$user = $model::find(Session::get(static::$key));
		}

		return static::$user;
	}

	/**
	 * Attempt to login a user.
	 *
	 * If the user credentials are valid. The user ID will be stored in the session
	 * and will be considered "logged in" on subsequent requests to the application.
	 *
	 * @param  string  $username
	 * @param  string  $password
	 */
	public static function login($username, $password)
	{
		$model = static::model();

		$user = $model::where(Config::get('auth.username'), '=', $username)->first();

		if ( ! is_null($user))
		{
			if ($user->password === Hash::make($password, $user->salt)->value)
			{
				static::$user = $user;

				Session::put(static::$key, $user->id);

				return true;
			}
		}

		return false;
	}

	/**
	 * Logout the user of the application.
	 *
	 * @return void
	 */
	public static function logout()
	{
		Session::forget(static::$key);

		static::$user = null;
	}

	/**
	 * Get the authentication model.
	 *
	 * @return string
	 */
	private static function model()
	{
		return '\\'.Config::get('auth.model');
	}

}