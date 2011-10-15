<?php namespace Laravel\Security; use Laravel\Config;

if (trim(Config::$items['application']['key']) === '')
{
	throw new \Exception('The encryption class may not be used without an application key.');
}

class Crypter {

	/**
	 * The encryption cipher.
	 *
	 * @var string
	 */
	protected static $cipher = MCRYPT_RIJNDAEL_256;

	/**
	 * The encryption mode.
	 *
	 * @var string
	 */
	protected static $mode = 'cbc';

	/**
	 * Encrypt a string using Mcrypt.
	 *
	 * The string will be encrypted using the cipher and mode specified when the
	 * crypter instance was created, and the final result will be base64 encoded.
	 *
	 * <code>
	 *		// Encrypt a string using the Mcrypt PHP extension
	 *		$encrypted = Crypter::encrpt('secret');
	 * </code>
	 *
	 * @param  string  $value
	 * @return string
	 */
	public static function encrypt($value)
	{
		// Determine the most appropriate random number generator for the
		// OS and system and environment the application is running on.
		if (defined('MCRYPT_DEV_URANDOM'))
		{
			$randomizer = MCRYPT_DEV_URANDOM;
		}
		elseif (defined('MCRYPT_DEV_RANDOM'))
		{
			$randomizer = MCRYPT_DEV_RANDOM;
		}
		else
		{
			$randomizer = MCRYPT_RAND;			
		}

		$iv = mcrypt_create_iv(static::iv_size(), $randomizer);

		$key = Config::$items['application']['key'];

		return base64_encode($iv.mcrypt_encrypt(static::$cipher, $key, $value, static::$mode, $iv));
	}

	/**
	 * Decrypt a string using Mcrypt.
	 *
	 * <code>
	 *		// Decrypt a string using the Mcrypt PHP extension
	 *		$decrypted = Crypter::decrypt($secret);
	 * </code>
	 *
	 * @param  string  $value
	 * @return string
	 */
	public static function decrypt($value)
	{
		// Since all encrypted strings generated by this class are base64
		// encoded, we will first attempt to base64 decode the string.
		// If we can't do it, we'll bail out.
		if ( ! is_string($value = base64_decode($value, true)))
		{
			throw new \Exception('Decryption error. Input value is not valid base64 data.');
		}

		// Extract the input vector and the encrypted string from the value.
		// These will be used by Mcrypt to properly decrypt the value.
		$iv = substr($value, 0, static::iv_size());

		$value = substr($value, static::iv_size());

		$key = Config::$items['application']['key'];

		return rtrim(mcrypt_decrypt(static::$cipher, $key, $value, static::$mode, $iv), "\0");
	}

	/**
	 * Get the input vector size for the cipher and mode.
	 *
	 * Different ciphers and modes use varying lengths of input vectors.
	 *
	 * @return int
	 */
	protected static function iv_size()
	{
		return mcrypt_get_iv_size(static::$cipher, static::$mode);
	}

}