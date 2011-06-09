<?php namespace System;

class HTML {

	/**
	 * Generate a JavaScript reference.
	 *
	 * @param  string  $url
	 * @return string
	 */
	public static function script($url)
	{
		return '<script type="text/javascript" src="'.trim(URL::to($url), '.js').'.js"></script>'.PHP_EOL;
	}

	/**
	 * Generate a CSS reference.
	 *
	 * @param  string  $url
	 * @return string
	 */
	public static function style($url, $media = 'all')
	{
		return '<link href="'.trim(URL::to($url), '.css').'.css" rel="stylesheet" type="text/css" media="'.$media.'" />'.PHP_EOL;
	}

	/**
	 * Generate a HTML link.
	 *
	 * @param  string  $url
	 * @param  string  $title
	 * @param  array   $attributes
	 * @param  bool    $https
	 * @return string
	 */
	public static function link($url, $title, $attributes = array(), $https = false)
	{
		return '<a href="'.URL::to($url, $https).'"'.static::attributes($attributes).'>'.Str::entities($title).'</a>';
	}

	/**
	 * Generate a HTTPS HTML link.
	 *
	 * @param  string  $url
	 * @param  string  $title
	 * @param  array   $attributes
	 * @return string
	 */
	public static function secure_link($url, $title, $attributes)
	{
		return static::link($url, $title, $attributes, true);
	}

	/**
	 * Generate an HTML mailto link.
	 *
	 * @param  string  $email
	 * @param  string  $title
	 * @param  array   $attributes
	 * @return string
	 */
	public static function mailto($email, $title = null, $attributes = array())
	{
		// -------------------------------------------------------
		// Obfuscate the e-mail address.
		// -------------------------------------------------------
		$email = static::email($email);

		// -------------------------------------------------------
		// If no title is specified, just use the e-mail address.
		// -------------------------------------------------------
		if (is_null($title))
		{
			$title = $email;
		}

		return '<a href="&#109;&#097;&#105;&#108;&#116;&#111;&#058;'.$email.'"'.static::attributes($attributes).'>'.$title.'</a>';
	}

	/**
	 * Obfuscate an e-mail address to prevent spam-bots from sniffing it.
	 *
	 * @param  string  $email
	 * @return string
	 */
	public static function email($email)
	{
		return str_replace('@', '&#64;', static::obfuscate($email));
	}

	/**
	 * Generate an HTML image.
	 *
	 * @param  string  $url
	 * @param  string  $alt
	 * @param  array   $attributes
	 * @return string
	 */
	public static function image($url, $alt = '', $attributes = array())
	{
		// -------------------------------------------------------
		// Add the "alt" tag to the attributes.
		// -------------------------------------------------------
		$attributes['alt'] = Str::entities($alt);

		return '<img src="'.URL::to($url).'"'.static::attributes($attributes).' />';
	}

	/**
	 * Generate HTML breaks.
	 *
	 * @param  int     $count
	 * @return string
	 */
	public static function breaks($count = 1)
	{
		return str_repeat('<br />', $count);
	}

	/**
	 * Generate non-breaking spaces.
	 *
	 * @param  int     $count
	 * @return string
	 */
	public static function spaces($count = 1)
	{
		return str_repeat('&nbsp;', $count);
	}

	/**
	 * Generate an ordered list.
	 *
	 * @param  array   $list
	 * @param  array   $attributes
	 * @return string
	 */
	public static function ol($list, $attributes = array())
	{
		return static::list_elements('ol', $list, $attributes);
	}

	/**
	 * Generate an un-ordered list.
	 *
	 * @param  array   $list
	 * @param  array   $attributes
	 * @return string
	 */
	public static function ul($list, $attributes = array())
	{
		return static::list_elements('ul', $list, $attributes);
	}

	/**
	 * Generate an ordered or un-ordered list.
	 *
	 * @param  string  $type
	 * @param  array   $list
	 * @param  array   $attributes
	 * @return string
	 */
	private static function list_elements($type, $list, $attributes)
	{
		// -------------------------------------------------------
		// Verify the list is an array.
		// -------------------------------------------------------
		if ( ! is_array($list))
		{
			return '';
		}

		// -------------------------------------------------------
		// Initialize the output value.
		// -------------------------------------------------------
		$html = '';

		// -------------------------------------------------------
		// Add the list items.
		// -------------------------------------------------------
		foreach ($list as $key => $value)
		{
			$html .= '<li>'.Str::entities($value).'</li>';
		}

		// -------------------------------------------------------
		// Build the list opening tag.
		// -------------------------------------------------------
		$start = '<'.$type.static::attributes($attributes).'>';

		return $start.$html.'</'.$type.'>';
	}

	/**
	 * Build a list of HTML attributes.
	 *
	 * @param  array   $attributes
	 * @return string
	 */		
	public static function attributes($attributes)
	{
		$html = array();

		foreach ($attributes as $key => $value)
		{
			// -------------------------------------------------------
			// If the value is null, skip it.
			// -------------------------------------------------------
			if (is_null($value))
			{
				continue;
			}

			// -------------------------------------------------------
			// Add the HTML attribute to the array of attributes.
			// -------------------------------------------------------
			$html[] = $key.'="'.Str::entities($value).'"';
		}

		// -------------------------------------------------------
		// Concatenate all of the attributes together.
		// -------------------------------------------------------
		if (count($html) > 0)
		{
			return ' '.implode(' ', $html);
		}
		else
		{
			return '';
		}
	}

	/**
	 * Obfuscate a string to prevent spam-bots from sniffing it.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public static function obfuscate($value)
	{
		$safe = '';

		// -------------------------------------------------------
		// Spin through the string letter by letter.
		// -------------------------------------------------------
		foreach (str_split($value) as $letter)
		{
			switch (rand(1, 3))
			{
				// -------------------------------------------------------
				// Convert the letter to its entity representation.
				// -------------------------------------------------------
				case 1:
					$safe .= '&#'.ord($letter).';';
					break;

				// -------------------------------------------------------
				// Convert the letter to a Hex character code.
				// -------------------------------------------------------
				case 2:
					$safe .= '&#x'.dechex(ord($letter)).';';
					break;

				// -------------------------------------------------------
				// No encoding.
				// -------------------------------------------------------
				case 3:
					$safe .= $letter;
			}
		}

		return $safe;
	}

}