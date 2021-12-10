<?php

use srag\Plugins\Opencast\Model\Object\ObjectSettings;

/**
 * Class xoctIVTGroupParticipantGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @ilCtrl_IsCalledBy xoctIVTGroupParticipantGUI:ilObjOpenCastGUI
 */
class xoctIVTGroupParticipantGUI extends xoctGUI
{

    /**
     * @var array
     */
    protected static $admin_commands = [
        self::CMD_CREATE,
        self::CMD_DELETE
    ];

	/**
	 * @param ObjectSettings $xoctOpenCast
	 */
	public function __construct(ObjectSettings $xoctOpenCast = null)
	{
		if ($xoctOpenCast instanceof ObjectSettings)
		{
			$this->xoctOpenCast = $xoctOpenCast;
		} else
		{
			$this->xoctOpenCast = new ObjectSettings ();
		}
		self::dic()->tabs()->setTabActive(ilObjOpenCastGUI::TAB_GROUPS);
		xoctWaiterGUI::loadLib();
		self::dic()->ui()->mainTemplate()->addJavaScript(self::plugin()->getPluginObject()->getStyleSheetLocation('default/group_participants.js'));
	}

    /**
     * @param $cmd
     */
    protected function performCommand($cmd)
    {
        if (in_array($cmd, self::$admin_commands)) {
            $access = ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_MANAGE_IVT_GROUPS);
        } else {
            $access = ilObjOpenCastAccess::hasPermission('read');
        }
        if (!$access) {
            ilUtil::sendFailure('No access.');
            self::dic()->ctrl()->redirectByClass('xoctEventGUI');
        }
        parent::performCommand($cmd);
    }

	/**
	 * @param $data
	 */
	protected function outJson($data)
	{
		header('Content-type: application/json');
		echo json_encode($data);
		exit;
	}


	protected function index()
	{
	}


	protected function getAvailable()
	{
		$data = array();
		/**
		 * @var $xoctGroupParticipant xoctIVTGroupParticipant
		 */
		foreach (xoctIVTGroupParticipant::getAvailable($_GET['ref_id'], $_GET['group_id']) as $xoctGroupParticipant)
		{
			$stdClass = $xoctGroupParticipant->__asStdClass();
			$stdClass->name = $xoctGroupParticipant->getXoctUser()->getNamePresentation(ilObjOpenCastAccess::hasWriteAccess());
			$data[] = $stdClass;
		}

		usort($data, ['xoctGUI', 'compareStdClassByName']);

		$this->outJson($data);
	}


	protected function getPerGroup()
	{
		$data = array();
		$group_id = $_GET['group_id'];
		if (!$group_id)
		{
			$this->outJson(null);
		}
		/**
		 * @var $xoctGroupParticipant xoctIVTGroupParticipant
		 */
		foreach (xoctIVTGroupParticipant::where(array( 'group_id' => $group_id ))->get() as $xoctGroupParticipant)
		{
			$stdClass = $xoctGroupParticipant->__asStdClass();
			$stdClass->name = $xoctGroupParticipant->getXoctUser()->getNamePresentation();
			$data[] = $stdClass;
		}

		usort($data, ['xoctGUI', 'compareStdClassByName']);

		$this->outJson($data);
	}


	protected function add()
	{
	}


	protected function create()
	{
		if (!$_POST['user_id'] OR !$_POST['group_id'])
		{
			$this->outJson(false);
		}
		$xoctGroupParticipant = new xoctIVTGroupParticipant();
		$xoctGroupParticipant->setUserId($_POST['user_id']);
		$xoctGroupParticipant->setGroupId($_POST['group_id']);
		$xoctGroupParticipant->create();
		$this->outJson(true);
	}


	protected function edit()
	{
	}


	protected function update()
	{
	}


	protected function confirmDelete()
	{
	}


	protected function delete()
	{
		if (!$_POST['id'] || !$_POST['group_id'])
		{
			$this->outJson(false);
		}
		$o = xoctIVTGroupParticipant::where(['user_id' => $_POST['id'], 'group_id' => $_POST['group_id']])->first();
		$o->delete();
		$this->outJson(true);
	}
}