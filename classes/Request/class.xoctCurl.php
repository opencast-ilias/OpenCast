<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctLog.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctException.php');
require_once('class.xoctCurlSettings.php');
require_once('class.xoctCurlError.php');

/**
 * Class xoctCurl
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctCurl {

	/**
	 * @param xoctCurlSettings $xoctCurlSettings
	 */
	public static function init(xoctCurlSettings $xoctCurlSettings) {
		self::$ip_v4 = $xoctCurlSettings->isIpV4();
		self::$ssl_version = $xoctCurlSettings->getSslVersion();
		self::$verify_host = $xoctCurlSettings->isVerifyHost();
		self::$verify_peer = $xoctCurlSettings->isVerifyHost();
		self::$username = $xoctCurlSettings->getUsername();
		self::$password = $xoctCurlSettings->getPassword();
	}


	/**
	 * @var int
	 */
	protected static $r_no = 1;


	public function get() {
		$this->setRequestType(self::REQ_TYPE_GET);
		$this->execute();
	}


	public function put() {
		$this->setRequestType(self::REQ_TYPE_PUT);
		$this->execute();
	}


	public function post() {
		$this->setRequestType(self::REQ_TYPE_POST);
		$this->execute();
	}


	public function delete() {
		$this->setRequestType(self::REQ_TYPE_DELETE);
		$this->execute();
	}


	protected function execute() {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->getUrl());
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->getRequestType());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if (self::$ip_v4) {
			curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		}

		if (self::$ssl_version) {
			//			curl_setopt($ch, CURLOPT_SSLVERSION, self::$ssl_version);
		}
		if ($this->getUsername() AND $this->getPassword()) {
			curl_setopt($ch, CURLOPT_USERPWD, $this->getUsername() . ':' . $this->getPassword());
		}

		if (! $this->isVerifyHost()) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		}
		if (! $this->isVerifyPeer()) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}

		$this->prepare($ch);

		if ($this->getRequestContentType()) {
			$this->addHeader('Content-Type: ' . $this->getRequestContentType());
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());
		$this->debug($ch);
		$resp_orig = curl_exec($ch);
		xoctLog::getInstance()->write($this->log_output, xoctLog::DEBUG_LEVEL_3);
		if ($resp_orig === false) {
			$this->setResponseError(new xoctCurlError($ch));
			curl_close($ch);
		}
		$this->setResponseBody($resp_orig);
		$this->setResponseMimeType(curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
		$this->setResponseContentSize(curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD));
		$this->setResponseStatus(curl_getinfo($ch, CURLINFO_HTTP_CODE));

		xoctLog::getInstance()->write('Connect-Time: ' . curl_getinfo($ch, CURLINFO_CONNECT_TIME) * 1000 . ' ms', xoctLog::DEBUG_LEVEL_1);

		if ($this->getResponseStatus() > 299) {
			xoctLog::getInstance()->write('ERROR ' . $this->getResponseStatus(), xoctLog::DEBUG_LEVEL_1);
			xoctLog::getInstance()->write('Response:' . $resp_orig, xoctLog::DEBUG_LEVEL_3);
			if ($this->getResponseStatus() == 403) {
				throw new xoctException(xoctException::API_CALL_STATUS_403, $resp_orig);
			} else {
				throw new xoctException(xoctException::API_CALL_STATUS_500, $resp_orig);
			}
		}
		curl_close($ch);
	}


	const REQ_TYPE_GET = 'GET';
	const REQ_TYPE_POST = 'POST';
	const REQ_TYPE_DELETE = 'DELETE';
	const REQ_TYPE_PUT = 'PUT';
	/**
	 * @var array
	 */
	protected $post_fields = array();
	/**
	 * @var int
	 */
	protected static $ssl_version = CURL_SSLVERSION_DEFAULT;
	/**
	 * @var bool
	 */
	protected static $ip_v4 = false;
	/**
	 * @var string
	 */
	protected $url = '';
	/**
	 * @var string
	 */
	protected $request_type = self::REQ_TYPE_GET;
	/**
	 * @var array
	 */
	protected $headers = array();
	/**
	 * @var string
	 */
	protected $response_body = '';
	/**
	 * @var string
	 */
	protected $response_mime_type = '';
	/**
	 * @var string
	 */
	protected $response_content_size = '';
	/**
	 * @var int
	 */
	protected $response_status = 200;
	/**
	 * @var xoctCurlError
	 */
	protected $response_error = NULL;
	/**
	 * @var string
	 */
	protected $put_file_path = '';
	/**
	 * @var string
	 */
	protected $post_body = '';
	/**
	 * @var string
	 */
	protected static $username = '';
	/**
	 * @var string
	 */
	protected static $password = '';
	/**
	 * @var bool
	 */
	protected static $verify_peer = true;
	/**
	 * @var bool
	 */
	protected static $verify_host = true;
	/**
	 * @var string
	 */
	protected $request_content_type = '';
	/**
	 * @var xoctUploadFile[]
	 */
	protected $files = array();


	/**
	 * @param $ch
	 *
	 * @return string
	 */
	public static function getErrorText($ch) {
		$xoctCurlError = new xoctCurlError($ch);

		return $xoctCurlError->getMessage();
	}


	/**
	 * @return int
	 */
	public static function getSslVersion() {
		return self::$ssl_version;
	}


	/**
	 * @param int $ssl_version
	 */
	public static function setSslVersion($ssl_version) {
		self::$ssl_version = $ssl_version;
	}


	/**
	 * @return boolean
	 */
	public static function isIpV4() {
		return self::$ip_v4;
	}


	/**
	 * @param boolean $ip_v4
	 */
	public static function setIpV4($ip_v4) {
		self::$ip_v4 = $ip_v4;
	}


	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}


	/**
	 * @param string $url
	 */
	public function setUrl($url) {
		$this->url = $url;
	}


	/**
	 * @return boolean
	 */
	public function isVerifyHost() {
		return self::$verify_host;
	}


	/**
	 * @param boolean $verify_host
	 */
	public function setVerifyHost($verify_host) {
		self::$verify_host = $verify_host;
	}


	/**
	 * @return boolean
	 */
	public function isVerifyPeer() {
		return self::$verify_peer;
	}


	/**
	 * @param boolean $verify_peer
	 */
	public function setVerifyPeer($verify_peer) {
		self::$verify_peer = $verify_peer;
	}


	/**
	 * @return string
	 */
	public function getRequestType() {
		return $this->request_type;
	}


	/**
	 * @param string $request_type
	 */
	public function setRequestType($request_type) {
		$this->request_type = $request_type;
	}


	/**
	 * @param $string
	 */
	public function addHeader($string) {
		$this->headers[] = $string;
	}


	/**
	 * @return array
	 */
	public function getHeaders() {
		return $this->headers;
	}


	/**
	 * @param array $headers
	 */
	public function setHeaders($headers) {
		$this->headers = $headers;
	}


	/**
	 * @return string
	 */
	public function getResponseBody() {
		return $this->response_body;
	}


	/**
	 * @param string $response_body
	 */
	public function setResponseBody($response_body) {
		$this->response_body = $response_body;
	}


	/**
	 * @return string
	 */
	public function getResponseMimeType() {
		return $this->response_mime_type;
	}


	/**
	 * @param string $response_mime_type
	 */
	public function setResponseMimeType($response_mime_type) {
		$this->response_mime_type = $response_mime_type;
	}


	/**
	 * @return string
	 */
	public function getResponseContentSize() {
		return $this->response_content_size;
	}


	/**
	 * @param string $response_content_size
	 */
	public function setResponseContentSize($response_content_size) {
		$this->response_content_size = $response_content_size;
	}


	/**
	 * @return int
	 */
	public function getResponseStatus() {
		return $this->response_status;
	}


	/**
	 * @param int $response_status
	 */
	public function setResponseStatus($response_status) {
		$this->response_status = $response_status;
	}


	/**
	 * @return xoctCurlError
	 */
	public function getResponseError() {
		return $this->response_error;
	}


	/**
	 * @param xoctCurlError $response_error
	 */
	public function setResponseError($response_error) {
		$this->response_error = $response_error;
	}


	/**
	 * @return string
	 */
	public function getPutFilePath() {
		return $this->put_file_path;
	}


	/**
	 * @param string $put_file_path
	 */
	public function setPutFilePath($put_file_path) {
		$this->put_file_path = $put_file_path;
	}


	/**
	 * @return string
	 */
	public function getPostBody() {
		return $this->post_body;
	}


	/**
	 * @param string $post_body
	 */
	protected function setPostBody($post_body) {
		$this->post_body = $post_body;
	}


	/**
	 * @return array
	 */
	protected function getPostFields() {
		return $this->post_fields;
	}


	/**
	 * @param array $post_fields
	 */
	public function setPostFields($post_fields) {
		$this->post_fields = $post_fields;
	}


	/**
	 * @param $key
	 * @param $value
	 */
	public function addPostField($key, $value) {
		$this->post_fields[$key] = $value;
	}


	/**
	 * @return string
	 */
	public function getUsername() {
		return self::$username;
	}


	/**
	 * @param string $username
	 */
	public function setUsername($username) {
		self::$username = $username;
	}


	/**
	 * @return string
	 */
	public function getPassword() {
		return self::$password;
	}


	/**
	 * @param string $password
	 */
	public function setPassword($password) {
		self::$password = $password;
	}


	/**
	 * @return string
	 */
	public function getRequestContentType() {
		return $this->request_content_type;
	}


	/**
	 * @param string $request_content_type
	 */
	public function setRequestContentType($request_content_type) {
		$this->request_content_type = $request_content_type;
	}


	/**
	 * @return xoctUploadFile[]
	 */
	public function getFiles() {
		return $this->files;
	}


	/**
	 * @param xoctUploadFile[] $files
	 */
	public function setFiles($files) {
		$this->files = $files;
	}


	/**
	 * @param xoctUploadFile $xoctUploadFile
	 */
	public function addFile(xoctUploadFile $xoctUploadFile) {
		$this->files[] = $xoctUploadFile;
	}


	/**
	 * @param $ch
	 *
	 * @throws xoctException
	 */
	protected function preparePut($ch) {
		if ($this->getPostFields()) {
			$this->preparePost($ch);
		}
	}


	/**
	 * @param $ch
	 */
	protected function preparePost($ch) {
		curl_getinfo($ch, CURLINFO_HEADER_OUT);
		if (count($this->getFiles()) > 0) {
			curl_getinfo($ch, CURLOPT_SAFE_UPLOAD, false);
			foreach ($this->getFiles() as $file) {
				$this->addPostField($file->getPostVar(), $file->getCurlString());
			}
		}
		$post_body_string = '';

		foreach ($this->getPostFields() as $key => $value) {
			$post_body_string .= $key . '=' . $value . '&';
		}

		$post_body_string = rtrim($post_body_string, '&');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getPostFields());
		$this->setPostBody($post_body_string);

		xoctLog::getInstance()->write('POST-Body', xoctLog::DEBUG_LEVEL_3);
		xoctLog::getInstance()->write($this->getPostBody(), xoctLog::DEBUG_LEVEL_3);
	}


	/**
	 * @param $ch
	 */
	protected function debug($ch) {
		$xoctLog = xoctLog::getInstance();
		$xoctLog->write('execute *************************************************', xoctLog::DEBUG_LEVEL_1);
		$xoctLog->write($this->getUrl(), xoctLog::DEBUG_LEVEL_1);
		$xoctLog->write($this->getRequestType(), xoctLog::DEBUG_LEVEL_1);
		$backtrace = "Backtrace: \n";
		foreach (debug_backtrace() as $b) {
			$backtrace .= $b['file'] . ': ' . $b["function"] . "\n";
		}
		$xoctLog->write($backtrace, xoctLog::DEBUG_LEVEL_4);
		if (xoctLog::getLogLevel() >= xoctLog::DEBUG_LEVEL_3) {
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			curl_setopt($ch, CURLOPT_STDERR, fopen(xoctLog::getFullPath(), 'a'));
		}
	}


	/**
	 * @param $ch
	 */
	protected function prepare($ch) {
		switch ($this->getRequestType()) {
			case self::REQ_TYPE_PUT:
				$this->preparePut($ch);
				break;
			case self::REQ_TYPE_POST:
				$this->preparePost($ch);
				break;
		}
	}


	/**
	 * @param $ch
	 */
	protected function buildBoundary($ch) {
		//		$disallow = array( "\0", "\"", "\r", "\n" );
		$disallow = array();
		$body = array();
		// build normal parameters
		foreach ($this->getPostFields() as $k => $v) {
			$k = str_replace($disallow, "", $k);
			$body[] = implode("\r\n", array(
				"Content-Disposition: form-data; name=\"{$k}\"",
				"",
				filter_var($v),
			));
		}

		// build file parameters
		foreach ($this->getFiles() as $k => $v) {
			$k = $v->getPostVar();
			$v = $v->getFilePath();

			switch (true) {
				case false === $v = realpath(filter_var($v)):
				case ! is_file($v):
				case ! is_readable($v):
					continue; // or return false, throw new InvalidArgumentException
			}
			$data = file_get_contents($v);
			$v = call_user_func("end", explode(DIRECTORY_SEPARATOR, $v));
			$k = str_replace($disallow, "_", $k);
			$v = str_replace($disallow, "_", $v);
			$body[] = implode("\r\n", array(
				"Content-Disposition: form-data; name=\"{$k}\"; filename=\"{$v}\"",
				"Content-Type: application/octet-stream",
				"",
				$data,
			));
		}

		// generate safe boundary
		do {
			$boundary = "---------------------" . md5(mt_rand() . microtime());
		} while (preg_grep("/{$boundary}/", $body));

		// add boundary for each parameters
		array_walk($body, function (&$part) use ($boundary) {
			$part = "--{$boundary}\r\n{$part}";
		});

		// add final boundary
		$body[] = "--{$boundary}--";
		$body[] = "";

		// set options
		$this->setPostBody(implode("\r\n", $body));
		//		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getPostBody());
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		$this->addHeader('Expect: 100-continue');
		$this->addHeader("Content-Type: multipart/form-data; boundary={$boundary}");
	}
}

