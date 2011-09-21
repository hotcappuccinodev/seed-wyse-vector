<?php namespace Laravel;

class Asset {

	/**
	 * All of the instantiated asset containers.
	 *
	 * @var array
	 */
	protected static $containers = array();

	/**
	 * Get an asset container instance.
	 *
	 * If no container name is specified, the default container will be returned.
	 * Containers provide a convenient method of grouping assets while maintaining
	 * expressive code and a clean API.
	 *
	 * <code>
	 *		// Get an instance of the default asset container
	 *		$container = Asset::container();
	 *
	 *		// Get an instance of the "footer" container
	 *		$container = Asset::container('footer');
	 * </code>
	 *
	 * @param  string            $container
	 * @return Asset_Container
	 */
	public static function container($container = 'default')
	{
		if ( ! isset(static::$containers[$container]))
		{
			static::$containers[$container] = new Asset_Container($container);
		}

		return static::$containers[$container];
	}

	/**
	 * Magic Method for calling methods on the default Asset container.
	 *
	 * <code>
	 *		// Call the "add" method on the default asset container
	 *		Asset::add('jquery', 'js/jquery.js');
	 *
	 *		// Get all of the styles from the default container
	 *		echo Asset::styles();
	 * </code>
	 */
	public static function __callStatic($method, $parameters)
	{
		return call_user_func_array(array(static::container(), $method), $parameters);
	}

}

class Asset_Container {

	/**
	 * The asset container name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * All of the registered assets.
	 *
	 * @var array
	 */
	public $assets = array();

	/**
	 * Create a new asset container instance.
	 *
	 * @param  string  $name
	 * @param  HTML    $html
	 * @return void
	 */
	public function __construct($name)
	{
		$this->name = $name;
	}

	/**
	 * Add an asset to the container.
	 *
	 * The extension of the asset source will be used to determine the type of
	 * asset being registered (CSS or JavaScript). If you are using a non-standard
	 * extension, you may use the style or script methods to register assets.
	 *
	 * You may also specify asset dependencies. This will instruct the class to
	 * only link to the registered asset after its dependencies have been linked.
	 * For example, you may wish to make jQuery UI dependent on jQuery.
	 *
	 * <code>
	 *		// Add an asset to the container
	 *		Asset::container()->add('style', 'style.css');
	 *
	 *		// Add an asset to the container with attributes
	 *		Asset::container()->add('style', 'style.css', array(), array('media' => 'print'));
	 *
	 *		// Add an asset to the container with dependencies
	 *		Asset::container()->add('jquery', 'jquery.js', array('jquery-ui'));
	 * </code>
	 *
	 * @param  string  $name
	 * @param  string  $source
	 * @param  array   $dependencies
	 * @param  array   $attributes
	 * @return void
	 */
	public function add($name, $source, $dependencies = array(), $attributes = array())
	{
		$type = (pathinfo($source, PATHINFO_EXTENSION) == 'css') ? 'style' : 'script';

		return call_user_func(array($this, $type), $name, $source, $dependencies, $attributes);
	}

	/**
	 * Add a CSS file to the registered assets.
	 *
	 * <code>
	 *		// Add a CSS file to the registered assets
	 *		Asset::container()->style('common', 'common.css');
	 *
	 *		// Add a CSS file with dependencies to the registered assets
	 *		Asset::container()->style('common', 'common.css', array('reset'));
	 *
	 *		// Add a CSS file with attributes to the registered assets
	 *		Asset::container()->style('common', 'common.css', array(), array('media' => 'print'));
	 * </code>
	 *
	 * @param  string           $name
	 * @param  string           $source
	 * @param  array            $dependencies
	 * @param  array            $attributes
	 * @return Asset_Container
	 */
	public function style($name, $source, $dependencies = array(), $attributes = array())
	{
		if ( ! array_key_exists('media', $attributes))
		{
			$attributes['media'] = 'all';
		}

		$this->register('style', $name, $source, $dependencies, $attributes);

		return $this;
	}

	/**
	 * Add a JavaScript file to the registered assets.
	 *
	 * <code>
	 *		// Add a CSS file to the registered assets
	 *		Asset::container()->script('jquery', 'jquery.js');
	 *
	 *		// Add a CSS file with dependencies to the registered assets
	 *		Asset::container()->script('jquery', 'jquery.js', array('jquery-ui'));
	 *
	 *		// Add a CSS file with attributes to the registered assets
	 *		Asset::container()->script('loader', 'loader.js', array(), array('defer'));
	 * </code>
	 *
	 * @param  string           $name
	 * @param  string           $source
	 * @param  array            $dependencies
	 * @param  array            $attributes
	 * @return Asset_Container
	 */
	public function script($name, $source, $dependencies = array(), $attributes = array())
	{
		$this->register('script', $name, $source, $dependencies, $attributes);

		return $this;
	}

