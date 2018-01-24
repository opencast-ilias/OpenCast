<?php
/**
 * Class xoctCurlSettings
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctCurlSettings {

	/**
	 * @var bool
	 */
	protected $ip_v4 = false;
	/**
	 * @var int
	 */
	protected $ssl_version = CURL_SSLVERSION_DEFAULT;
	/**
	 * @var bool
	 */
	protected $verify_host = false;
	/**
	 * @var bool
	 */
	protected $verify_peer = false;
	/**
	 * @var string
	 */
	protected $username = NULL;
	/**
	 * @var string
	 */
	protected $password = NULL;


	/**
	 * @return boolean
	 */
	public function isIpV4() {
		return $this->ip_v4;
	}


	/**
	 * @param boolean $ip_v4
	 */
	public function setIpV4($ip_v4) {
		$this->ip_v4 = $ip_v4;
	}


	/**
	 * @return int
	 */
	public function getSslVersion() {
		return $this->ssl_version;
	}


	/**
	 * @param int $ssl_version
	 */
	public function setSslVersion($ssl_version) {
		$this->ssl_version = $ssl_version;
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
}

?>
