<?php

/**
 * Class xoctConf
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctConf extends ActiveRecord {

	const TABLE_NAME = 'xoct_config';
	const CONFIG_VERSION = 1;
	const F_CONFIG_VERSION = 'config_version';
	const F_USE_MODALS = 'use_modals';
    const F_CURL_USERNAME = 'curl_username';
    const F_CURL_PASSWORD = 'curl_password';
    const F_WORKFLOW = 'workflow';
    const F_WORKFLOW_UNPUBLISH = 'workflow_unpublish';
	const F_EULA = 'eula';
	const F_CURL_DEBUG_LEVEL = 'curl_debug_level';
	const F_API_VERSION = 'api_version';
	const F_API_BASE = 'api_base';
	const F_ACTIVATE_CACHE = 'activate_cache';
	const F_USER_MAPPING = 'user_mapping';
	const F_GROUP_PRODUCERS = 'group_producers';
	const F_STD_ROLES = 'std_roles';
	const F_ROLE_USER_PREFIX = 'role_user_prefix';
	const F_ROLE_OWNER_EXTERNAL_PREFIX = 'role_ivt_external_prefix';
	const F_ROLE_OWNER_EMAIL_PREFIX = 'role_ivt_email_prefix';
	const F_IDENTIFIER_TO_UPPERCASE = 'identifier_to_uppercase';
	const F_LICENSE_INFO = 'license_info';
	const F_LICENSES = 'licenses';
	const F_UPLOAD_TOKEN = 'upload_token';
	const F_SIGN_ANNOTATION_LINKS = 'sign_annotation_links';
	const F_REQUEST_COMBINATION_LEVEL = 'request_comb_lv';
	const F_EDITOR_LINK = 'editor_link';
	const SEP_EVERYTHING = 1;
	const SEP_EV_ACL_MD = 2;
	const SEP_EV_ACL_MD_PUB = 3;
	const F_NO_METADATA = 'no_metadata';
	const F_INTERNAL_VIDEO_PLAYER = 'internal_player';
	const F_SIGN_PLAYER_LINKS = 'sign_player_links';
	const F_SIGN_DOWNLOAD_LINKS = 'sign_download_links';
	const F_SIGN_THUMBNAIL_LINKS = 'sign_thumbnail_links';
	const F_WORKFLOW_PARAMETERS = 'workflow_parameters';
	const F_AUDIO_ALLOWED = 'audio_allowed';
	const F_CREATE_SCHEDULED_ALLOWED = 'create_scheduled_allowed';
	const F_VIDEO_PORTAL_LINK = 'video_portal_link';
	const F_VIDEO_PORTAL_TITLE = 'video_portal_title';

	const F_REPORT_QUALITY = 'report_quality';
	const F_REPORT_QUALITY_EMAIL = 'report_quality_email';
	const F_REPORT_QUALITY_TEXT = 'report_quality_text';
	const F_REPORT_QUALITY_ACCESS = 'report_quality_access';
	const ACCESS_ALL = 1;
	const ACCESS_OWNER_ADMIN = 2;
	const F_REPORT_DATE = 'report_date';
	const F_REPORT_DATE_EMAIL = 'report_date_email';
	const F_REPORT_DATE_TEXT = 'report_date_text';
	const F_SCHEDULED_METADATA_EDITABLE = 'scheduled_metadata_editable';
	const NO_METADATA = 0;
	const ALL_METADATA = 1;
	const METADATA_EXCEPT_DATE_PLACE = 2;

	const USE_STREAMING = 'use_streaming';
    const STREAMING_URL = 'streaming_url';
    const F_UPLOAD_CHUNK_SIZE = 'upload_chunk_size';

	/**
	 * @var array
	 */
	public static $roles = array(
		self::F_ROLE_USER_PREFIX,
		self::F_ROLE_OWNER_EXTERNAL_PREFIX,
		self::F_ROLE_OWNER_EMAIL_PREFIX,
	);
	/**
	 * @var array
	 */
	public static $groups = array(
		self::F_GROUP_PRODUCERS,
	);


	/**
	 * @return string
	 * @deprecated
	 */
	static function returnDbTableName() {
		return self::TABLE_NAME;
	}


	/**
	 * @return string
	 */
	public function getConnectorContainerName() {
		return self::TABLE_NAME;
	}


	public static function setApiSettings() {
		// CURL
		$xoctCurlSettings = new xoctCurlSettings();
		$xoctCurlSettings->setUsername(self::getConfig(self::F_CURL_USERNAME));
		$xoctCurlSettings->setPassword(self::getConfig(self::F_CURL_PASSWORD));
		$xoctCurlSettings->setVerifyPeer(true);
		$xoctCurlSettings->setVerifyHost(true);
		xoctCurl::init($xoctCurlSettings);

		//CACHE
		//		xoctCache::setOverrideActive(self::getConfig(self::F_ACTIVATE_CACHE));
		//		xoctCache::setOverrideActive(true);

		// API
		$xoctRequestSettings = new xoctRequestSettings();
		$xoctRequestSettings->setApiBase(self::getConfig(self::F_API_BASE));
		xoctRequest::init($xoctRequestSettings);

		// LOG
		xoctLog::init(self::getConfig(self::F_CURL_DEBUG_LEVEL));

		// USER
		xoctUser::setUserMapping(self::getConfig(self::F_USER_MAPPING) ? self::getConfig(self::F_USER_MAPPING) : xoctUser::MAP_EMAIL);

		// EVENT REQUEST LEVEL
		switch (self::getConfig(self::F_REQUEST_COMBINATION_LEVEL)) {
			default:
			case xoctConf::SEP_EVERYTHING:
				xoctEvent::$LOAD_ACL_SEPARATE = true;
				xoctEvent::$LOAD_PUB_SEPARATE = true;
				xoctEvent::$LOAD_MD_SEPARATE = true;
				break;
			case xoctConf::SEP_EV_ACL_MD:
				xoctEvent::$LOAD_ACL_SEPARATE = false;
				xoctEvent::$LOAD_PUB_SEPARATE = true;
				xoctEvent::$LOAD_MD_SEPARATE = false;
				break;
			case xoctConf::SEP_EV_ACL_MD_PUB:
				xoctEvent::$LOAD_ACL_SEPARATE = false;
				xoctEvent::$LOAD_PUB_SEPARATE = false;
				xoctEvent::$LOAD_MD_SEPARATE = false;
				break;
		}

		// META DATA
		xoctEvent::$NO_METADATA = self::getConfig(self::F_NO_METADATA);
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
		return self::getConfig(self::F_CONFIG_VERSION) == self::CONFIG_VERSION;
	}


	public static function load() {
		$null = parent::get();
	}


	/**
	 * @param $name
	 *
	 * @return mixed
	 */
	public static function getConfig($name) {
		if (!self::$cache_loaded[$name]) {
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