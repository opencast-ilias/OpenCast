<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctLog.php');

/**
 * Class xoctCurl
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctCurl {

	/**
	 * @var int
	 */
	protected static $r_no = 1;
	const DEBUG = 1;


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
//			curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
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
		if (self::DEBUG) {
			$this->debug($ch);
		}

		$this->prepare($ch);

		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());

		$resp_orig = curl_exec($ch);
		//		var_dump($resp_orig); // FSX

		if ($resp_orig === false) {
			$this->setResponseError(new exodCurlError($ch));
			curl_close($ch);
		}
		$this->setResponseBody($resp_orig);
		$this->setResponseMimeType(curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
		$this->setResponseContentSize(curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD));
		$this->setResponseStatus(curl_getinfo($ch, CURLINFO_HTTP_CODE));

		if ($this->getResponseStatus() > 299) {
			xoctLog::getInstance()->write('ERROR 500');
			//			xoctLog::getInstance()->write($resp_orig);
			echo $resp_orig;
			exit;
			//			throw new xoctExeption(xoctExeption::API_CALL_STATUS_500);
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
	 * @var exodCurlError
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
	protected $username = '';
	/**
	 * @var string
	 */
	protected $password = '';
	/**
	 * @var bool
	 */
	protected $verify_peer = true;
	/**
	 * @var bool
	 */
	protected $verify_host = true;


	/**
	 * @param $ch
	 *
	 * @return string
	 */
	public static function getErrorText($ch) {
		$exodCurlError = new exodCurlError($ch);

		return $exodCurlError->getMessage();
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
		return $this->verify_host;
	}


	/**
	 * @param boolean $verify_host
	 */
	public function setVerifyHost($verify_host) {
		$this->verify_host = $verify_host;
	}


	/**
	 * @return boolean
	 */
	public function isVerifyPeer() {
		return $this->verify_peer;
	}


	/**
	 * @param boolean $verify_peer
	 */
	public function setVerifyPeer($verify_peer) {
		$this->verify_peer = $verify_peer;
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
	 * @return exodCurlError
	 */
	public function getResponseError() {
		return $this->response_error;
	}


	/**
	 * @param exodCurlError $response_error
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
		return $this->username;
	}


	/**
	 * @param string $username
	 */
	public function setUsername($username) {
		$this->username = $username;
	}


	/**
	 * @return string
	 */
	public function getPassword() {
		return $this->password;
	}


	/**
	 * @param string $password
	 */
	public function setPassword($password) {
		$this->password = $password;
	}


	/**
	 * @param $ch
	 *
	 * @throws xoctExeption
	 */
	protected function preparePut($ch) {
		//		curl_setopt($ch, CURLOPT_PUT, self::DEBUG);
		if ($this->getPutFilePath()) {
			//			if (! is_readable($this->getPutFilePath())) {
			//
			//				throw new xoctExeption(- 1, 'File not readable');
			//			}
			//			$fh_res = fopen($this->getPutFilePath(), 'r');
			//			curl_setopt($ch, CURLOPT_INFILE, $fh_res);
			//			curl_setopt($ch, CURLOPT_INFILESIZE, filesize($this->getPutFilePath()));
		}
		if ($this->getPostFields()) {
			$this->preparePost($ch);
		}
	}


	/**
	 * @param $ch
	 */
	protected function preparePost($ch) {
		$post_body = array();
		foreach ($this->getPostFields() as $key => $value) {
			$post_body[] = $key . '=' . $value;
		}
		$this->setPostBody(implode('&', $post_body));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getPostBody());
	}


	/**
	 * @param $ch
	 */
	protected function debug($ch) {
		$exodLog = xoctLog::getInstance();
		$exodLog->write('execute *************************************************');
		$exodLog->write($this->getUrl());
		$exodLog->write($this->getRequestType());
		if (self::DEBUG > 1) {
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			$handle = fopen(xoctLog::getFullPath(), 'a');
			curl_setopt($ch, CURLOPT_STDERR, $handle);
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
}

/**
 * Class exodCurlError
 */
class exodCurlError {

	/**
	 * @var array
	 */
	protected static $error_codes = array(
		1 => 'CURLE_UNSUPPORTED_PROTOCOL',
		2 => 'CURLE_FAILED_INIT',
		3 => 'CURLE_URL_MALFORMAT',
		4 => 'CURLE_URL_MALFORMAT_USER',
		5 => 'CURLE_COULDNT_RESOLVE_PROXY',
		6 => 'CURLE_COULDNT_RESOLVE_HOST',
		7 => 'CURLE_COULDNT_CONNECT',
		8 => 'CURLE_FTP_WEIRD_SERVER_REPLY',
		9 => 'CURLE_REMOTE_ACCESS_DENIED',
		11 => 'CURLE_FTP_WEIRD_PASS_REPLY',
		13 => 'CURLE_FTP_WEIRD_PASV_REPLY',
		14 => 'CURLE_FTP_WEIRD_227_FORMAT',
		15 => 'CURLE_FTP_CANT_GET_HOST',
		17 => 'CURLE_FTP_COULDNT_SET_TYPE',
		18 => 'CURLE_PARTIAL_FILE',
		19 => 'CURLE_FTP_COULDNT_RETR_FILE',
		21 => 'CURLE_QUOTE_ERROR',
		22 => 'CURLE_HTTP_RETURNED_ERROR',
		23 => 'CURLE_WRITE_ERROR',
		25 => 'CURLE_UPLOAD_FAILED',
		26 => 'CURLE_READ_ERROR',
		27 => 'CURLE_OUT_OF_MEMORY',
		28 => 'CURLE_OPERATION_TIMEDOUT',
		30 => 'CURLE_FTP_PORT_FAILED',
		31 => 'CURLE_FTP_COULDNT_USE_REST',
		33 => 'CURLE_RANGE_ERROR',
		34 => 'CURLE_HTTP_POST_ERROR',
		35 => 'CURLE_SSL_CONNECT_ERROR',
		36 => 'CURLE_BAD_DOWNLOAD_RESUME',
		37 => 'CURLE_FILE_COULDNT_READ_FILE',
		38 => 'CURLE_LDAP_CANNOT_BIND',
		39 => 'CURLE_LDAP_SEARCH_FAILED',
		41 => 'CURLE_FUNCTION_NOT_FOUND',
		42 => 'CURLE_ABORTED_BY_CALLBACK',
		43 => 'CURLE_BAD_FUNCTION_ARGUMENT',
		45 => 'CURLE_INTERFACE_FAILED',
		47 => 'CURLE_TOO_MANY_REDIRECTS',
		48 => 'CURLE_UNKNOWN_TELNET_OPTION',
		49 => 'CURLE_TELNET_OPTION_SYNTAX',
		51 => 'CURLE_PEER_FAILED_VERIFICATION',
		52 => 'CURLE_GOT_NOTHING',
		53 => 'CURLE_SSL_ENGINE_NOTFOUND',
		54 => 'CURLE_SSL_ENGINE_SETFAILED',
		55 => 'CURLE_SEND_ERROR',
		56 => 'CURLE_RECV_ERROR',
		58 => 'CURLE_SSL_CERTPROBLEM',
		59 => 'CURLE_SSL_CIPHER',
		60 => 'CURLE_SSL_CACERT',
		61 => 'CURLE_BAD_CONTENT_ENCODING',
		62 => 'CURLE_LDAP_INVALID_URL',
		63 => 'CURLE_FILESIZE_EXCEEDED',
		64 => 'CURLE_USE_SSL_FAILED',
		65 => 'CURLE_SEND_FAIL_REWIND',
		66 => 'CURLE_SSL_ENGINE_INITFAILED',
		67 => 'CURLE_LOGIN_DENIED',
		68 => 'CURLE_TFTP_NOTFOUND',
		69 => 'CURLE_TFTP_PERM',
		70 => 'CURLE_REMOTE_DISK_FULL',
		71 => 'CURLE_TFTP_ILLEGAL',
		72 => 'CURLE_TFTP_UNKNOWNID',
		73 => 'CURLE_REMOTE_FILE_EXISTS',
		74 => 'CURLE_TFTP_NOSUCHUSER',
		75 => 'CURLE_CONV_FAILED',
		76 => 'CURLE_CONV_REQD',
		77 => 'CURLE_SSL_CACERT_BADFILE',
		78 => 'CURLE_REMOTE_FILE_NOT_FOUND',
		79 => 'CURLE_SSH',
		80 => 'CURLE_SSL_SHUTDOWN_FAILED',
		81 => 'CURLE_AGAIN',
		82 => 'CURLE_SSL_CRL_BADFILE',
		83 => 'CURLE_SSL_ISSUER_ERROR',
		84 => 'CURLE_FTP_PRET_FAILED',
		84 => 'CURLE_FTP_PRET_FAILED',
		85 => 'CURLE_RTSP_CSEQ_ERROR',
		86 => 'CURLE_RTSP_SESSION_ERROR',
		87 => 'CURLE_FTP_BAD_FILE_LIST',
		88 => 'CURLE_CHUNK_FAILED'
	);


	/**
	 * @param $ch
	 */
	public function __construct($ch) {
		$this->setErrorNr(curl_errno($ch));
		$this->setErrorText(curl_error($ch));
	}


	/**
	 * @return string
	 */
	public function getMessage() {
		return self::$error_codes[$this->getErrorNr()] . ': ' . $this->getErrorText();
	}


	/**
	 * @var int
	 */
	protected $error_nr = 0;
	/**
	 * @var string
	 */
	protected $error_text = '';


	/**
	 * @return int
	 */
	public function getErrorNr() {
		return $this->error_nr;
	}


	/**
	 * @param int $error_nr
	 */
	public function setErrorNr($error_nr) {
		$this->error_nr = $error_nr;
	}


	/**
	 * @return string
	 */
	public function getErrorText() {
		return $this->error_text;
	}


	/**
	 * @param string $error_text
	 */
	public function setErrorText($error_text) {
		$this->error_text = $error_text;
	}
}