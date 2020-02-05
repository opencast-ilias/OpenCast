<?php

namespace srag\Plugins\Opencast\UI\Input;

use ilException;
use ilOpenCastPlugin;
use ilPropertyFormGUI;
use ilSubEnabledFormPropertyGUI;
use ilTemplate;
use ilTemplateException;
use ilUtil;
use srag\DIC\OpenCast\DICTrait;
use stdClass;
use xoctConf;
use xoctException;
use xoctWaiterGUI;

/**
 * Class xoctFileUploadInputGUI
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class FileUploadInputGUI extends ilSubEnabledFormPropertyGUI {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

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
	protected $chunk_size = '20M';
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
		xoctWaiterGUI::loadLib();
		$ilPropertyFormGUI->setId($ilPropertyFormGUI->getId() ? $ilPropertyFormGUI->getId() : md5(rand(1, 99)));
		$this->setFormId($ilPropertyFormGUI->getId());
		$this->setCmd($cmd);
		self::dic()->mainTemplate()->addJavaScript('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/js/plupload-2.1.8/js/plupload.full.min.js');

		if ($chunk_size = xoctConf::getConfig(xoctConf::F_UPLOAD_CHUNK_SIZE)) {
		    $this->setChunkSize($chunk_size . 'M');
        }
		parent::__construct($a_title, $a_postvar);
	}


    /**
     * @return string
     * @throws ilTemplateException
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


    /**
     *
     */
	protected function initJS() {
		self::dic()->mainTemplate()->addJavaScript('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/form/uploader.min.js');
		$pl = ilOpenCastPlugin::getInstance();
		$settings = new stdClass();
		$settings->lng = new stdClass();
		$settings->lng->msg_select = $pl->txt('form_msg_select');
		$settings->lng->msg_not_supported = $pl->txt('form_msg_not_supported');
		$settings->log = $this->isLog();
		$settings->cmd = $this->getCmd();
		$settings->form_id = $this->getFormId();
		$settings->url = $this->getUrl();
		$settings->runtimes = 'html5,html4';
		$settings->pick_button = 'xoct_pickfiles';
		$settings->chunk_size = $this->getChunkSize();
		$settings->max_file_size = '10000mb';
		$settings->supported_suffixes = implode(',', $this->getSuffixes());
		$settings->supported_suffixes_array = $this->getSuffixes();
		$settings->mime_types = implode(',', $this->getMimeTypes());
		$settings->mime_types_array = $this->getMimeTypes();

		self::dic()->mainTemplate()->addOnLoadCode('xoctFileuploaderSettings.initFromJSON(\'' . json_encode($settings) . '\');');
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
     *
     * @throws ilTemplateException
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
