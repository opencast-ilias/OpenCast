<?php
require_once('./Services/ActiveRecord/class.ActiveRecord.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Group/class.xoctUser.php');

/**
 * Class xoctConf
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctConf extends ActiveRecord {

	const CONFIG_VERSION = 1;
	const F_CONFIG_VERSION = 'config_version';
	const F_CURL_USERNAME = 'curl_username';
	const F_CURL_PASSWORD = 'curl_password';
	const F_WORKFLOW = 'workflow';
	const F_CURL_DEBUG_LEVEL = 'curl_debug_level';
	const F_API_BASE = 'api_base';
	const F_ACTIVATE_CACHE = 'activate_cache';
	const F_USER_MAPPING = 'user_mapping';
	const F_ROLE_PRODUCER = 'role_producer';
	const F_ROLE_EXT_APPLICATION = 'role_ext_application';
	const F_ROLE_USER_PREFIX = 'role_user_prefix';
	const F_ROLE_ORGANIZATION_PREFIX = 'role_organisation_prefix';
	const F_ROLE_ANONYMOUS = 'role_anonymous';
	const F_ROLE_FEDERATION_MEMBER = 'role_federation_member';
	const F_ROLE_ROLE_EXTERNAL_APPLICATION_MEMBER = 'role_external_application_member';
	const F_ROLE_USER_IVT_EXTERNAL_PREFIX = 'role_ivt_external_prefix';
	const F_ROLE_USER_IVT_EMAIL_PREFIX = 'role_ivt_email_prefix';
	/**
	 * @var array
	 */
	public static $roles = array(
		self::F_ROLE_PRODUCER,
		self::F_ROLE_EXT_APPLICATION,
		self::F_ROLE_USER_PREFIX,
		self::F_ROLE_ORGANIZATION_PREFIX,
		self::F_ROLE_ANONYMOUS,
		self::F_ROLE_FEDERATION_MEMBER,
		self::F_ROLE_ROLE_EXTERNAL_APPLICATION_MEMBER,
		self::F_ROLE_USER_IVT_EXTERNAL_PREFIX,
		self::F_ROLE_USER_IVT_EMAIL_PREFIX,
	);


	public static function setApiSettings() {
		// CURL
		$xoctCurlSettings = new xoctCurlSettings();
		$xoctCurlSettings->setUsername(self::get(self::F_CURL_USERNAME));
		$xoctCurlSettings->setPassword(self::get(self::F_CURL_PASSWORD));
		$xoctCurlSettings->setVerifyPeer(true);
		$xoctCurlSettings->setVerifyHost(true);
		xoctCurl::init($xoctCurlSettings);

		//CACHE
		xoctCache::setOverrideActive(self::get(self::F_ACTIVATE_CACHE));
//		xoctCache::setOverrideActive(true);

		// API
		$xoctRequestSettings = new xoctRequestSettings();
		$xoctRequestSettings->setApiBase(self::get(self::F_API_BASE));
		xoctRequest::init($xoctRequestSettings);

		// LOG
		xoctLog::init(self::get(self::F_CURL_DEBUG_LEVEL));

		// USER
		xoctUser::setUserMapping(self::get(self::F_USER_MAPPING) ? self::get(self::F_USER_MAPPING) : xoctUser::MAP_EMAIL);
	}


	/**
	 * @return string
	 * @description Return the Name of your Database Table
	 * @deprecated
	 */
	static function returnDbTableName() {
		return 'xoct_config';
	}


	/**
	 * @var array
	 */
	protected static $cache = array();
	/**
	 * @var array
	 */
	protected static $cache_loaded = array();
	/**
	 * @var bool
	 */
	protected $ar_safe_read = false;


	/**
	 * @return bool
	 */
	public static function isConfigUpToDate() {
		return self::get(self::F_CONFIG_VERSION) == self::CONFIG_VERSION;
	}


	public static function load() {
		$null = parent::get();
	}


	/**
	 * @param $name
	 *
	 * @return mixed
	 */
	public static function get($name) {
		if (! self::$cache_loaded[$name]) {
			$obj = new self($name);
			self::$cache[$name] = json_decode($obj->getValue());
			self::$cache_loaded[$name] = true;
		}

		return self::$cache[$name];
	}


	/**
	 * @param $name
	 * @param $value
	 */
	public static function set($name, $value) {
		$obj = new self($name);
		$obj->setValue(json_encode($value));

		if (self::where(array( 'name' => $name ))->hasSets()) {
			$obj->update();
		} else {
			$obj->create();
		}
	}


	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_is_unique        true
	 * @db_is_primary       true
	 * @db_is_notnull       true
	 * @db_fieldtype        text
	 * @db_length           250
	 */
	protected $name;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           4000
	 */
	protected $value;


	/**
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}


	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}


	/**
	 * @param string $value
	 */
	public function setValue($value) {
		$this->value = $value;
	}


	/**
	 * @return string
	 */
	public function getValue() {
		return $this->value;
	}
}