<?php namespace Laravel; use Closure;

class View_Factory {

	/**
	 * The directory containing the views.
	 *
	 * @var string
	 */
	public $path;

	/**
	 * The view composer instance.
	 *
	 * @var View_Composer
	 */
	protected $composer;

	/**
	 * Create a new view factory instance.
	 *
	 * @param  View_Composer  $composer
	 * @param  string         $path
	 * @return void
	 */
	public function __construct(View_Composer $composer, $path)
	{
		$this->path = $path;
		$this->composer = $composer;
	}

	/**
	 * Create a new view instance.
	 *
	 * The name of the view given to this method should correspond to a view
	 * within your application views directory. Dots or slashes may used to
	 * reference views within sub-directories.
	 *
	 * <code>
	 *		// Create a new view instance
	 *		$view = View::make('home.index');
	 *
	 *		// Create a new view instance with bound data
	 *		$view = View::make('home.index', array('name' => 'Taylor'));
	 * </code>
	 *
	 * @param  string  $view
	 * @param  array   $data
	 * @return View
	 */
	public function make($view, $data = array())
	{
		return new View($this, $this->composer, $view, $data, $this->path($view));
	}

	/**
	 * Create a new view instance from a view name.
	 *
	 * View names are defined in the application composers file.
	 *
	 * <code>
	 *		// Create an instance of the "layout" named view
	 *		$view = View::of('layout');
	 *
	 *		// Create an instance of the "layout" view with bound data
	 *		$view = View::of('layout', array('name' => 'Taylor'));
	 * </code>
	 *
	 * @param  string  $name
	 * @param  array   $data
	 * @return View
	 */
	public function of($name, $data = array())
	{
		if ( ! is_null($view = $this->composer->name($name))) return $this->make($view, $data);

		throw new \Exception("Named view [$name] is not defined.");
	}

	/**
	 * Get the path to a given view on disk.
	 *
	 * @param  string  $view
	 * @return string
	 */
	protected function path($view)
	{
		$view = str_replace('.', '/', $view);

		// A view may have a regular extension or a blade extension. We will need to
		// check for both extensions when determining the path to the view.
		foreach (array(EXT, BLADE_EXT) as $extension)
		{
			if (file_exists($path = $this->path.$view.$extension)) return $path;
		}

		throw new \Exception("View [$view] does not exist.");
	}

	/**
	 * Magic Method for handling the dynamic creation of named views.
	 *
	 * <code>
	 *		// Create an instance of the "layout" named view
	 *		$view = View::of_layout();
	 *
	 *		// Create an instance of a named view with data
	 *		$view = View::of_layout(array('name' => 'Taylor'));
	 * </code>
	 */
	public function __call($method, $parameters)
	{
		if (strpos($method, 'of_') === 0)
		{
			return $this->of(substr($method, 3), Arr::get($parameters, 0, array()));
		}
	}

}


class View_Composer {

	/**
	 * The view composers.
	 *
	 * @var array
	 */
	protected $composers;

	/**
	 * Create a new view composer instance.
	 *
	 * @param  array      $composers
	 * @return void
	 */
	public function __construct($composers)
	{
		$this->composers = $composers;
	}

	/**
	 * Find the key for a view by name.
	 *
	 * The view's key can be used to create instances of the view through the typical
	 * methods available on the view factory.
	 *
	 * @param  string  $name
	 * @return string
	 */
	public function name($name)
	{
		// The view's name may specified in several different ways in the composers file.
		// The composer may simple have a string value, which is the name. Or, the composer
		// could have an array value in which a "name" key exists.
		foreach ($this->composers as $key => $value)
		{
			if ($name === $value or (isset($value['name']) and $name === $value['name'])) { return $key; }
		}
	}

	/**
	 * Call the composer for the view instance.
	 *
	 * @param  View  $view
	 * @return void
	 */
	public function compose(View $view)
	{
		// The shared composer is called for every view instance. This allows the
		// convenient binding of global view data or partials within a single method.
		if (isset($this->composers['shared'])) call_user_func($this->composers['shared'], $view);

		if (isset($this->composers[$view->view]))
		{
			foreach ((array) $this->composers[$view->view] as $key => $value)
			{
				if ($value instanceof Closure) return call_user_func($value, $view);
			}
		}
	}

}


