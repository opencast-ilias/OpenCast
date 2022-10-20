<?php

/**
 * Class xoctException
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctException extends Exception
{
    public const API_CALL_UNSUPPORTED = 10;
    public const OBJECT_WRONG_PARENT = 20;
    public const API_CREATION_FAILED = 30;
    public const NO_USER_MAPPING = 40;
    public const INTERNAL_ERROR = 50;
    public const NO_STREAMING_DATA = 60;
    public const API_CALL_STATUS_500 = 500;
    public const API_CALL_STATUS_403 = 403;
    public const API_CALL_STATUS_404 = 404;
    public const API_CALL_STATUS_409 = 409;
    public const API_CALL_BAD_CREDENTIALS = 401;
    /**
     * @var array
     */
    protected static $messages = [
        self::API_CALL_UNSUPPORTED => 'This Api-Call is not supported',
        self::API_CALL_STATUS_500 => 'An error occurred while communicating with the OpenCast-Server',
        self::API_CALL_STATUS_403 => 'Access denied',
        self::API_CALL_STATUS_404 => 'Not Found',
        self::API_CALL_STATUS_409 => 'Conflict',
        self::OBJECT_WRONG_PARENT => 'OpenCast-Object have to be in courses',
        self::API_CREATION_FAILED => 'The response from the OpenCast-Server was wrong. The series has not been created on the server. Please delete the ILIAS-Object.',
        self::NO_USER_MAPPING => 'Your user-account cannot communicate with the OpenCast-Server. please contact your system administrator.',
        self::API_CALL_BAD_CREDENTIALS => 'The OpenCast-Server cannot be accessed at the moment.',
        self::INTERNAL_ERROR => 'A plugin-internal error occured.',
        self::NO_STREAMING_DATA => 'No streaming data found.',
    ];


    /**
     * @param string $code
     * @param string $additional_message
     */
    public function __construct($code, $additional_message = '')
    {
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
