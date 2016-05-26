<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctWaiterGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Request/class.xoctCurl.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Group/class.xoctUser.php');

/**
 * Class xoctConfFormGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctConfFormGUI extends ilPropertyFormGUI {

	/**
	 * @var  xoctConf
	 */
	protected $object;
	/**
	 * @var xoctConfGUI
	 */
	protected $parent_gui;
	/**
	 * @var  ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilOpenCastPlugin
	 */
	protected $pl;


	/**
	 * @param $parent_gui
	 */
	public function __construct(xoctConfGUI $parent_gui) {
		global $ilCtrl, $lng;
		$this->parent_gui = $parent_gui;
		$this->ctrl = $ilCtrl;
		$this->pl = ilOpenCastPlugin::getInstance();
		$this->lng = $lng;
		$this->initForm();
	}


	protected function initForm() {
		$this->setTarget('_top');
		$this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
		$this->initButtons();

		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->parent_gui->txt('curl'));
		$this->addItem($h);

		$te = new ilTextInputGUI($this->parent_gui->txt(xoctConf::F_API_BASE), xoctConf::F_API_BASE);
		$te->setRequired(true);
		$this->addItem($te);

		$te = new ilTextInputGUI($this->parent_gui->txt(xoctConf::F_CURL_USERNAME), xoctConf::F_CURL_USERNAME);
		$te->setRequired(true);
		$this->addItem($te);

		$te = new ilTextInputGUI($this->parent_gui->txt(xoctConf::F_CURL_PASSWORD), xoctConf::F_CURL_PASSWORD);
		$te->setRequired(true);
		$this->addItem($te);

		$te = new ilSelectInputGUI($this->parent_gui->txt(xoctConf::F_CURL_DEBUG_LEVEL), xoctConf::F_CURL_DEBUG_LEVEL);
		$te->setOptions(array(
			xoctLog::DEBUG_DEACTIVATED => $this->parent_gui->txt('log_level_' . xoctLog::DEBUG_DEACTIVATED),
			xoctLog::DEBUG_LEVEL_1 => $this->parent_gui->txt('log_level_' . xoctLog::DEBUG_LEVEL_1),
			xoctLog::DEBUG_LEVEL_2 => $this->parent_gui->txt('log_level_' . xoctLog::DEBUG_LEVEL_2),
			xoctLog::DEBUG_LEVEL_3 => $this->parent_gui->txt('log_level_' . xoctLog::DEBUG_LEVEL_3),
			xoctLog::DEBUG_LEVEL_4 => $this->parent_gui->txt('log_level_' . xoctLog::DEBUG_LEVEL_4),
		));
		$this->addItem($te);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_ACTIVATE_CACHE), xoctConf::F_ACTIVATE_CACHE);
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_UPLOAD_TOKEN), xoctConf::F_UPLOAD_TOKEN);
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_USE_MODALS), xoctConf::F_USE_MODALS);
		$this->addItem($cb);

		$te = new ilTextInputGUI($this->parent_gui->txt(xoctConf::F_WORKFLOW), xoctConf::F_WORKFLOW);
		$te->setRequired(true);
		$this->addItem($te);

		$te = new ilSelectInputGUI($this->parent_gui->txt(xoctConf::F_USER_MAPPING), xoctConf::F_USER_MAPPING);
		$te->setOptions(array(
			xoctUser::MAP_EXT_ID => 'External-ID',
			xoctUser::MAP_EMAIL => 'E-Mail'
		));
		$this->addItem($te);

		$te = new ilTextAreaInputGUI($this->parent_gui->txt(xoctConf::F_EULA), xoctConf::F_EULA);
		$te->setRequired(true);
		$te->setUseRte(true);
		$te->setRteTags(array(
			'p',
			'a',
			'br'
		));
		$te->usePurifier(true);
		$te->disableButtons(array(
			'charmap',
			'undo',
			'redo',
			'justifyleft',
			'justifycenter',
			'justifyright',
			'justifyfull',
			'anchor',
			'fullscreen',
			'cut',
			'copy',
			'paste',
			'pastetext',
			'formatselect'
		));

		$te->setRows(5);
		$this->addItem($te);

		$te = new ilTextAreaInputGUI($this->parent_gui->txt(xoctConf::F_LICENSES), xoctConf::F_LICENSES);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_LICENSES . '_info'));
		$this->addItem($te);

		$te = new ilTextAreaInputGUI($this->parent_gui->txt(xoctConf::F_LICENSE_INFO), xoctConf::F_LICENSE_INFO);
		$te->setRequired(true);
		$te->setUseRte(true);
		$te->setRteTags(array(
			'p',
			'a',
			'br'
		));
		$te->usePurifier(true);
		$te->disableButtons(array(
			'charmap',
			'undo',
			'redo',
			'justifyleft',
			'justifycenter',
			'justifyright',
			'justifyfull',
			'anchor',
			'fullscreen',
			'cut',
			'copy',
			'paste',
			'pastetext',
			'formatselect'
		));

		$te->setRows(5);
		$this->addItem($te);

		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->parent_gui->txt('roles'));
		$this->addItem($h);

		foreach (xoctConf::$roles as $role) {
			$te = new ilTextInputGUI($this->parent_gui->txt($role), $role);
			$te->setRequired(true);
			$this->addItem($te);
		}
	}


	protected function initButtons() {
		$this->addCommandButton(xoctConfGUI::CMD_UPDATE, $this->parent_gui->txt(xoctConfGUI::CMD_UPDATE));
		$this->addCommandButton(xoctConfGUI::CMD_CANCEL, $this->parent_gui->txt(xoctConfGUI::CMD_CANCEL));
	}


	public function fillForm() {
		$array = array();
		foreach ($this->getItems() as $item) {
			$this->getValuesForItem($item, $array);
		}
		$this->setValuesByArray($array);
	}


	/**
	 * @param $item
	 * @param $array
	 *
	 * @internal param $key
	 */
	private function getValuesForItem($item, &$array) {
		if (self::checkItem($item)) {
			$key = $item->getPostVar();
			$array[$key] = xoctConf::get($key);
			if (self::checkForSubItem($item)) {
				foreach ($item->getSubItems() as $subitem) {
					$this->getValuesForItem($subitem, $array);
				}
			}
		}
	}


	/**
	 * @return bool
	 */
	public function saveObject() {
		if (!$this->checkInput()) {
			return false;
		}
		foreach ($this->getItems() as $item) {
			$this->saveValueForItem($item);
		}
		xoctConf::set(xoctConf::F_CONFIG_VERSION, xoctConf::CONFIG_VERSION);

		return true;
	}


	/**
	 * @param $item
	 */
	private function saveValueForItem($item) {
		if (self::checkItem($item)) {
			$key = $item->getPostVar();
			xoctConf::set($key, $this->getInput($key));
			if (self::checkForSubItem($item)) {
				foreach ($item->getSubItems() as $subitem) {
					$this->saveValueForItem($subitem);
				}
			}
		}
	}


	/**
	 * @param $item
	 *
	 * @return bool
	 */
	public static function checkForSubItem($item) {
		return !$item instanceof ilFormSectionHeaderGUI AND !$item instanceof ilMultiSelectInputGUI;
	}


	/**
	 * @param $item
	 *
	 * @return bool
	 */
	public static function checkItem($item) {
		return !$item instanceof ilFormSectionHeaderGUI;
	}
}

?>
