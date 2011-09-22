<?php namespace Laravel;

use Closure;

class Arr {

	/**
	 * Get an item from an array.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public static function get($array, $key, $default = null)
	{
		if (is_null($key)) return $array;

		foreach (explode('.', $key) as $segment)
		{
			if ( ! is_array($array) or ! array_key_exists($segment, $array))
			{
				return ($default instanceof Closure) ? call_user_func($default) : $default;
			}

			$array = $array[$segment];
		}

		return $array;
	}

	/**
	 * Set an array item to a given value.
	 *
	 * The same "dot" syntax used by the "get" method may be used here.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public static function set(&$array, $key, $value)
	{
		if (is_null($key)) return $array = $value;

		$keys = explode('.', $key);

		while (count($keys) > 1)
		{
			$key = array_shift($keys);

			if ( ! isset($array[$key]) or ! is_array($array[$key]))
			{
				$array[$key] = array();
			}

			$array =& $array[$key];
		}

		$array[array_shift($keys)] = $value;
	}

	/**
	 * Return the first element in an array which passes a given truth test.
	 *
	 * @param  array    $array
	 * @param  Closure  $callback
	 * @return mixed
	 */
	public static function first($array, $callback, $default = null)
	{
		foreach ($array as $key => $value)
		{
			if (call_user_func($callback, $key, $value)) return $value;
		}

		return ($default instanceof Closure) ? call_user_func($default) : $default;
	}

	/**
	 * Remove all array values that are contained within a given array of values.
	 *
	 * @param  array  $array
	 * @param  array  $without
	 * @return array
	 */
	public static function without($array, $without = array())
	{
		foreach ($array as $key => $value)
		{
			if (in_array($value, $without)) unset($array[$key]);
		}

		return $array;
	}

}