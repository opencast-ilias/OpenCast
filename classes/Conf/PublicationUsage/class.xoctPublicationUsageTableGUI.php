<?php

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
	 * @param string                  $a_parent_cmd
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
		$this->setRowTemplate('tpl.publication_usage.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast');
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
		$xoctPublicationUsage = xoctPublicationUsage::find($a_set['usage_id']);
		$this->tpl->setVariable('USAGE_ID', $xoctPublicationUsage->getUsageId());
		$this->tpl->setVariable('TITLE', $xoctPublicationUsage->getTitle());
		$this->tpl->setVariable('DESCRIPTION', $xoctPublicationUsage->getDescription());
		$this->tpl->setVariable('CHANNEL', $xoctPublicationUsage->getChannel());
		$this->tpl->setVariable('MD_TYPE', $this->parent_obj->txt('md_type_' . $xoctPublicationUsage->getMdType()));
		$this->tpl->setVariable('FLAVOR', $xoctPublicationUsage->getFlavor());

		$this->addActionMenu($xoctPublicationUsage);
	}


	protected function initColums() {
		$this->addColumn($this->parent_obj->txt('usage_id'));
		$this->addColumn($this->parent_obj->txt('title'));
		$this->addColumn($this->parent_obj->txt('description'));
		$this->addColumn($this->parent_obj->txt('channel'));
		$this->addColumn($this->parent_obj->txt('md_type'));
		$this->addColumn($this->parent_obj->txt('flavor'));
		//		$this->addColumn($this->txt('status'));

		$this->addColumn($this->pl->txt('common_actions'), '', '150px');
	}


	/**
	 * @param xoctPublicationUsage $xoctPublicationUsage
	 */
	protected function addActionMenu(xoctPublicationUsage $xoctPublicationUsage) {
		$current_selection_list = new ilAdvancedSelectionListGUI();
		$current_selection_list->setListTitle($this->pl->txt('common_actions'));
		$current_selection_list->setId(self::TBL_ID . '_actions_' . $xoctPublicationUsage->getUsageId());
		$current_selection_list->setUseImages(false);

		$this->ctrl->setParameter($this->parent_obj, xoctPublicationUsageGUI::IDENTIFIER, $xoctPublicationUsage->getUsageId());
		$current_selection_list->addItem($this->parent_obj->txt(xoctPublicationUsageGUI::CMD_EDIT), xoctPublicationUsageGUI::CMD_EDIT, $this->ctrl->getLinkTarget($this->parent_obj, xoctPublicationUsageGUI::CMD_EDIT));
		$current_selection_list->addItem($this->parent_obj->txt(xoctPublicationUsageGUI::CMD_DELETE), xoctPublicationUsageGUI::CMD_DELETE, $this->ctrl->getLinkTarget($this->parent_obj, xoctPublicationUsageGUI::CMD_CONFIRM));

		$this->tpl->setVariable('ACTIONS', $current_selection_list->getHTML());
	}


	protected function parseData() {
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
