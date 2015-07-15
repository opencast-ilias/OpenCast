<?php

/**
 * Class xoctException
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctException extends Exception {

	const API_CALL_UNSUPPORTED = 10;
	const OBJECT_WRONG_PARENT = 20;
	const API_CREATION_FAILED = 30;
	const NO_USER_MAPPING = 40;
	const API_CALL_STATUS_500 = 500;
	/**
	 * @var array
	 */
	protected static $messages = array(
		self::API_CALL_UNSUPPORTED => 'This Api-Call is not supported',
		self::API_CALL_STATUS_500 => 'An error occurred during the request',
		self::OBJECT_WRONG_PARENT => 'OpenCast-Object have to be in courses',
		self::API_CREATION_FAILED => 'The response from the OpenCast-Server was wrong. The series has not been created on the server. Please delete the ILIAS-Object.',
		self::NO_USER_MAPPING => 'Your user-account cannot communicate with the OpenCast-Server. please contact your system administrator.',
	);


	/**
	 * @param string $code
	 * @param string $additional_message
	 */
	public function __construct($code, $additional_message = '') {
		$message = '';
		if (isset(self::$messages[$code])) {
			$message = self::$messages[$code];
		}
		if ($additional_message) {
			$message .= ': ' . $additional_message;
		}
		parent::__construct($message, $code);
	}
}

?>
