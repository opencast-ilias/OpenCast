<?php
use srag\DIC\OpenCast\DICTrait;
/**
 * Class xoctEventTableGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.00
 *
 */
class xoctSystemAccountTableGUI extends ilTable2GUI {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

	const TBL_ID = 'tbl_xoct_sys_a';
	/**
	 * @var array
	 */
	protected $filter = array();


	/**
	 * @param xoctSystemAccountGUI $a_parent_obj
	 * @param string               $a_parent_cmd
	 */
	public function  __construct(xoctSystemAccountGUI $a_parent_obj, $a_parent_cmd) {
		$this->setId(self::TBL_ID);
		$this->setPrefix(self::TBL_ID);
		$this->setFormName(self::TBL_ID);
		self::dic()->ctrl()->saveParameter($a_parent_obj, $this->getNavParameter());
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->parent_obj = $a_parent_obj;
		$this->setRowTemplate('tpl.system_accounts.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast');
		$this->setFormAction(self::dic()->ctrl()->getFormAction($a_parent_obj));
		$this->initColums();
		$this->parseData();
	}


	/**
	 * @param array $a_set
	 */
	public function fillRow($a_set) {
		/**
		 * @var $xoctSystemAccount xoctSystemAccount
		 */
		$xoctSystemAccount = xoctSystemAccount::find($a_set['domain']);
		$this->tpl->setVariable('DOMAIN', $xoctSystemAccount->getDomain());
		$this->tpl->setVariable('EXT_ID', $xoctSystemAccount->getExtId());
		$this->tpl->setVariable('STATUS', $xoctSystemAccount->getStatus());

		$this->addActionMenu($xoctSystemAccount);
	}


	protected function initColums() {
		$this->addColumn($this->parent_obj->txt('domain'));
		$this->addColumn($this->parent_obj->txt('ext_id'));
		//		$this->addColumn($this->txt('status'));

		$this->addColumn(self::plugin()->translate('common_actions'), '', '150px');
	}





	/**
	 * @param xoctSystemAccount $xoctSystemAccount
	 */
	protected function addActionMenu(xoctSystemAccount $xoctSystemAccount) {
		$current_selection_list = new ilAdvancedSelectionListGUI();
		$current_selection_list->setListTitle(self::plugin()->translate('common_actions'));
		$current_selection_list->setId('sys_a_actions_' . $xoctSystemAccount->getDomain());
		$current_selection_list->setUseImages(false);

		self::dic()->ctrl()->setParameter($this->parent_obj, xoctSystemAccountGUI::IDENTIFIER, $xoctSystemAccount->getDomain());
		$current_selection_list->addItem($this->parent_obj->txt(xoctSystemAccountGUI::CMD_EDIT), xoctSystemAccountGUI::CMD_EDIT, self::dic()->ctrl()->getLinkTarget($this->parent_obj, xoctSystemAccountGUI::CMD_EDIT));
		$current_selection_list->addItem($this->parent_obj->txt(xoctSystemAccountGUI::CMD_DELETE), xoctSystemAccountGUI::CMD_DELETE, self::dic()->ctrl()->getLinkTarget($this->parent_obj, xoctSystemAccountGUI::CMD_CONFIRM));

		$this->tpl->setVariable('ACTIONS', $current_selection_list->getHTML());
	}


	protected function parseData() {
		xoctSystemAccount::installDB();
		$this->setData(xoctSystemAccount::getArray());
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
