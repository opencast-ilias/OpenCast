<?php
require_once('class.xoctCurl.php');
require_once('class.xoctRequestSettings.php');

/**
 * Class xoctRequest
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctRequest {

	const X_RUN_AS_USER = 'X-RUN-AS-USER';
	const X_RUN_WITH_ROLES = 'X-RUN-WITH-ROLES';


	/**
	 * @param xoctRequestSettings $xoctRequestSettings
	 */
	public static function init(xoctRequestSettings $xoctRequestSettings) {
		self::$base = $xoctRequestSettings->getApiBase();
	}


	/**
	 * @param string $as_user
	 * @param array $roles
	 *
	 * @return string
	 */
	public function get($as_user = '', array $roles = array()) {
		$url = $this->getUrl();

		$xoctCurl = new xoctCurl();
		$xoctCurl->setUrl($url);
		if ($as_user) {
			$xoctCurl->addHeader(self::X_RUN_AS_USER . ': ' . $as_user);
		}

		if (count($roles) > 0) {
			$xoctCurl->addHeader(self::X_RUN_WITH_ROLES . ': ' . implode(',', $roles));
		}

		$xoctCurl->get();

		$responseBody = $xoctCurl->getResponseBody();

		return $responseBody;
	}


	/**
	 * @param array $post_data
	 * @param string $as_user
	 *
	 * @return string
	 */
	public function post(array $post_data, $as_user = '') {
		$xoctCurl = new xoctCurl();
		$xoctCurl->setUrl($this->getUrl());
		$xoctCurl->setPostFields($post_data);
		if ($as_user) {
			$xoctCurl->addHeader('X-API-AS-USER: ' . $as_user);
		}

		$xoctCurl->post();

		return $xoctCurl->getResponseBody();
	}


	/**
	 * @param array $post_data
	 * @param xoctUploadFile[] $files
	 * @param string $as_user
	 *
	 * @return string
	 */
	public function postFiles(array $post_data, array $files, $as_user = '') {
		$xoctCurl = new xoctCurl();
		$xoctCurl->setUrl($this->getUrl());
		$xoctCurl->setPostFields($post_data);
		$xoctCurl->setRequestContentType('multipart/form-data');
		if ($as_user) {
			$xoctCurl->addHeader('X-API-AS-USER: ' . $as_user);
		}
		foreach ($files as $file) {
			if ($file instanceof xoctUploadFile) {
				$xoctCurl->addFile($file);
			}
		}

		$xoctCurl->post();

		return $xoctCurl->getResponseBody();
	}


	/**
	 * @param array $post_data
	 * @param string $as_user
	 *
	 * @return string
	 */
	public function put(array $post_data, $as_user = '') {
		$xoctCurl = new xoctCurl();
		$xoctCurl->setUrl($this->getUrl());
		$xoctCurl->setPostFields($post_data);
		if ($as_user) {
			$xoctCurl->addHeader('X-API-AS-USER: ' . $as_user);
		}

		$xoctCurl->put();

		return $xoctCurl->getResponseBody();
	}


	/**
	 * @return string
	 */
	public function delete() {
		$xoctCurl = new xoctCurl();
		$xoctCurl->setUrl($this->getUrl());
		$xoctCurl->delete();

		return $xoctCurl->getResponseBody();
	}


	/**
	 * @return xoctRequest
	 */
	public static function root() {
		return new self();
	}


	protected function __construct() {
	}


	const BRANCH_OTHER = - 1;
	const BRANCH_SERIES = 1;
	const BRANCH_EVENTS = 2;
	const BRANCH_BASE = 3;
	const BRANCH_SECURITY = 4;
	/**
	 * @var array
	 */
	protected $parts = array();
	/**
	 * @var int
	 */
	protected $branch = self::BRANCH_OTHER;
	/**
	 * @var string
	 */
	protected static $base = '';
	/**
	 * @var array
	 */
	protected $parameters = array();


	/**
	 * @return string
	 */
	protected function getUrl() {
		$path = rtrim($this->getBase(), '/') . '/';
		$path .= implode('/', $this->parts);
		if ($this->getParameters()) {
			$path .= '?';
			foreach ($this->getParameters() as $k => $v) {
				$path .= $k . '=' . urlencode($v) . '&';
			}
		}

		return $path;
	}

	//
	// EVENTS
	//

	/**
	 * @param string $identifier
	 *
	 * @return $this
	 * @throws xoctException
	 */
	public function events($identifier = '') {
		$this->checkRoot();
		$this->checkBranch(array( self::BRANCH_EVENTS ));
		$this->branch = self::BRANCH_EVENTS;
		$this->addPart('events');
		if ($identifier) {
			$this->addPart($identifier);
		}

		return $this;
	}


	/**
	 * @return $this
	 */
	public function publications($publication_id = '') {
		$this->checkBranch(array( self::BRANCH_EVENTS ));
		$this->addPart('publications');
		if ($publication_id) {
			$this->addPart($publication_id);
		}

		return $this;
	}


	//
	// SERIES
	//

	public function series($series_id = '') {
		$this->checkRoot();
		$this->checkBranch(array( self::BRANCH_SERIES ));
		$this->branch = self::BRANCH_SERIES;
		$this->addPart('series');
		if ($series_id) {
			$this->addPart($series_id);
		}

		return $this;
	}


	/**
	 * @return $this
	 */
	public function properties() {
		$this->checkBranch(array(
			self::BRANCH_SERIES,
			self::BRANCH_EVENTS
		));
		$this->addPart('properties');

		return $this;
	}

	//
	// BOTH
	//

	/**
	 * @return $this
	 */
	public function metadata() {
		$this->checkBranch(array(
			self::BRANCH_SERIES,
			self::BRANCH_EVENTS
		));
		$this->addPart('metadata');

		return $this;
	}


	/**
	 * @return $this
	 */
	public function acl($action = NULL) {
		$this->checkBranch(array(
			self::BRANCH_SERIES,
			self::BRANCH_EVENTS
		));
		$this->addPart('acl');
		if ($action) {
			$this->addPart($action);
		}

		return $this;
	}

	//
	// BASE
	//

	public function base() {
		$this->checkBranch(array( self::BRANCH_BASE ));
		$this->checkRoot();
		$this->branch = self::BRANCH_BASE;

		return $this;
	}


	/**
	 * @return $this
	 */
	public function version() {
		$this->checkBranch(array( self::BRANCH_BASE ));
		$this->addPart('version');

		return $this;
	}


	/**
	 * @return $this
	 */
	public function organization() {
		$this->checkBranch(array( self::BRANCH_BASE ));
		$this->addPart('info');
		$this->addPart('organization');

		return $this;
	}

	//
	//
	//

	//
	// SECURITY
	//
	/**
	 * @return $this
	 * @throws xoctException
	 */
	public function security() {
		$this->checkRoot();
		$this->checkBranch(array( self::BRANCH_SECURITY ));
		$this->branch = self::BRANCH_SECURITY;
		$this->addPart('security');

		return $this;
	}


	/**
	 * @param $url
	 *
	 * @return string
	 * @throws xoctException
	 */
	public function sign($url) {
		$this->checkBranch(array( self::BRANCH_SECURITY ));
		$this->addPart('sign');
		$data = array( 'url' => $url );

		return $this->post($data);
	}


	/**
	 * @param $part
	 */
	protected function addPart($part) {
		$this->parts[] = $part;
	}


	/**
	 * @return array
	 */
	public function getParts() {
		return $this->parts;
	}


	/**
	 * @param array $parts
	 */
	public function setParts($parts) {
		$this->parts = $parts;
	}


	/**
	 * @return string
	 */
	public function getBase() {
		return self::$base;
	}


	/**
	 * @param string $base
	 */
	public function setBase($base) {
		self::$base = $base;
	}


	/**
	 * @return array
	 */
	public function getParameters() {
		return $this->parameters;
	}


	/**
	 * @param array $parameters
	 */
	public function setParameters($parameters) {
		$this->parameters = $parameters;
	}


	/**
	 * @param $key
	 * @param $value
	 *
	 * @return $this
	 */
	public function parameter($key, $value) {
		switch (true) {
			case is_bool($value):
				$value = ($value ? 'true' : 'false');
				break;
		}

		$this->parameters[$key] = $value;
		return $this;
	}


	/**
	 * @return int
	 */
	protected function getBranch() {
		return $this->branch;
	}


	/**
	 * @param int $branch
	 */
	protected function setBranch($branch) {
		$this->branch = $branch;
	}


	/**
	 * @param array $supported_branches
	 *
	 * @throws xoctException
	 */
	protected function checkBranch(array $supported_branches) {
		$supported_branches[] = self::BRANCH_OTHER;
		if (!in_array($this->branch, $supported_branches)) {
			throw new xoctException(xoctException::API_CALL_UNSUPPORTED);
		}
	}


	protected function checkRoot() {
		if (count($this->parts) > 0 OR $this->branch != self::BRANCH_OTHER) {
			throw new xoctException(xoctException::API_CALL_UNSUPPORTED);
		}
	}
}