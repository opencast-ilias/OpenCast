<?php

/**
 * Class xoctLog
 * TODO: initialize and make available in OpencastDIC
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctLog extends ilLog
{
    public const DEBUG_DEACTIVATED = 0;
    public const DEBUG_LEVEL_1 = 1;
    public const DEBUG_LEVEL_2 = 2;
    public const DEBUG_LEVEL_3 = 3;
    public const DEBUG_LEVEL_4 = 4;
    public const OD_LOG = 'curl.log';
    /**
     * @var xoctLog
     */
    protected static $instance;
    /**
     * @var int
     */
    protected static $log_level = self::DEBUG_DEACTIVATED;

    /**
     * @param $log_level
     */
    public static function init($log_level)
    {
        self::$log_level = $log_level;
    }

    /**
     * @param $log_level
     *
     * @return bool
     */
    public static function relevant($log_level)
    {
        return $log_level <= self::$log_level;
    }

    /**
     * @return xoctLog
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            if (ILIAS_LOG_DIR === "php:/" && ILIAS_LOG_FILE === "stdout") {
                // Fix Docker-ILIAS log
                self::$instance = new self(ILIAS_LOG_DIR, ILIAS_LOG_FILE);
            } else {
                self::$instance = new self(ILIAS_LOG_DIR, self::OD_LOG);
            }
        }

        return self::$instance;
    }

    /**
     * @param      $a_msg
     * @param null $log_level
     */
    public function write($a_msg, $log_level = null)
    {
        if (self::relevant($log_level)) {
            parent::write($a_msg);
        }
    }

    public function writeTrace()
    {
        try {
            throw new Exception();
        } catch (Exception $e) {
            parent::write($e->getTraceAsString());
        }
    }

    /**
     * @return mixed
     */
    public function getLogDir()
    {
        return ILIAS_LOG_DIR;
    }

    /**
     * @return string
     */
    public function getLogFile()
    {
        if (ILIAS_LOG_DIR === "php:/" && ILIAS_LOG_FILE === "stdout") {
            // Fix Docker-ILIAS log
            return ILIAS_LOG_FILE;
        } else {
            return self::OD_LOG;
        }
    }

    /**
     * @return string
     */
    public static function getFullPath()
    {
        $log = self::getInstance();

        return $log->getLogDir() . '/' . $log->getLogFile();
    }

    /**
     * @return int
     */
    public static function getLogLevel()
    {
        return self::$log_level;
    }
}
