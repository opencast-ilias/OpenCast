<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctWaiterGUI.php');

/**
 * Class xoctFileUploadInputGUI
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctFileUploadInputGUI extends ilSubEnabledFormPropertyGUI {

	/**
	 * @var array
	 */
	protected $suffixes = array();
	/**
	 * @var string
	 */
	protected $url = '';
	/**
	 * @var string
	 */
	protected $chunk_size = '1M';
	/**
	 * @var bool
	 */
	protected $unique_names = true;
	/**
	 * @var string
	 */
	protected $max_file_size = '10000mb';
	/**
	 * @var bool
	 */
	protected $log = false;
	/**
	 * @var string
	 */
	protected $form_id = '';
	/**
	 * @var string
	 */
	protected $cmd = '';
	/**
	 * @var array
	 */
	protected $mime_types = array();


	/**
	 * xoctFileUploadInputGUI constructor.
	 *
	 * @param ilPropertyFormGUI $ilPropertyFormGUI
	 * @param string $a_title
	 * @param $a_postvar
	 */
	public function __construct(ilPropertyFormGUI $ilPropertyFormGUI, $cmd, $a_title, $a_postvar) {
		global $tpl;
		$pl = ilOpenCastPlugin::getInstance();
		xoctWaiterGUI::loadLib();
		$ilPropertyFormGUI->setId($ilPropertyFormGUI->getId() ? $ilPropertyFormGUI->getId() : md5(rand(1, 99)));
		$this->setFormId($ilPropertyFormGUI->getId());
		$this->setCmd($cmd);
		$tpl->addJavaScript('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/js/plupload-2.1.8/js/plupload.full.min.js');

		parent::__construct($a_title, $a_postvar);
	}


	/**
	 * @return string
	 */
	public function render() {
		$pl = ilOpenCastPlugin::getInstance();
		$tpl = new ilTemplate('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/form/tpl.uploader.html', false, true);
		$this->initJS();
		$tpl->setVariable('BUTTON_SELECT', $pl->txt('event_upload_select'));
		$tpl->setVariable('BUTTON_CLEAR', $pl->txt('event_upload_clear'));
		$tpl->setVariable('POSTVAR', $this->getPostVar());
		$tpl->setVariable('FILETYPES', $pl->txt('event_supported_filetypes') . ': ' . implode(', ', $this->getSuffixes()));

		return $tpl->get();
	}


	protected function initJS() {
		global $tpl;
		$tpl->addJavaScript('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/form/uploader.min.js');
		$pl = ilOpenCastPlugin::getInstance();
		$settings = new stdClass();
		$settings->lng = new stdClass();
		$settings->lng->msg_select = $pl->txt('form_msg_select');
		$settings->log = $this->isLog();
		$settings->cmd = $this->getCmd();
		$settings->form_id = $this->getFormId();
		$settings->url = $this->getUrl();
		$settings->runtimes = 'html5,html4';
		$settings->pick_button = 'xoct_pickfiles';
		$settings->chunk_size = '10mb';
		$settings->max_file_size = '10000mb';
		$settings->supported_suffixes = implode(',', $this->getSuffixes());
		$settings->mime_types = implode(',', $this->getMimeTypes());

		$tpl->addOnLoadCode('xoctFileuploaderSettings.initFromJSON(\'' . json_encode($settings) . '\');');
	}


	/**
	 * @return string
	 */
	public function getCmd() {
		return $this->cmd;
	}


	/**
	 * @param string $cmd
	 */
	public function setCmd($cmd) {
		$this->cmd = $cmd;
	}


	/**
	 * @param array $suffixes
	 */
	public function setSuffixes(array $suffixes) {
		$this->suffixes = $suffixes;
	}


	/**
	 * @return array
	 */
	public function getSuffixes() {
		return $this->suffixes;
	}


	public function setValueByArray(array $value) {
	}


	/**
	 * @param ilTemplate $a_tpl
	 */
	public function insert(ilTemplate &$a_tpl) {
		$html = $this->render();

		$a_tpl->setCurrentBlock("prop_generic");
		$a_tpl->setVariable("PROP_GENERIC", $html);
		$a_tpl->parseCurrentBlock();
	}


	/**
	 * Check input, strip slashes etc. set alert, if input is not ok.
	 *
	 * @return    boolean        Input ok, true/false
	 */
	function checkInput() {
		return true;
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
	 * @return string
	 */
	public function getChunkSize() {
		return $this->chunk_size;
	}


	/**
	 * @param string $chunk_size
	 */
	public function setChunkSize($chunk_size) {
		$this->chunk_size = $chunk_size;
	}


	/**
	 * @return boolean
	 */
	public function isUniqueNames() {
		return $this->unique_names;
	}


	/**
	 * @param boolean $unique_names
	 */
	public function setUniqueNames($unique_names) {
		$this->unique_names = $unique_names;
	}


	/**
	 * @return string
	 */
	public function getMaxFileSize() {
		return $this->max_file_size;
	}


	/**
	 * @param string $max_file_size
	 */
	public function setMaxFileSize($max_file_size) {
		$this->max_file_size = $max_file_size;
	}


	/**
	 * @return boolean
	 */
	public function isLog() {
		return $this->log;
	}


	/**
	 * @param boolean $log
	 */
	public function setLog($log) {
		$this->log = $log;
	}


	/**
	 * @return string
	 */
	public function getFormId() {
		return $this->form_id;
	}


	/**
	 * @param string $form_id
	 */
	public function setFormId($form_id) {
		$this->form_id = $form_id;
	}


	/**
	 * @return array
	 */
	public function getMimeTypes() {
		return $this->mime_types;
	}


	/**
	 * @param array $mime_types
	 */
	public function setMimeTypes($mime_types) {
		$this->mime_types = $mime_types;
	}
}

