<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctWaiterGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Request/class.xoctCurl.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/IVTGroup/class.xoctUser.php');

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
		$te->setInfo($this->parent_gui->txt(xoctConf::F_API_BASE. '_info'));
		$te->setRequired(true);
		$this->addItem($te);

		$te = new ilTextInputGUI($this->parent_gui->txt(xoctConf::F_CURL_USERNAME), xoctConf::F_CURL_USERNAME);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_CURL_USERNAME. '_info'));
		$te->setRequired(true);
		$this->addItem($te);

		$te = new ilTextInputGUI($this->parent_gui->txt(xoctConf::F_CURL_PASSWORD), xoctConf::F_CURL_PASSWORD);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_CURL_PASSWORD. '_info'));
		$te->setRequired(true);
		$this->addItem($te);

		$te = new ilTextInputGUI($this->parent_gui->txt(xoctConf::F_EDITOR_LINK), xoctConf::F_EDITOR_LINK);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_EDITOR_LINK. '_info'));
		$te->setRequired(true);
		$this->addItem($te);

		$te = new ilSelectInputGUI($this->parent_gui->txt(xoctConf::F_CURL_DEBUG_LEVEL), xoctConf::F_CURL_DEBUG_LEVEL);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_CURL_DEBUG_LEVEL. '_info'));
		$te->setOptions(array(
			xoctLog::DEBUG_DEACTIVATED => $this->parent_gui->txt('log_level_' . xoctLog::DEBUG_DEACTIVATED),
			xoctLog::DEBUG_LEVEL_1     => $this->parent_gui->txt('log_level_' . xoctLog::DEBUG_LEVEL_1),
			xoctLog::DEBUG_LEVEL_2     => $this->parent_gui->txt('log_level_' . xoctLog::DEBUG_LEVEL_2),
			xoctLog::DEBUG_LEVEL_3     => $this->parent_gui->txt('log_level_' . xoctLog::DEBUG_LEVEL_3),
			xoctLog::DEBUG_LEVEL_4     => $this->parent_gui->txt('log_level_' . xoctLog::DEBUG_LEVEL_4),
		));
		$this->addItem($te);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_ACTIVATE_CACHE), xoctConf::F_ACTIVATE_CACHE);
		$cb->setInfo($this->parent_gui->txt(xoctConf::F_ACTIVATE_CACHE. '_info'));
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_INTERNAL_VIDEO_PLAYER), xoctConf::F_INTERNAL_VIDEO_PLAYER);
		$cb->setInfo($this->parent_gui->txt(xoctConf::F_INTERNAL_VIDEO_PLAYER . '_info'));
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_USE_MODALS), xoctConf::F_USE_MODALS);
		$cb->setInfo($this->parent_gui->txt(xoctConf::F_USE_MODALS. '_info'));
		$this->addItem($cb);

		$te = new ilTextInputGUI($this->parent_gui->txt(xoctConf::F_WORKFLOW), xoctConf::F_WORKFLOW);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_WORKFLOW. '_info'));
		$te->setRequired(true);
		$this->addItem($te);

		$te = new ilSelectInputGUI($this->parent_gui->txt(xoctConf::F_USER_MAPPING), xoctConf::F_USER_MAPPING);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_USER_MAPPING. '_info'));
		$te->setOptions(array(
			xoctUser::MAP_EXT_ID => 'External-ID',
			xoctUser::MAP_EMAIL  => 'E-Mail',
		));
		$this->addItem($te);

		$te = new ilTextAreaInputGUI($this->parent_gui->txt(xoctConf::F_EULA), xoctConf::F_EULA);
		$te->setRequired(true);
		$te->setUseRte(true);
		$te->setRteTags(array(
			'p',
			'a',
			'br',
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
			'formatselect',
		));

		$te->setRows(5);
		$this->addItem($te);

		$te = new ilTextAreaInputGUI($this->parent_gui->txt(xoctConf::F_LICENSES), xoctConf::F_LICENSES);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_LICENSES . '_info'));
		$this->addItem($te);

		$te = new ilTextAreaInputGUI($this->parent_gui->txt(xoctConf::F_LICENSE_INFO), xoctConf::F_LICENSE_INFO);
		$te->setRequired(true);
//		$te->setUseRte(true);
////		$te->setRteTags(array(
////			'p',
////			'a',
////			'br',
////		));
//		$te->usePurifier(true);
//		$te->disableButtons(array(
//			'charmap',
//			'undo',
//			'redo',
//			'justifyleft',
//			'justifycenter',
//			'justifyright',
//			'justifyfull',
//			'anchor',
//			'fullscreen',
//			'cut',
//			'copy',
//			'paste',
//			'pastetext',
//			'formatselect',
//		));

		$te->setRows(5);
		$this->addItem($te);

		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->parent_gui->txt('groups'));
		$this->addItem($h);

		// groups
		foreach (xoctConf::$groups as $group) {
			$te = new ilTextInputGUI($this->parent_gui->txt($group), $group);
			$te->setInfo($this->parent_gui->txt($group . '_info'));
			$te->setRequired(true);
			$this->addItem($te);
		}

		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->parent_gui->txt('roles'));
		$this->addItem($h);

		// standard roles
		$te = new ilTextInputGUI($this->parent_gui->txt('std_roles'), xoctConf::F_STD_ROLES);
		$te->setInfo($this->parent_gui->txt('std_roles_info'));
		$te->setMulti(true);
		$te->setInlineStyle('min-width:250px');
		$this->addItem($te);

		// other roles
		foreach (xoctConf::$roles as $role) {
			$te = new ilTextInputGUI($this->parent_gui->txt($role), $role);
			$te->setInfo($this->parent_gui->txt($role . '_info'));
			$te->setRequired(true);
			$this->addItem($te);
		}

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_IDENTIFIER_TO_UPPERCASE), xoctConf::F_IDENTIFIER_TO_UPPERCASE);
		$this->addItem($cb);


		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->parent_gui->txt('security'));
		$h->setInfo($this->parent_gui->txt('security_info'));
		$this->addItem($h);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_SIGN_PLAYER_LINKS), xoctConf::F_SIGN_PLAYER_LINKS);
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_SIGN_DOWNLOAD_LINKS), xoctConf::F_SIGN_DOWNLOAD_LINKS);
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_SIGN_THUMBNAIL_LINKS), xoctConf::F_SIGN_THUMBNAIL_LINKS);
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_SIGN_ANNOTATION_LINKS), xoctConf::F_SIGN_ANNOTATION_LINKS);
		$this->addItem($cb);

		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->parent_gui->txt('advanced'));
		$this->addItem($h);

		$cb = new ilSelectInputGUI($this->parent_gui->txt(xoctConf::F_REQUEST_COMBINATION_LEVEL), xoctConf::F_REQUEST_COMBINATION_LEVEL);
		$cb->setOptions(array( xoctConf::SEP_EVERYTHING    => 'Everything separate',
		                       xoctConf::SEP_EV_ACL_MD     => 'Event + ACL + MD',
		                       xoctConf::SEP_EV_ACL_MD_PUB => 'Event + ACL + MD + PUB',
		));
		$cb->setInfo($this->parent_gui->txt(xoctconf::F_REQUEST_COMBINATION_LEVEL . '_info'));
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_NO_METADATA), xoctConf::F_NO_METADATA);
		$cb->setInfo($this->parent_gui->txt(xoctConf::F_NO_METADATA . '_info'));
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
			$array[$key] = xoctConf::getConfig($key);
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
