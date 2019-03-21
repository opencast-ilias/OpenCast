<?php
/**
 * Class xoctIVTGroupGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctIVTGroupGUI: ilObjOpenCastGUI
 */
class xoctIVTGroupGUI extends xoctGUI {

	/**
	 * @param xoctOpenCast $xoctOpenCast
	 */
	public function __construct(xoctOpenCast $xoctOpenCast = NULL) {
		if ($xoctOpenCast instanceof xoctOpenCast) {
			$this->xoctOpenCast = $xoctOpenCast;
		} else {
			$this->xoctOpenCast = new xoctOpenCast ();
		}
		self::dic()->tabs()->setTabActive(ilObjOpenCastGUI::TAB_GROUPS);
		//		xoctGroup::installDB();
		xoctWaiterGUI::loadLib();
		self::dic()->mainTemplate()->addCss(self::plugin()->getPluginObject()->getStyleSheetLocation('default/groups.css'));
		self::dic()->mainTemplate()->addJavaScript(self::plugin()->getPluginObject()->getStyleSheetLocation('default/groups.js'));
	}


	public function executeCommand() {
		if (! ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_MANAGE_IVT_GROUPS) ||
			!(self::dic()->tree()->checkForParentType($_GET['ref_id'], 'crs') || self::dic()->tree()->checkForParentType($_GET['ref_id'], 'grp'))) {
			self::dic()->ctrl()->redirectByClass('xoctEventGUI');
		}
		parent::executeCommand();
	}


	protected function index() {
		$temp = self::plugin()->getPluginObject()->getTemplate('default/tpl.groups.html', false, false);
		$temp->setVariable('HEADER_GROUPS', self::plugin()->translate('groups_header'));
		$temp->setVariable('HEADER_PARTICIPANTS', self::plugin()->translate('groups_participants_header'));
		$temp->setVariable('HEADER_PARTICIPANTS_AVAILABLE', self::plugin()->translate('groups_available_participants_header'));
		$temp->setVariable('L_GROUP_NAME', self::plugin()->translate('groups_new'));
		$temp->setVariable('PH_GROUP_NAME', self::plugin()->translate('groups_new_placeholder'));
		$temp->setVariable('L_FILTER', self::plugin()->translate('groups_participants_filter'));
		$temp->setVariable('PH_FILTER', self::plugin()->translate('groups_participants_filter_placeholder'));
		$temp->setVariable('BUTTON_GROUP_NAME', self::plugin()->translate('groups_new_button'));
		$temp->setVariable('BASE_URL', (self::dic()->ctrl()->getLinkTarget($this, '', '', true)));
		$temp->setVariable('GP_BASE_URL', (self::dic()->ctrl()->getLinkTarget(new xoctIVTGroupParticipantGUI($this->xoctOpenCast), '', '', true)));
		$temp->setVariable('GROUP_LANGUAGE', json_encode(array(
			'no_title' => self::plugin()->translate('group_alert_no_title'),
			'delete_group' => self::plugin()->translate('group_alert_delete_group'),
			'none_available' => self::plugin()->translate('group_none_available')
		)));
		$temp->setVariable('PARTICIPANTS_LANGUAGE', json_encode(array(
			'delete_participant' => self::plugin()->translate('group_delete_participant'),
			'select_group' => self::plugin()->translate('group_select_group'),
			'none_available' => self::plugin()->translate('group_none_available'),
			'none_available_all' => self::plugin()->translate('group_none_available_all'),

		)));

		self::dic()->mainTemplate()->setContent($temp->get());
	}


	/**
	 * @param $data
	 */
	protected function outJson($data) {
		header('Content-type: application/json');
		echo json_encode($data);
		exit;
	}


	protected function add() {
	}


	public function getAll() {
		$arr = array();
		foreach (xoctIVTGroup::getAllForId($this->xoctOpenCast->getObjId()) as $group) {
			$stdClass = $group->__asStdClass();
			$stdClass->user_count = xoctIVTGroupParticipant::where(array( 'group_id' => $group->getId() ))->count();
			$stdClass->name = $stdClass->title;
			$arr[] = $stdClass;
		}
		usort($arr, ['xoctGUI', 'compareStdClassByName']);
		$this->outJson($arr);
	}


	protected function create() {
		$obj = new xoctIVTGroup();
		$obj->setSerieId($this->xoctOpenCast->getObjId());
		$obj->setTitle($_POST['title']);
		$obj->create();
		$this->outJson($obj->__asStdClass());
	}


	protected function edit() {
	}


	protected function update() {
	}


	protected function confirmDelete() {
	}


	protected function delete() {
		/**
		 * @var $xoctIVTGroup xoctIVTGroup
		 */
		$status = false;
		$xoctIVTGroup = xoctIVTGroup::find($_GET['id']);
		if ($xoctIVTGroup->getSerieId() == $this->xoctOpenCast->getObjId()) {
			$xoctIVTGroup->delete();
			$status = true;
		}
		$this->outJson($status);
	}
}