/**
 * Class plupload
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctPlupload {

	/**
	 * @var bool
	 */
	protected $finished = false;
	/**
	 * @var string
	 */
	protected $target_dir = '';
	/**
	 * @var string
	 */
	protected $file_path = '';
	/**
	 * @var bool
	 */
	protected $clean_up = false;


	/**
	 * xoctPlupload constructor.
	 */
	public function __construct() {
		$this->setTargetDir(ilUtil::getDataDir() . "/temp/plupload");
	}


	/**
	 * @return boolean
	 */
	public function isFinished() {
		return $this->finished;
	}


	/**
	 * @param boolean $finished
	 */
	public function setFinished($finished) {
		$this->finished = $finished;
	}


	/**
	 * @return string
	 */
	public function getFilePath() {
		return $this->file_path;
	}


	/**
	 * @param string $file_path
	 */
	public function setFilePath($file_path) {
		$this->file_path = $file_path;
	}


	/**
	 * @return string
	 */
	public function getTargetDir() {
		return $this->target_dir;
	}


	/**
	 * @param string $target_dir
	 */
	public function setTargetDir($target_dir) {
		$this->target_dir = $target_dir;
	}


	/**
	 * @return boolean
	 */
	public function isCleanUp() {
		return $this->clean_up;
	}


	/**
	 * @param boolean $clean_up
	 */
	public function setCleanUp($clean_up) {
		$this->clean_up = $clean_up;
	}


	public function handleUpload() {
		$this->setHeaders();

		// 5 minutes execution time
		@set_time_limit(5 * 60);

		// Settings
		$targetDir = $this->getTargetDir();

		//$targetDir = 'uploads';
		$cleanupTargetDir = $this->isCleanUp(); // Remove old files
		$maxFileAge = 5 * 3600; // Temp file age in seconds

		// Create target dir
		if (!file_exists($targetDir)) {
			if (!mkdir($targetDir, 0777, true)) {
				throw new ilException('Could not create directory');
			}
		}

		// Get a file name
		if (isset($_REQUEST["name"])) {
			$fileName = $_REQUEST["name"];
		} elseif (!empty($_FILES)) {
			$fileName = $_FILES["file"]["name"];
		} else {
			$fileName = uniqid("file_");
		}

		$filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
		global $ilLog;
		$ilLog->write('plupload chunks');
		$ilLog->write($filePath);
		$this->setFilePath($filePath);

		// Chunking might be enabled
		$chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
		$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;

		// Remove old temp files
		if ($cleanupTargetDir) {
			if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
			}

			while (($file = readdir($dir)) !== false) {
				$tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

				// If temp file is current file proceed to the next
				if ($tmpfilePath == "{$filePath}.part") {
					continue;
				}

				// Remove temp file if it is older than the max age and is not the current file
				if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge)) {
					@unlink($tmpfilePath);
				}
			}
			closedir($dir);
		}

		// Open temp file
		if (!$out = @fopen("{$filePath}.part", $chunks ? "ab" : "wb")) {
			die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
		}

		if (!empty($_FILES)) {
			if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
			}

			// Read binary input stream and append it to temp file
			if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
			}
		} else {
			if (!$in = @fopen("php://input", "rb")) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
			}
		}

		while ($buff = fread($in, 4096)) {
			fwrite($out, $buff);
		}

		@fclose($out);
		@fclose($in);

		// Check if file has been uploaded
		if (!$chunks || $chunk == $chunks - 1) {
			// Strip the temp .part suffix off
			rename("{$filePath}.part", $filePath);
		}

		// Return Success JSON-RPC response

		die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
	}


	protected function setHeaders() {
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
	}
}

/**
 * Class xoctPluploadException
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctPluploadException extends xoctException {

	/**
	 * @param string $code
	 * @param string $additional_message
	 */
	public function __construct($code, $additional_message) {
		parent::__construct($code, $additional_message);
	}
}