<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once('class.xoctPublicationUsage.php');
require_once('./Services/Table/classes/class.ilTable2GUI.php');
require_once('./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php');
require_once('./Services/Form/classes/class.ilMultiSelectInputGUI.php');


/**
 * Class xoctEventTableGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.00
 *
 */
class xoctPublicationUsageTableGUI extends ilTable2GUI {

	const TBL_ID = 'tbl_xoct_pub_u';
	/**
	 * @var ilOpenCastPlugin
	 */
	protected $pl;
	/**
	 * @var array
	 */
	protected $filter = array();


	/**
	 * @param xoctPublicationUsageGUI $a_parent_obj
	 * @param string               $a_parent_cmd
	 */
	public function  __construct(xoctPublicationUsageGUI $a_parent_obj, $a_parent_cmd) {
		/**
		 * @var $ilCtrl ilCtrl
		 */
		global $ilCtrl;
		$this->ctrl = $ilCtrl;
		$this->pl = ilOpenCastPlugin::getInstance();
		$this->setId(self::TBL_ID);
		$this->setPrefix(self::TBL_ID);
		$this->setFormName(self::TBL_ID);
		$this->ctrl->saveParameter($a_parent_obj, $this->getNavParameter());
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->parent_obj = $a_parent_obj;
		$this->setRowTemplate('tpl.system_accounts.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast');
		$this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
		$this->initColums();
		//		$this->initFilters();
		//		$this->setDefaultOrderField('title');
		//		$this->setEnableNumInfo(true);
		//		$this->setExternalSorting(true);
		//		$this->setExternalSegmentation(true);
		$this->parseData();
	}


	/**
	 * @param array $a_set
	 */
	public function fillRow($a_set) {
		/**
		 * @var $xoctPublicationUsage xoctPublicationUsage
		 */
		$xoctPublicationUsage = xoctPublicationUsage::find($a_set['usage']);
//		$this->tpl->setVariable('DOMAIN', $xoctPublicationUsage->getDomain());
//		$this->tpl->setVariable('EXT_ID', $xoctPublicationUsage->getExtId());
//		$this->tpl->setVariable('STATUS', $xoctPublicationUsage->getStatus());

		$this->addActionMenu($xoctPublicationUsage);
	}


	protected function initColums() {
		$this->addColumn($this->parent_obj->txt('domain'));
		$this->addColumn($this->parent_obj->txt('ext_id'));
		//		$this->addColumn($this->txt('status'));

		$this->addColumn($this->pl->txt('common_actions'), '', '150px');
	}





	/**
	 * @param xoctPublicationUsage $xoctPublicationUsage
	 */
	protected function addActionMenu(xoctPublicationUsage $xoctPublicationUsage) {
		$current_selection_list = new ilAdvancedSelectionListGUI();
		$current_selection_list->setListTitle($this->pl->txt('common_actions'));
		$current_selection_list->setId('sys_a_actions_' . $xoctPublicationUsage->getDomain());
		$current_selection_list->setUseImages(false);

		$this->ctrl->setParameter($this->parent_obj, xoctPublicationUsageGUI::IDENTIFIER, $xoctPublicationUsage->getDomain());
		$current_selection_list->addItem($this->parent_obj->txt(xoctPublicationUsageGUI::CMD_EDIT), xoctPublicationUsageGUI::CMD_EDIT, $this->ctrl->getLinkTarget($this->parent_obj, xoctPublicationUsageGUI::CMD_EDIT));
		$current_selection_list->addItem($this->parent_obj->txt(xoctPublicationUsageGUI::CMD_DELETE), xoctPublicationUsageGUI::CMD_DELETE, $this->ctrl->getLinkTarget($this->parent_obj, xoctPublicationUsageGUI::CMD_CONFIRM));

		$this->tpl->setVariable('ACTIONS', $current_selection_list->getHTML());
	}


	protected function parseData() {
		xoctPublicationUsage::installDB();
		$this->setData(xoctPublicationUsage::getArray());
	}


	/**
	 * @param $item
	 */
	protected function addAndReadFilterItem(ilFormPropertyGUI $item) {
		$this->addFilterItem($item);
		$item->readFromSession();
		if ($item instanceof ilCheckboxInputGUI) {
			$this->filter[$item->getPostVar()] = $item->getChecked();
		} else {
			$this->filter[$item->getPostVar()] = $item->getValue();
		}
	}
}

?>