class View {

	/**
	 * The name of the view.
	 *
	 * @var string
	 */
	public $view;

	/**
	 * The view data.
	 *
	 * @var array
	 */
	public $data;

	/**
	 * The path to the view on disk.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * The view composer instance.
	 *
	 * @var View_Composer
	 */
	protected $composer;

	/**
	 * The view factory instance, which is used to create sub-views.
	 *
	 * @var View_Factory
	 */
	protected $factory;

	/**
	 * Create a new view instance.
	 *
	 * @param  View_Factory   $factory
	 * @param  View_Composer  $composer
	 * @param  string         $view
	 * @param  array          $data
	 * @param  string         $path
	 * @return void
	 */
	public function __construct(View_Factory $factory, View_Composer $composer, $view, $data, $path)
	{
		$this->view = $view;
		$this->data = $data;
		$this->path = $path;
		$this->factory = $factory;
		$this->composer = $composer;
	}

	/**
	 * Get the evaluated string content of the view.
	 *
	 * @return string
	 */
	public function render()
	{
		$this->composer->compose($this);

		// All nested views and responses are evaluated before the main view. This allows
		// the assets used by these views to be added to the asset container before the
		// main view is evaluated and dumps the links to the assets.
		foreach ($this->data as &$data) 
		{
			if ($data instanceof View or $data instanceof Response) $data = $data->render();
		}

		// We don't want the view's contents to be rendered immediately, so we will fire
		// up an output buffer to catch the view output. The output of the view will be
		// rendered automatically later in the request lifecycle.
		ob_start() and extract($this->data, EXTR_SKIP);

		// If the view is a "Blade" view, we need to check the view for modifications
		// and get the path to the compiled view file. Otherwise, we'll just use the
		// regular path to the view.
		$view = (strpos($this->path, BLADE_EXT) !== false) ? $this->compile() : $this->path;

		try { include $view; } catch (Exception $e) { ob_get_clean(); throw $e; }

		return ob_get_clean();
	}

	/**
	 * Compile the Bladed view and return the path to the compiled view.
	 *
	 * @return string
	 */
	protected function compile()
	{
		// For simplicity, compiled views are stored in a single directory by the MD5 hash of
		// their name. This allows us to avoid recreating the entire view directory structure
		// within the compiled views directory.
		$compiled = $this->factory->path.'compiled/'.md5($this->view);

		// The view will only be re-compiled if the view has been modified since the last compiled
	 	// version of the view was created or no compiled view exists. Otherwise, the path will
	 	// be returned without re-compiling.
		if ((file_exists($compiled) and filemtime($this->path) > filemtime($compiled)) or ! file_exists($compiled))
		{
			file_put_contents($compiled, Blade::parse($this->path));
		}

		return $compiled;
	}

	/**
	 * Add a view instance to the view data.
	 *
	 * <code>
	 *		// Add a view instance to a view's data
	 *		$view = View::make('foo')->partial('footer', 'partials.footer');
	 *
	 *		// Equivalent functionality using the "with" method
	 *		$view = View::make('foo')->with('footer', View::make('partials.footer'));
	 *
	 *		// Bind a view instance with data
	 *		$view = View::make('foo')->partial('footer', 'partials.footer', array('name' => 'Taylor'));
	 * </code>
	 *
	 * @param  string  $key
	 * @param  string  $view
	 * @param  array   $data
	 * @return View
	 */
	public function partial($key, $view, $data = array())
	{
		return $this->with($key, $this->factory->make($view, $data));
	}

	/**
	 * Add a key / value pair to the view data.
	 *
	 * Bound data will be available to the view as variables.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return View
	 */
	public function with($key, $value)
	{
		$this->data[$key] = $value;

		return $this;
	}

	/**
	 * Magic Method for getting items from the view data.
	 */
	public function __get($key)
	{
		return $this->data[$key];
	}

	/**
	 * Magic Method for setting items in the view data.
	 */
	public function __set($key, $value)
	{
		$this->with($key, $value);
	}

	/**
	 * Magic Method for determining if an item is in the view data.
	 */
	public function __isset($key)
	{
		return array_key_exists($key, $this->data);
	}

	/**
	 * Magic Method for removing an item from the view data.
	 */
	public function __unset($key)
	{
		unset($this->data[$key]);
	}

}