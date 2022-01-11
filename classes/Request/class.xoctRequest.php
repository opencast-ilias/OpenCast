<?php
/**
 * Class xoctRequest
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctRequest {

	const X_RUN_AS_USER = 'X-RUN-AS-USER';
	const X_RUN_WITH_ROLES = 'X-RUN-WITH-ROLES';
    /**
     * @var bool
     */
    private $rest_api;
    /**
	 * @param xoctRequestSettings $xoctRequestSettings
	 */
	public static function init(xoctRequestSettings $xoctRequestSettings) {
		self::$base = $xoctRequestSettings->getApiBase();
	}




    /**
	 * @param array $roles
	 *
	 * @param string $as_user
	 * @param string $base_url
	 * @return string
	 */
	public function get(array $roles = array(), $as_user = '', $base_url = '') {
		$url = $this->getUrl($base_url);

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
     * @param array  $post_data
     * @param array  $roles
     * @param string $as_user
     *
     * @param string $base_url
     *
     * @return string
     */
	public function post(array $post_data, array $roles = array(), string $as_user = '', string $base_url = '') {
		$xoctCurl = new xoctCurl();
		$xoctCurl->setUrl($this->getUrl($base_url));
		$xoctCurl->setPostFields($post_data);
		
		if ($as_user) {
			$xoctCurl->addHeader(self::X_RUN_AS_USER . ': ' . $as_user);
		}

		if (count($roles) > 0) {
			$xoctCurl->addHeader(self::X_RUN_WITH_ROLES . ': ' . implode(',', $roles));
		}

		$xoctCurl->post();

		return $xoctCurl->getResponseBody();
	}


    /**
     * @param array            $post_data
     * @param xoctUploadFile[] $files
     * @param array            $roles
     * @param string           $as_user
     *
     * @param string           $base_url
     *
     * @return string
     */
	public function postFiles(array $post_data, array $files, array $roles = array(), string $as_user = '', string $base_url = '') {
		$xoctCurl = new xoctCurl();
		$xoctCurl->setUrl($this->getUrl($base_url));
		$xoctCurl->setPostFields($post_data);
		$xoctCurl->setRequestContentType('multipart/form-data');

		if ($as_user) {
			$xoctCurl->addHeader(self::X_RUN_AS_USER . ': ' . $as_user);
		}

		if (count($roles) > 0) {
			$xoctCurl->addHeader(self::X_RUN_WITH_ROLES . ': ' . implode(',', $roles));
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
	 * @param array  $post_data
	 * @param array  $roles
	 * @param string $as_user
	 *
	 * @return string
	 */
	public function put(array $post_data, array $roles = array(), $as_user = '') {
		$xoctCurl = new xoctCurl();
		$xoctCurl->setUrl($this->getUrl());
		$xoctCurl->setPostFields($post_data);

		if ($as_user) {
			$xoctCurl->addHeader(self::X_RUN_AS_USER . ': ' . $as_user);
		}

		if (count($roles) > 0) {
			$xoctCurl->addHeader(self::X_RUN_WITH_ROLES . ': ' . implode(',', $roles));
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
	public static function root()
    {
		return new self();
	}

    /**
     * xoctRequest constructor.
     */
    protected function __construct()
    {
        if (!self::$base) {
            xoctConf::setApiSettings();
        }
    }


	const BRANCH_OTHER = - 1;
	const BRANCH_SERIES = 1;
	const BRANCH_EVENTS = 2;
	const BRANCH_BASE = 3;
	const BRANCH_SECURITY = 4;
	const BRANCH_GROUPS = 5;
	const BRANCH_WORKFLOWS = 6;
	const BRANCH_WORKFLOW_DEFINITIONS = 7;
	const BRANCH_SEARCH = 8;
	const BRANCH_INGEST = 9;
	const BRANCH_WORKFLOW = 10;
    const BRANCH_SERVICES = 11;

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
	 * @param string $base
	 * @return string
	 */
	protected function getUrl($base = '') {
		$path = rtrim($base ?: $this->getBase(), '/') . '/';
		$path .= implode('/', $this->parts);
		if ($this->getParameters()) {
			$path .= '?';
			foreach ($this->getParameters() as $k => $v) {
				$path .= $k . '=' . urlencode($v) . '&';
			}
		}

		return rtrim($path, '&');
	}

	//
	// EVENTS
	//

	/**
	 * This method is just temporary and will hopefully be obsolete soon
	 *
	 * @param $identifier
	 * @return self
	 * @throws xoctException
	 */
	public function episodeJson($identifier) {
		$this->checkRoot();
		$this->branch = self::BRANCH_SEARCH;
		$this->addPart('search');
		$this->addPart('episode.json');
		$this->setParameters([
			'id' => $identifier
		]);

		return $this;
	}

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

    /**
     * @param string $series_id
     * @return $this
     * @throws xoctException
     */
    public function series($series_id = '') {
		$this->checkRoot();
		$this->checkBranch(array( self::BRANCH_SERIES ));
		$this->branch = self::BRANCH_SERIES;
		$this->addPart('series');
//        $this->parameter('withacl', true);
		if ($series_id) {
			$this->addPart($series_id);
		}

		return $this;
	}

    /**
     * @param string $group_id
     * @return $this
     * @throws xoctException
     */
    public function groups($group_id = '') {
		$this->checkRoot();
		$this->checkBranch(array( self::BRANCH_GROUPS ));
		$this->branch = self::BRANCH_GROUPS;
		$this->addPart('groups');
		if ($group_id) {
			$this->addPart($group_id);
		}

		return $this;
	}


	/**
	 * @param string $workflow_id
	 *
	 * @return $this
	 * @throws xoctException
	 */
    public function workflows($workflow_id = '') {
        $this->checkRoot();
        $this->checkBranch(array( self::BRANCH_WORKFLOWS ));
        $this->branch = self::BRANCH_WORKFLOWS;
        $this->addPart('workflows');
        if ($workflow_id) {
        	$this->addPart($workflow_id);
        }

        return $this;
	}


	/**
	 * @param string $definition_id
	 *
	 * @return $this
	 * @throws xoctException
	 */
    public function workflowDefinition($definition_id = '') {
        $this->checkRoot();
        $this->checkBranch(array( self::BRANCH_WORKFLOW_DEFINITIONS ));
        $this->branch = self::BRANCH_WORKFLOW_DEFINITIONS;
        $this->addPart('workflow-definitions');
		if ($definition_id) {
			$this->addPart($definition_id);
		}

        return $this;
	}


    /**
     * @return $this
     * @throws xoctException
     */
    public function members() {
		$this->checkBranch(array( self::BRANCH_GROUPS ));
		$this->addPart('members');

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

    /**
     * @return $this
     * @throws xoctException
     */
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
	 * @param      $url
	 *
	 * @param null $valid_until
	 *
	 * @param null $ip
	 *
	 * @return string
	 * @throws xoctException
	 */
	public function sign($url, $valid_until = null, $ip = null) {
		$this->checkBranch(array( self::BRANCH_SECURITY ));
		$this->addPart('sign');
		$data = array( 'url' => $url );

		if ($valid_until) {
			$data['valid-until'] = $valid_until;
		}

		if ($ip) {
			$data['valid-source'] = $ip;
		}

		return $this->post($data);
	}


    /**
	 * @return $this
	 * @throws xoctException
	 */
	public function agents() {
		$this->checkBranch(array( self::BRANCH_BASE ));
		$this->addPart('agents');

		return $this;
	}

    /**
     * @return $this
     * @throws xoctException
     */
    public function scheduling() {
		$this->checkBranch(array( self::BRANCH_EVENTS ));

        if (xoct::isApiVersionGreaterThan('v1.1.0')){
            $this->addPart('scheduling');
        }
        else{
            $this->addPart('scheduling.json');
        }

		return $this;
	}


    /**
     * @return $this
     * @throws xoctException
     */
	public function services() : self
    {
        $this->rest_api = true;
        $this->checkBranch([self::BRANCH_BASE]);
        $this->addPart('services');
        $this->branch = self::BRANCH_SERVICES;

        return $this;
    }


    /**
     * @param string $service_type
     *
     * @return $this
     * @throws xoctException
     */
    public function available(string $service_type) : self
    {
        $this->checkBranch([self::BRANCH_SERVICES]);
        $this->addPart('available.json');
        $this->parameter('serviceType', $service_type);

        return $this;
    }

    /**
     * REST-Endpoint
     *
     * @return $this
     * @throws xoctException
     */
	public function workflow() : self
    {
        $this->rest_api = true;
        $this->checkBranch([self::BRANCH_BASE]);
        $this->addPart('workflow');
        $this->branch = self::BRANCH_WORKFLOW;

        return $this;
    }


    /**
     * Start a workflow
     * https://stable.opencast.org/docs.html?path=/workflow#start-4
     *
     * @return $this
     * @throws xoctException
     */
    public function start() : self
    {
        $this->checkBranch([self::BRANCH_WORKFLOW]);
        $this->addPart('start');

        return $this;
    }

    /**
     * @param string $workflow_definition_id
     *
     * @return $this
     * @throws xoctException
     */
	public function ingest(string $workflow_definition_id = '') : self
    {
        $this->rest_api = true;
        $this->checkBranch([self::BRANCH_BASE, self::BRANCH_INGEST]);
        $this->addPart('ingest');
        if ($workflow_definition_id) {
            $this->addPart($workflow_definition_id);
        }
        $this->branch = self::BRANCH_INGEST;

        return $this;
    }


    /**
     * @return $this
     * @throws xoctException
     */
    public function addDCCatalog() : self
    {
        $this->checkBranch([self::BRANCH_INGEST]);
        $this->addPart('addDCCatalog');

        return $this;
    }


    /**
     * @return $this
     * @throws xoctException
     */
    public function addAttachment() : self
    {
        $this->checkBranch([self::BRANCH_INGEST]);
        $this->addPart('addAttachment');

        return $this;
    }


    /**
     * @return $this
     * @throws xoctException
     */
    public function addTrack() : self
    {
        $this->checkBranch([self::BRANCH_INGEST]);
        $this->addPart('addTrack');

        return $this;
    }

    /**
     * @return $this
     * @throws xoctException
     */
    public function createMediaPackage() : self
    {
        $this->checkBranch([self::BRANCH_INGEST]);
        $this->addPart('createMediaPackage');

        return $this;
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
		return $this->rest_api ? rtrim(self::$base, '/api') : self::$base;
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


    /**
     * @throws xoctException
     */
    protected function checkRoot() {
		if (count($this->parts) > 0 OR $this->branch != self::BRANCH_OTHER) {
			throw new xoctException(xoctException::API_CALL_UNSUPPORTED);
		}
	}
}
