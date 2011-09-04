<?php namespace Laravel\Security;

use Laravel\IoC;
use Laravel\Facade;

class Crypter_Facade extends Facade { public static $resolve = 'crypter'; }

class Crypter {

	/**
	 * The encryption cipher.
	 *
	 * @var string
	 */
	public $cipher;

	/**
	 * The encryption mode.
	 *
	 * @var string
	 */
	public $mode;

	/**
	 * The encryption key.
	 *
	 * @var string
	 */
	public $key;

	/**
	 * Create a new Crypter instance.
	 *
	 * @param  string  $cipher
	 * @param  string  $mode
	 * @param  string  $key
	 * @return void
	 */
	public function __construct($cipher, $mode, $key)
	{
		$this->cipher = $cipher;
		$this->mode = $mode;
		$this->key = $key;

		if (trim((string) $this->key) === '')
		{
			throw new \Exception('The encryption class may not be used without an encryption key.');
		}
	}

	/**
	 * Encrypt a string using Mcrypt.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function encrypt($value)
	{
		$iv = mcrypt_create_iv($this->iv_size(), $this->randomizer());

		return base64_encode($iv.mcrypt_encrypt($this->cipher, $this->key, $value, $this->mode, $iv));
	}

	/**
	 * Get the random number source available to the OS.
	 *
	 * @return int
	 */
	protected function randomizer()
	{
		if (defined('MCRYPT_DEV_URANDOM'))
		{
			return MCRYPT_DEV_URANDOM;
		}
		elseif (defined('MCRYPT_DEV_RANDOM'))
		{
			return MCRYPT_DEV_RANDOM;
		}

		return MCRYPT_RAND;
	}

	/**
	 * Decrypt a string using Mcrypt.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function decrypt($value)
	{
		if ( ! is_string($value = base64_decode($value, true)))
		{
			throw new \Exception('Decryption error. Input value is not valid base64 data.');
		}

		list($iv, $value) = array(substr($value, 0, $this->iv_size()), substr($value, $this->iv_size()));

		return rtrim(mcrypt_decrypt($this->cipher, $this->key, $value, $this->mode, $iv), "\0");
	}

	/**
	 * Get the input vector size for the cipher and mode.
	 *
	 * Different ciphers and modes use varying lengths of input vectors.
	 *
	 * @return int
	 */
	private function iv_size()
	{
		return mcrypt_get_iv_size($this->cipher, $this->mode);
	}

}