	/**
	 * Add an asset to the array of registered assets.
	 *
	 * Assets are organized in the array by type (CSS or JavaScript).
	 *
	 * @param  string  $type
	 * @param  string  $name
	 * @param  string  $source
	 * @param  array   $dependencies
	 * @param  array   $attributes
	 * @return void
	 */
	protected function register($type, $name, $source, $dependencies, $attributes)
	{
		$dependencies = (array) $dependencies;

		$this->assets[$type][$name] = compact('source', 'dependencies', 'attributes');
	}

	/**
	 * Get the links to all of the registered CSS assets.
	 *
	 * @return  string
	 */
	public function styles()
	{
		return $this->group('style');
	}

	/**
	 * Get the links to all of the registered JavaScript assets.
	 *
	 * @return  string
	 */
	public function scripts()
	{
		return $this->group('script');
	}

	/**
	 * Get all of the registered assets for a given type / group.
	 *
	 * @param  string  $group
	 * @return string
	 */
	protected function group($group)
	{
		if ( ! isset($this->assets[$group]) or count($this->assets[$group]) == 0) return '';

		$assets = '';

		foreach ($this->arrange($this->assets[$group]) as $name => $data)
		{
			$assets .= $this->asset($group, $name);
		}
		
		return $assets;
	}

	/**
	 * Get the HTML link to a registered asset.
	 *
	 * @param  string  $group
	 * @param  string  $name
	 * @return string
	 */
	protected function asset($group, $name)
	{
		if ( ! isset($this->assets[$group][$name])) return '';

		$asset = $this->assets[$group][$name];

		return HTML::$group($asset['source'], $asset['attributes']);
	}

	/**
	 * Sort and retrieve assets based on their dependencies
	 *
	 * @param   array  $assets
	 * @return  array
	 */
	protected function arrange($assets)
	{
		list($original, $sorted) = array($assets, array());

		while (count($assets) > 0)
		{
			foreach ($assets as $asset => $value)
			{
				$this->evaluate_asset($asset, $value, $original, $sorted, $assets);
			}
		}
		
		return $sorted;
	}

	/**
	 * Evaluate an asset and its dependencies.
	 *
	 * @param  string  $asset
	 * @param  string  $value
	 * @param  array   $original
	 * @param  array   $sorted
	 * @param  array   $assets
	 * @return void
	 */
	protected function evaluate_asset($asset, $value, $original, &$sorted, &$assets)
	{
		// If the asset has no more dependencies, we can add it to the sorted list
		// and remove it from the array of assets. Otherwise, we will not verify
		// the asset's dependencies and determine if they have already been sorted.
		if (count($assets[$asset]['dependencies']) == 0)
		{
			$sorted[$asset] = $value;
			unset($assets[$asset]);
		}
		else
		{
			foreach ($assets[$asset]['dependencies'] as $key => $dependency)
			{
				if ( ! $this->dependency_is_valid($asset, $dependency, $original, $assets))
				{
					unset($assets[$asset]['dependencies'][$key]);
					continue;
				}

				// If the dependency has not yet been added to the sorted list, we can not
				// remove it from this asset's array of dependencies. We'll try again on
				// the next trip through the loop.
				if ( ! isset($sorted[$dependency])) continue;

				unset($assets[$asset]['dependencies'][$key]);
			}
		}		
	}

	/**
	 * Verify that an asset's dependency is valid.
	 *
	 * A dependency is considered valid if it exists, is not a circular reference, and is
	 * not a reference to the owning asset itself.
	 *
	 * @param  string  $asset
	 * @param  string  $dependency
	 * @param  array   $original
	 * @param  array   $assets
	 * @return bool
	 */
	protected function dependency_is_valid($asset, $dependency, $original, $assets)
	{
		if ( ! isset($original[$dependency])) return false;

		if ($dependency === $asset)
		{
			throw new \Exception("Asset [$asset] is dependent on itself.");
		}
		elseif (isset($assets[$dependency]) and in_array($asset, $assets[$dependency]['dependencies']))
		{
			throw new \Exception("Assets [$asset] and [$dependency] have a circular dependency.");
		}
	}

}