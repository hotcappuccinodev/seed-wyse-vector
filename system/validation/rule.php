<?php namespace System\Validation;

use System\Lang;

abstract class Rule {

	/**
	 * The attributes being validated by the rule.
	 *
	 * @var array
	 */
	public $attributes;

	/**
	 * The validation error message.
	 *
	 * @var string
	 */
	public $message;

	/**
	 * The error type. This is used for rules that have more than
	 * one type of error such as Size_Of and Upload_Of.
	 *
	 * @var string
	 */
	protected $error;

	/**
	 * Create a new validation Rule instance.
	 *
	 * @param  array      $attributes
	 * @return void
	 */
	public function __construct($attributes)
	{
		$this->attributes = $attributes;
	}

	/**
	 * Run the validation rule.
	 *
	 * @param  array  $attributes
	 * @param  array  $errors
	 * @return void
	 */
	public function validate($attributes, &$errors)
	{
		foreach ($this->attributes as $attribute)
		{
			if ( ! $this->check($attribute, $attributes))
			{
				$errors[$attribute][] = $this->prepare_message($attribute);
			}
		}
	}

	/**
	 * Prepare the message to be added to the error collector.
	 *
	 * @param  string  $attribute
	 * @return string
	 */
	private function prepare_message($attribute)
	{
		if (is_null($this->message))
		{
			throw new \Exception("An error message must be specified for every validation rule.");
		}

		$message = $this->message;

		// ---------------------------------------------------------
		// Replace any place-holders with their actual values.
		//
		// Attribute place-holders are loaded from the language
		// directory. If the line doesn't exist, the attribute
		// name will be used instead.
		// ---------------------------------------------------------
		if (strpos($message, ':attribute'))
		{
			$message = str_replace(':attribute', Lang::line('attributes.'.$attribute)->get($attribute), $message);
		}

		if ($this instanceof Rules\Size_Of)
		{
			$message = str_replace(':max', $this->maximum, $message);
			$message = str_replace(':min', $this->minimum, $message);
			$message = str_replace(':size', $this->length, $message);
		}

		return $message;
	}

	/**
	 * Set the validation error message.
	 *
	 * @param  string  $message
	 * @return Rule
	 */
	public function message($message)
	{
		$this->message = $message;
		return $this;
	}

}