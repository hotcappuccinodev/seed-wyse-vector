<?php namespace System;

class Error {

	/**
	 * Error levels and descriptions.
	 *
	 * @var array
	 */
	public static $levels = array(
		0                  => 'Error',
		E_ERROR            => 'Error',
		E_WARNING          => 'Warning',
		E_PARSE            => 'Parsing Error',
		E_NOTICE           => 'Notice',
		E_CORE_ERROR       => 'Core Error',
		E_CORE_WARNING     => 'Core Warning',
		E_COMPILE_ERROR    => 'Compile Error',
		E_COMPILE_WARNING  => 'Compile Warning',
		E_USER_ERROR       => 'User Error',
		E_USER_WARNING     => 'User Warning',
		E_USER_NOTICE      => 'User Notice',
		E_STRICT           => 'Runtime Notice'
	);

	/**
	 * Handle an exception.
	 *
	 * @param  Exception  $e
	 * @return void
	 */
	public static function handle($e)
	{
		// -----------------------------------------------------
		// Clean the output buffer. We don't want any rendered
		// views or text to be sent to the browser.
		// -----------------------------------------------------
		if (ob_get_level() > 0)
		{
			ob_clean();
		}

		// -----------------------------------------------------
		// Get the error severity in human readable format.
		// -----------------------------------------------------
		$severity = (array_key_exists($e->getCode(), static::$levels)) ? static::$levels[$e->getCode()] : $e->getCode();

		// -----------------------------------------------------
		// Get the error file. Views require special handling
		// since view errors occur within eval'd code.
		// -----------------------------------------------------
		if (strpos($e->getFile(), 'view.php') !== false and strpos($e->getFile(), "eval()'d code") !== false)
		{
			$file = APP_PATH.'views/'.View::$last.EXT;
		}
		else
		{
			$file = $e->getFile();
		}

		$message = rtrim($e->getMessage(), '.');

		if (Config::get('error.log'))
		{
			Log::error($message.' in '.$e->getFile().' on line '.$e->getLine());
		}

		// -----------------------------------------------------
		// Detailed error view contains the file name and stack
		// trace of the error. It is not wise to have details
		// enabled in a production environment.
		//
		// The generic error view (error/500) only has a simple,
		// generic error message suitable for production.
		// -----------------------------------------------------
		if (Config::get('error.detail'))
		{
			$view = View::make('exception')
									->bind('severity', $severity)
									->bind('message', $message)
									->bind('file', $file)
									->bind('line', $e->getLine())
									->bind('trace', $e->getTraceAsString())
									->bind('contexts', static::context($file, $e->getLine()));
			
			Response::make($view, 500)->send();
		}
		else
		{
			Response::make(View::make('error/500'), 500)->send();
		}

		exit(1);
	}

	/**
	 * Get the file context of an exception.
	 *
	 * @param  string  $path
	 * @param  int     $line
	 * @param  int     $padding
	 * @return array
	 */
	private static function context($path, $line, $padding = 5)
	{
		if (file_exists($path))
		{
			$file = file($path, FILE_IGNORE_NEW_LINES);

			array_unshift($file, '');

			// -----------------------------------------------------
			// Calculate the starting position of the file context.
			// -----------------------------------------------------
			$start = $line - $padding;
			$start = ($start < 0) ? 0 : $start;

			// -----------------------------------------------------
			// Calculate the context length.
			// -----------------------------------------------------
			$length = ($line - $start) + $padding + 1;
			$length = (($start + $length) > count($file) - 1) ? null : $length;

			return array_slice($file, $start, $length, true);			
		}

		return array();
	}

}