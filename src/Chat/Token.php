<?php

namespace srag\Plugins\Opencast\Chat;

/**
 * Class Token
 *
 * @package srag\Plugins\Opencast\Chat
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class Token {

	/**
	 * @var string
	 */
	protected $token;


	/**
	 * Token constructor.
	 *
	 * @param string $token
	 */
	public function __construct($token = '') {
		if ($token === '') {
			$token = openssl_random_pseudo_bytes(16);
			$token = bin2hex($token);
		}
		$this->token = $token;
	}


	/**
	 * @return string
	 */
	public function toString() {
		return $this->token;
	}


}