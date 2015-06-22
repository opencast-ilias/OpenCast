<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctWaiterGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Request/class.xoctCurl.php');

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
		$te->setOptions(array( xoctCurl::DEBUG_DEACTIVATED, xoctCurl::DEBUG_LEVEL_1, xoctCurl::DEBUG_LEVEL_2, xoctCurl::DEBUG_LEVEL_3 ));
		$this->addItem($te);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_ACTIVATE_CACHE), xoctConf::F_ACTIVATE_CACHE);
		$this->addItem($cb);
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
		if (! $this->checkInput()) {
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
		return ! $item instanceof ilFormSectionHeaderGUI AND ! $item instanceof ilMultiSelectInputGUI;
	}


	/**
	 * @param $item
	 *
	 * @return bool
	 */
	public static function checkItem($item) {
		return ! $item instanceof ilFormSectionHeaderGUI;
	}
}

?>
