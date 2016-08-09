<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctCache.php');

/**
 * Class xoctObject
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
abstract class xoctObject {

	/**
	 * @var array
	 */
	protected static $cache = array();
	/**
	 * @var bool
	 */
	protected $loaded = false;


	/**
	 * @param $identifier
	 *
	 * @return xoctObject
	 */
	public static function find($identifier) {
		$class_name = get_called_class();
		$key = $class_name . '-' . $identifier;
		if (self::$cache[$key] instanceof $class_name) {
			return self::$cache[$key];
		}
		$existing = xoctCache::getInstance()->get($key);

		if ($existing) {
			xoctLog::getInstance()->write('CACHE: used cached: ' . $key, xoctLog::DEBUG_LEVEL_2);

			return $existing;
		}
		xoctLog::getInstance()->write('CACHE: cached not used: ' . $key, xoctLog::DEBUG_LEVEL_2);
		$var = new $class_name($identifier);
		self::cache($identifier, $var);

		return $var;
	}


	/**
	 * @param $identifier
	 *
	 * @return xoctObject
	 */
	public static function removeFromCache($identifier) {
		$class_name = get_called_class();
		$key = $class_name . '-' . $identifier;
		self::$cache[$key] = null;
		xoctLog::getInstance()->write('CACHE: removed from cache: ' . $key, xoctLog::DEBUG_LEVEL_1);
		xoctCache::getInstance()->delete($key);
	}


	/**
	 * @param            $identifier
	 * @param xoctObject $object
	 */
	public static function cache($identifier, xoctObject $object) {
		$class_name = get_class($object);
		$key = $class_name . '-' . $identifier;
		self::$cache[$key] = $object;
		xoctLog::getInstance()->write('CACHE: added to cache: ' . $key, xoctLog::DEBUG_LEVEL_1);
		xoctCache::getInstance()->set($key, $object);
	}


	/**
	 * @return array
	 */
	public function __toArray() {
		$data = $this->__toStdClass();
		$array = (array)$data;

		return $array;
	}


	/**
	 * @return string
	 */
	public function __toCsv($separator = ';', $line_separator = "\n\r") {
		$csv = '';
		foreach ($this->__toArray() as $k => $v) {
			switch (true) {
				case $v instanceof DateTime:
					$csv .= $k . $separator . date(DATE_ISO8601, $v->getTimestamp()) . $line_separator;
					break;
				case $v instanceof stdClass:
				case is_array($v):
				case $v === null:
				case $v === false:
				case $v === '':
					break;
				default:
					$csv .= $k . $separator . $v . $line_separator;
					break;
			}
		}

		return $csv;
	}


	/**
	 * @return string
	 */
	public function __toJSON() {
		return json_encode($this->__toStdClass());
	}


	/**
	 * @return stdClass
	 */
	public function __toStdClass() {
		$r = new ReflectionClass($this);
		$stdClass = new stdClass();
		foreach ($r->getProperties() as $name) {
			$key = utf8_encode($name->getName());

			if ($key == 'cache') {
				continue;
			}

			$value = $this->sleep($key, $this->{$key});
			switch (true) {
				case ($value instanceof xoctObject):
					$stdClass->{$key} = $value->__toStdClass();
					break;
				case (is_array($value)):
					$a = array();
					foreach ($value as $k => $v) {
						if ($v instanceof xoctObject) {
							$a[$k] = $v->__toStdClass();
						} else {
							$a[$k] = self::convertToUtf8($v);
						}
					}
					$stdClass->{$key} = $a;
					break;
				case (is_bool($value)):
					$stdClass->{$key} = $value;
					break;
				case ($value instanceof DateTime):
					$stdClass->{$key} = $value->getTimestamp();
					break;
				case ($value instanceof stdClass):
					$a = array();
					$value = (array)$value;
					foreach ($value as $k => $v) {
						if ($v instanceof xoctObject) {
							$a[$k] = $v->__toStdClass();
						} else {
							$a[$k] = self::convertToUtf8($v);
						}
					}
					$stdClass->{$key} = $a;
					break;
				default:
					$stdClass->{$key} = self::convertToUtf8($value);
					break;
			}
		}

		return $stdClass;
	}


	/**
	 * @param $string
	 *
	 * @return string
	 */
	public static function convertToUtf8($string) {
		if (is_object($string)) {
			return $string;
		}

		return iconv(mb_detect_encoding($string, mb_detect_order(), true), "UTF-8", $string);
	}


	/**
	 * @param $class
	 * @throws xoctException
	 */
	public function loadFromStdClass($class) {
		if (!$class instanceof stdClass) {
			throw new xoctException(xoctException::API_CALL_STATUS_500);
		}
		$array = (array)$class;
		$this->loadFromArray($array);
	}


	/**
	 * @param $json_string
	 */
	public function loadFromJson($json_string) {
		$array = json_decode($json_string, true);
		$this->loadFromArray($array);
	}


	/**
	 * @param $array
	 */
	public function loadFromArray($array) {
		foreach ($array as $k => $v) {
			$this->{$k} = $this->wakeup($k, $v);
		}
		$this->afterObjectLoad();
		$this->setLoaded(true);
	}


	/**
	 * @return boolean
	 */
	public function isLoaded() {
		return $this->loaded;
	}


	/**
	 * @param boolean $loaded
	 */
	public function setLoaded($loaded) {
		$this->loaded = $loaded;
	}


	/**
	 * @param $fieldname
	 * @param $value
	 *
	 * @return mixed
	 */
	protected function sleep($fieldname, $value) {
		return $value;
	}


	/**
	 * @param $fieldname
	 * @param $value
	 *
	 * @return mixed
	 */
	protected function wakeup($fieldname, $value) {
		return $value;
	}


	protected function afterObjectLoad() {
	}
}

?>
