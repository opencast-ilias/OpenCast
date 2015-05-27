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
		$key = $class_name . '-' . $identifier;
		$existing = xoctCache::getInstance()->get($key);
		if ($existing) {
//			echo '<pre>' . print_r($existing, 1) . '</pre>';
			return $existing;
		}else {
//			echo $existing;
		}

		$var = new $class_name($identifier);
		$failed = xoctCache::getInstance()->set($key, $var);
		if (! $failed) {
			echo $key; // FSX delete
			exit;
		}

		return $var;
	}


	/**
	 * @param $identifier
	 *
	 * @return xoctObject
	 */
	public static function removeFromCache($identifier) {
		$class_name = get_called_class();
		xoctCache::getInstance()->delete($class_name . '-' . $identifier);
//		if (isset(self::$cache[$class_name][$identifier])) {
//			unset(self::$cache[$class_name][$identifier]);
//		}
	}


	/**
	 * @param            $identifier
	 * @param xoctObject $object
	 */
	public static function cache($identifier, xoctObject $object) {
		$class_name = get_class($object);
		xoctCache::getInstance()->set($class_name . '-' . $identifier, $object);
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
