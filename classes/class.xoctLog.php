<?php
require_once('./Services/Logging/classes/class.ilLog.php');

/**
 * Class xoctLog
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctLog extends ilLog {

	const DEBUG_DEACTIVATED = 0;
	const DEBUG_LEVEL_1 = 1;
	const DEBUG_LEVEL_2 = 2;
	const DEBUG_LEVEL_3 = 3;
	const DEBUG_LEVEL_4 = 4;
	const OD_LOG = 'curl.log';
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
	public static function init($log_level) {
		self::$log_level = $log_level;
	}


	/**
	 * @param $log_level
	 *
	 * @return bool
	 */
	public static function relevant($log_level) {
		return $log_level <= self::$log_level;
	}


	/**
	 * @return xoctLog
	 */
	public static function getInstance() {
		if (! isset(self::$instance)) {
			self::$instance = new self(ILIAS_LOG_DIR, self::OD_LOG);
		}

		return self::$instance;
	}


	/**
	 * @param      $a_msg
	 * @param null $log_level
	 */
	function write($a_msg, $log_level = null) {
		if (self::relevant($log_level)) {
			parent::write($a_msg);
		}
	}


	public function writeTrace() {
		try {
			throw new Exception();
		} catch (Exception $e) {
			parent::write($e->getTraceAsString());
		}
	}


	/**
	 * @return mixed
	 */
	public function getLogDir() {
		return ILIAS_LOG_DIR;
	}


	/**
	 * @return string
	 */
	public function getLogFile() {
		return self::OD_LOG;
	}


	/**
	 * @return string
	 */
	public static function getFullPath() {
		$log = self::getInstance();

		return $log->getLogDir() . '/' . $log->getLogFile();
	}


	/**
	 * @return int
	 */
	public static function getLogLevel() {
		return self::$log_level;
	}
}

?>
