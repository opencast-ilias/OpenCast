<?php
use srag\DIC\OpenCast\DICTrait;

/**
 * Class xoctConfFormGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctConfFormGUI extends ilPropertyFormGUI {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

	/**
	 * @var  xoctConf
	 */
	protected $object;
	/**
	 * @var xoctConfGUI
	 */
	protected $parent_gui;
	/**
	 * @var string
	 */
	protected $subtab_active;


	/**
	 * @param $parent_gui
	 */
	public function __construct(xoctConfGUI $parent_gui, $subtab_active) {
		parent::__construct();
		$this->parent_gui = $parent_gui;
		$this->subtab_active = $subtab_active;
		$this->initForm();
	}


	/**
	 *
	 */
	protected function initForm() {
		$this->setTarget('_top');
		$this->setFormAction(self::dic()->ctrl()->getFormAction($this->parent_gui));
		$this->initButtons();

		switch ($this->subtab_active) {
			case xoctMainGUI::SUBTAB_API:
				$this->initAPISection();
				break;
			case xoctMainGUI::SUBTAB_EVENTS:
				$this->initEventsSection();
				break;
			case xoctMainGUI::SUBTAB_SERIES:
				$this->initSeriesSection();
				break;
			case xoctMainGUI::SUBTAB_GROUPS_ROLES:
				$this->initGroupsRolesSection();
				break;
			case xoctMainGUI::SUBTAB_SECURITY:
				$this->initSecuritySection();
				break;
			case xoctMainGUI::SUBTAB_ADVANCED:
				$this->initAdvancedSection();
				break;
		}

	}


	/**
	 *
	 */
	protected function initButtons() {
		$this->addCommandButton(xoctConfGUI::CMD_UPDATE, $this->parent_gui->txt(xoctConfGUI::CMD_UPDATE));
	}


	/**
	 *
	 */
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


	/**
	 *
	 */
	protected function initAPISection() {
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->parent_gui->txt('curl'));
		$this->addItem($h);

		$te = new ilTextInputGUI($this->parent_gui->txt(xoctConf::F_API_VERSION), xoctConf::F_API_VERSION);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_API_VERSION . '_info'));
		$te->setRequired(true);
		$this->addItem($te);

		$te = new ilTextInputGUI($this->parent_gui->txt(xoctConf::F_API_BASE), xoctConf::F_API_BASE);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_API_BASE . '_info'));
		$te->setRequired(true);
		$this->addItem($te);

		$te = new ilTextInputGUI($this->parent_gui->txt(xoctConf::F_CURL_USERNAME), xoctConf::F_CURL_USERNAME);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_CURL_USERNAME . '_info'));
		$te->setRequired(true);
		$this->addItem($te);

		$te = new ilTextInputGUI($this->parent_gui->txt(xoctConf::F_CURL_PASSWORD), xoctConf::F_CURL_PASSWORD);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_CURL_PASSWORD . '_info'));
		$te->setRequired(true);
		$this->addItem($te);
	}


	/**
	 *
	 */
	protected function initEventsSection() {
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->parent_gui->txt('events'));
		$this->addItem($h);

		$te = new ilTextInputGUI($this->parent_gui->txt(xoctConf::F_WORKFLOW), xoctConf::F_WORKFLOW);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_WORKFLOW . '_info'));
		$te->setRequired(true);
		$this->addItem($te);

		$te = new ilTextInputGUI($this->parent_gui->txt(xoctConf::F_WORKFLOW_UNPUBLISH), xoctConf::F_WORKFLOW_UNPUBLISH);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_WORKFLOW_UNPUBLISH . '_info'));
		$this->addItem($te);

		$te = new ilTextInputGUI($this->parent_gui->txt(xoctConf::F_EDITOR_LINK), xoctConf::F_EDITOR_LINK);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_EDITOR_LINK . '_info'));
		$te->setRequired(true);
		$this->addItem($te);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_CREATE_SCHEDULED_ALLOWED), xoctConf::F_CREATE_SCHEDULED_ALLOWED);
		$cb->setInfo($this->parent_gui->txt(xoctConf::F_CREATE_SCHEDULED_ALLOWED . '_info'));
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_STUDIO_ALLOWED), xoctConf::F_STUDIO_ALLOWED);
		$cb->setInfo($this->parent_gui->txt(xoctConf::F_STUDIO_ALLOWED . '_info'));
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_AUDIO_ALLOWED), xoctConf::F_AUDIO_ALLOWED);
		$cb->setInfo($this->parent_gui->txt(xoctConf::F_AUDIO_ALLOWED . '_info'));
		$this->addItem($cb);

		// INTERNAL VIDEO PLAYER
		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_INTERNAL_VIDEO_PLAYER), xoctConf::F_INTERNAL_VIDEO_PLAYER);
		$cb->setInfo($this->parent_gui->txt(xoctConf::F_INTERNAL_VIDEO_PLAYER . '_info'));
		$this->addItem($cb);

		$cbs = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_USE_STREAMING), xoctConf::F_USE_STREAMING);
		$cbs->setInfo($this->parent_gui->txt(xoctConf::F_USE_STREAMING . '_info'));
		$cbs->setRequired(false);
		$cb->addSubItem($cbs);

		$te = new ilTextInputGUI($this->parent_gui->txt(xoctConf::F_STREAMING_URL), xoctConf::F_STREAMING_URL);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_STREAMING_URL . '_info'));
		$te->setRequired(true);
		$cbs->addSubItem($te);

		$cbs = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_USE_HIGH_LOW_RES_SEGMENT_PREVIEWS), xoctConf::F_USE_HIGH_LOW_RES_SEGMENT_PREVIEWS);
		$cbs->setInfo($this->parent_gui->txt(xoctConf::F_USE_HIGH_LOW_RES_SEGMENT_PREVIEWS . '_info'));
		$cbs->setRequired(false);
		$cb->addSubItem($cbs);

		// LIVE STREAMS
		$cbs = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_ENABLE_LIVE_STREAMS), xoctConf::F_ENABLE_LIVE_STREAMS);
		$cbs->setInfo($this->parent_gui->txt(xoctConf::F_ENABLE_LIVE_STREAMS . '_info'));
		$cbs->setRequired(false);
		$this->addItem($cbs);

		$te = new ilTextInputGUI($this->parent_gui->txt(xoctConf::F_PRESENTATION_NODE), xoctConf::F_PRESENTATION_NODE);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_PRESENTATION_NODE . '_info'));
		$te->setRequired(true);
		$cbs->addSubItem($te);

		$ni = new ilNumberInputGUI($this->parent_gui->txt(xoctConf::F_START_X_MINUTES_BEFORE_LIVE), xoctConf::F_START_X_MINUTES_BEFORE_LIVE);
		$ni->setInfo($this->parent_gui->txt(xoctConf::F_START_X_MINUTES_BEFORE_LIVE . '_info'));
		$cbs->addSubItem($ni);

		$cbs2 = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_ENABLE_CHAT), xoctConf::F_ENABLE_CHAT);
		$cbs2->setInfo($this->parent_gui->txt(xoctConf::F_ENABLE_CHAT . '_info'));
		$cbs2->setRequired(false);
		$cbs->addSubItem($cbs2);

		// MODALS
		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_USE_MODALS), xoctConf::F_USE_MODALS);
		$cb->setInfo($this->parent_gui->txt(xoctConf::F_USE_MODALS . '_info'));
		$this->addItem($cb);


		// QUALITY REPORT
		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_REPORT_QUALITY), xoctConf::F_REPORT_QUALITY);
		$cb->setInfo($this->parent_gui->txt(xoctConf::F_REPORT_QUALITY . '_info'));
		$this->addItem($cb);

		$te = new ilTextInputGUI($this->parent_gui->txt(xoctConf::F_REPORT_QUALITY_EMAIL), xoctConf::F_REPORT_QUALITY_EMAIL);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_REPORT_QUALITY_EMAIL . '_info'));
		$te->setRequired(true);
		$cb->addSubItem($te);

		$te = new ilTextAreaInputGUI($this->parent_gui->txt(xoctConf::F_REPORT_QUALITY_TEXT), xoctConf::F_REPORT_QUALITY_TEXT);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_REPORT_QUALITY_TEXT . '_info'));
		$te->setRequired(true);
		$te->setRows(8);
		$te->setUseRte(1);
		$te->setRteTagSet("extended");
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
		$cb->addSubItem($te);

		$ri = new ilRadioGroupInputGUI($this->parent_gui->txt(xoctConf::F_REPORT_QUALITY_ACCESS), xoctConf::F_REPORT_QUALITY_ACCESS);
		$ro = new ilRadioOption($this->parent_gui->txt(xoctConf::F_REPORT_QUALITY_ACCESS . '_' . xoctConf::ACCESS_ALL), xoctConf::ACCESS_ALL);
		$ri->addOption($ro);
		$ro = new ilRadioOption($this->parent_gui->txt(xoctConf::F_REPORT_QUALITY_ACCESS . '_' . xoctConf::ACCESS_OWNER_ADMIN), xoctConf::ACCESS_OWNER_ADMIN);
		$ri->addOption($ro);
		$ri->setRequired(true);
		$cb->addSubItem($ri);


		// SCHEDULED METADATA EDITABLE
		$ri = new ilRadioGroupInputGUI($this->parent_gui->txt(xoctConf::F_SCHEDULED_METADATA_EDITABLE), xoctConf::F_SCHEDULED_METADATA_EDITABLE);
		$ro = new ilRadioOption($this->parent_gui->txt(xoctConf::F_SCHEDULED_METADATA_EDITABLE . '_' . xoctConf::NO_METADATA), xoctConf::NO_METADATA);
		$ri->addOption($ro);
		$ro = new ilRadioOption($this->parent_gui->txt(xoctConf::F_SCHEDULED_METADATA_EDITABLE . '_' . xoctConf::ALL_METADATA), xoctConf::ALL_METADATA);
		$ro->setInfo($this->parent_gui->txt(xoctConf::F_SCHEDULED_METADATA_EDITABLE . '_' . xoctConf::ALL_METADATA . '_info'));
		$ri->addOption($ro);
		$ro = new ilRadioOption($this->parent_gui->txt(xoctConf::F_SCHEDULED_METADATA_EDITABLE . '_' . xoctConf::METADATA_EXCEPT_DATE_PLACE), xoctConf::METADATA_EXCEPT_DATE_PLACE);
		$ri->addOption($ro);
		$this->addItem($ri);
	}


	/**
	 *
	 */
	protected function initSeriesSection() {
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->parent_gui->txt('series'));
		$this->addItem($h);

		$te = new ilTextAreaInputGUI($this->parent_gui->txt(xoctConf::F_EULA), xoctConf::F_EULA);
		$te->setRequired(true);
		$te->setUseRte(true);
		$te->setRteTagSet("extended");
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

		$te = new ilTextAreaInputGUI($this->parent_gui->txt(xoctConf::F_LICENSE_INFO), xoctConf::F_LICENSE_INFO);
		$te->setRequired(true);
		$te->setUseRte(true);
		$te->setRteTagSet("extended");
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

		// DATE REPORT
		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_REPORT_DATE), xoctConf::F_REPORT_DATE);
		$cb->setInfo($this->parent_gui->txt(xoctConf::F_REPORT_DATE . '_info'));
		$this->addItem($cb);

		$te = new ilTextInputGUI($this->parent_gui->txt(xoctConf::F_REPORT_DATE_EMAIL), xoctConf::F_REPORT_DATE_EMAIL);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_REPORT_DATE_EMAIL . '_info'));
		$te->setRequired(true);
		$cb->addSubItem($te);

		$te = new ilTextAreaInputGUI($this->parent_gui->txt(xoctConf::F_REPORT_DATE_TEXT), xoctConf::F_REPORT_DATE_TEXT);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_REPORT_DATE_TEXT . '_info'));
		$te->setRequired(true);
		$te->setRows(8);
		$te->setUseRte(true);
		$te->setRteTagSet("extended");
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
		$cb->addSubItem($te);
	}


	/**
	 *
	 */
	protected function initGroupsRolesSection() {
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
	}


	/**
	 *
	 */
	protected function initSecuritySection() {
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->parent_gui->txt('security'));
		$h->setInfo($this->parent_gui->txt('security_info'));
		$this->addItem($h);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_SIGN_PLAYER_LINKS), xoctConf::F_SIGN_PLAYER_LINKS);
		$this->addItem($cb);

		$cb_sub = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_SIGN_PLAYER_LINKS_OVERWRITE_DEFAULT), xoctConf::F_SIGN_PLAYER_LINKS_OVERWRITE_DEFAULT);
		$cb->addSubItem($cb_sub);

		$cb_sub_2 = new ilNumberInputGUI($this->parent_gui->txt(xoctConf::F_SIGN_PLAYER_LINKS_ADDITIONAL_TIME_PERCENT), xoctConf::F_SIGN_PLAYER_LINKS_ADDITIONAL_TIME_PERCENT);
		$cb_sub_2->setInfo($this->parent_gui->txt(xoctConf::F_SIGN_PLAYER_LINKS_ADDITIONAL_TIME_PERCENT . '_info'));
		$cb_sub->addSubItem($cb_sub_2);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_SIGN_DOWNLOAD_LINKS), xoctConf::F_SIGN_DOWNLOAD_LINKS);
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_SIGN_THUMBNAIL_LINKS), xoctConf::F_SIGN_THUMBNAIL_LINKS);
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_SIGN_ANNOTATION_LINKS), xoctConf::F_SIGN_ANNOTATION_LINKS);
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_ANNOTATION_TOKEN_SEC), xoctConf::F_ANNOTATION_TOKEN_SEC);
		$cb->setInfo($this->parent_gui->txt(xoctConf::F_ANNOTATION_TOKEN_SEC . '_info'));
		$this->addItem($cb);
	}


	/**
	 *
	 */
	protected function initAdvancedSection() {
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->parent_gui->txt('advanced'));
		$this->addItem($h);

		$te = new ilSelectInputGUI($this->parent_gui->txt(xoctConf::F_USER_MAPPING), xoctConf::F_USER_MAPPING);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_USER_MAPPING . '_info'));
		$te->setOptions(array(
			xoctUser::MAP_EXT_ID => 'External-ID',
			xoctUser::MAP_EMAIL => 'E-Mail',
		));
		$this->addItem($te);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_ACTIVATE_CACHE), xoctConf::F_ACTIVATE_CACHE);
		$cb->setInfo($this->parent_gui->txt(xoctConf::F_ACTIVATE_CACHE . '_info'));
		$this->addItem($cb);

		$te = new ilSelectInputGUI($this->parent_gui->txt(xoctConf::F_CURL_DEBUG_LEVEL), xoctConf::F_CURL_DEBUG_LEVEL);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_CURL_DEBUG_LEVEL . '_info'));
		$te->setOptions(array(
			xoctLog::DEBUG_DEACTIVATED => $this->parent_gui->txt('log_level_' . xoctLog::DEBUG_DEACTIVATED),
			xoctLog::DEBUG_LEVEL_1 => $this->parent_gui->txt('log_level_' . xoctLog::DEBUG_LEVEL_1),
			xoctLog::DEBUG_LEVEL_2 => $this->parent_gui->txt('log_level_' . xoctLog::DEBUG_LEVEL_2),
			xoctLog::DEBUG_LEVEL_3 => $this->parent_gui->txt('log_level_' . xoctLog::DEBUG_LEVEL_3),
			xoctLog::DEBUG_LEVEL_4 => $this->parent_gui->txt('log_level_' . xoctLog::DEBUG_LEVEL_4),
		));
		$this->addItem($te);

		$cb = new ilSelectInputGUI($this->parent_gui->txt(xoctConf::F_REQUEST_COMBINATION_LEVEL), xoctConf::F_REQUEST_COMBINATION_LEVEL);
		$cb->setOptions(array(
			xoctConf::SEP_EVERYTHING => 'Everything separate',
			xoctConf::SEP_EV_ACL_MD => 'Event + ACL + MD',
			xoctConf::SEP_EV_ACL_MD_PUB => 'Event + ACL + MD + PUB',
		));
		$cb->setInfo($this->parent_gui->txt(xoctconf::F_REQUEST_COMBINATION_LEVEL . '_info'));
		$this->addItem($cb);

		$cb = new ilCheckboxInputGUI($this->parent_gui->txt(xoctConf::F_NO_METADATA), xoctConf::F_NO_METADATA);
		$cb->setInfo($this->parent_gui->txt(xoctConf::F_NO_METADATA . '_info'));
		$this->addItem($cb);

		$te = new ilNumberInputGUI($this->parent_gui->txt(xoctConf::F_UPLOAD_CHUNK_SIZE), xoctConf::F_UPLOAD_CHUNK_SIZE);
		$te->setInfo($this->parent_gui->txt(xoctConf::F_UPLOAD_CHUNK_SIZE . '_info'));
		$this->addItem($te);
	}

}

?>
