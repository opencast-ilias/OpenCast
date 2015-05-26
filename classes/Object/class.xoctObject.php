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
	 * @param $identifier
	 *
	 * @return xoctObject
	 */
	public static function find($identifier) {
		$class_name = get_called_class();
		if ($existing = xoctCache::getInstance()->get($class_name . '-' . $identifier)) {
			return $existing;
		}

		if (! isset(self::$cache[$class_name][$identifier])) {
			$var = new $class_name($identifier);
			xoctCache::getInstance()->set($class_name . '-' . $identifier, $var);
			self::$cache[$class_name][$identifier] = $var;
		}

		return self::$cache[$class_name][$identifier];
	}


	/**
	 * @param $identifier
	 *
	 * @return xoctObject
	 */
	public static function removeFromCache($identifier) {
		$class_name = get_called_class();
		xoctCache::getInstance()->delete($class_name . '-' . $identifier);
		if (isset(self::$cache[$class_name][$identifier])) {
			unset(self::$cache[$class_name][$identifier]);
		}
	}


	/**
	 * @return array
	 */
	protected function __toArray() {
		$data = $this->__toStdClass();
		$array = (array)$data;

		return $array;
	}


	/**
	 * @return stdClass
	 */
	protected function __toStdClass() {
		$r = new ReflectionClass($this);
		$stdClass = new stdClass();
		foreach ($r->getProperties() as $name) {
			$key = $name->getName();
			if ($key == 'cache') {
				continue;
			}
			if ($this->{$key} instanceof xoctObject) {
				$stdClass->{$key} = $this->{$key}->__toStdClass();
			} elseif (is_array($this->{$key})) {
				$a = array();
				foreach ($this->{$key} as $k => $v) {
					if ($v instanceof xoctObject) {
						$a[$k] = $v->__toStdClass();
					} else {
						$a[$k] = $v;
					}
				}
				$stdClass->{$key} = $a;
			} else {
				$stdClass->{$key} = $this->{$key};
			}
		}

		return $stdClass;
	}


	/**
	 * @return string
	 */
	public function __toJson() {
		return json_encode($this->__toStdClass());
	}


	/**
	 * @param stdClass $class
	 */
	public function loadFromStdClass(stdClass $class) {
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
			$this->{$k} = $v;
		}
		$this->afterObjectLoad();
	}


	protected function afterObjectLoad() {
	}
}

?>
