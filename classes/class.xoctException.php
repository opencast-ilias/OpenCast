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
	const INTERNAL_ERROR = 50;
	const NO_STREAMING_DATA = 60;
	const API_CALL_STATUS_500 = 500;
	const API_CALL_STATUS_403 = 403;
	const API_CALL_STATUS_404 = 404;
	const API_CALL_STATUS_409 = 409;
	const API_CALL_BAD_CREDENTIALS = 401;
	/**
	 * @var array
	 */
	protected static $messages = array(
		self::API_CALL_UNSUPPORTED => 'This Api-Call is not supported',
		self::API_CALL_STATUS_500 => 'An error occurred while communicating with the OpencastObject-Server',
		self::API_CALL_STATUS_403 => 'Access denied',
		self::API_CALL_STATUS_404 => 'Not Found',
		self::API_CALL_STATUS_409 => 'Conflict',
		self::OBJECT_WRONG_PARENT => 'OpencastObject-Object have to be in courses',
		self::API_CREATION_FAILED => 'The response from the OpencastObject-Server was wrong. The series has not been created on the server. Please delete the ILIAS-Object.',
		self::NO_USER_MAPPING => 'Your user-account cannot communicate with the OpencastObject-Server. please contact your system administrator.',
		self::API_CALL_BAD_CREDENTIALS => 'The OpencastObject-Server cannot be accessed at the moment.',
		self::INTERNAL_ERROR => 'A plugin-internal error occured.',
		self::NO_STREAMING_DATA => 'No streaming data found.',
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
