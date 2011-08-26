<?php namespace Laravel;

class Package {

	/**
	 * All of the loaded packages.
	 *
	 * @var array
	 */
	public static $loaded = array();

	/**
	 * Load a package or set of packages.
	 *
	 * The package name should correspond to a package directory for your application.
	 *
	 * <code>
	 *		// Load the "swift-mailer" package
	 *		Package::load('swift-mailer');
	 *
	 *		// Load the "swift-mailer" and "facebook" package
	 *		Package::load(array('swift-mailer', 'facebook'));
	 * </code>
	 *
	 * @param  string|array  $packages
	 * @param  string        $path
	 * @return void
	 */
	public static function load($packages, $path = PACKAGE_PATH)
	{
		foreach ((array) $packages as $package)
		{
			if ( ! static::loaded($package) and file_exists($bootstrap = $path.$package.'/bootstrap'.EXT))
			{
				require $bootstrap;
			}

			static::$loaded[] = $package;
		}
	}

	/**
	 * Determine if a given package has been loaded.
	 *
	 * <code>
	 *		// Determine if the "swift-mailer" package has been loaded
	 *		$loaded = Package::loaded('swift-mailer');
	 * </code>
	 *
	 * @param  string  $package
	 * @return bool
	 */
	public static function loaded($package)
	{
		return array_key_exists($package, static::$loaded);
	}

}