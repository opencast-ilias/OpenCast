<?php
use srag\DIC\OpenCast\DICTrait;
use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Model\Config\PublicationUsage\PublicationUsage;
use srag\Plugins\Opencast\Model\Config\PublicationUsage\PublicationUsageRepository;

/**
 * Class xoctEventTableGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.00
 *
 */
class xoctPublicationUsageTableGUI extends ilTable2GUI {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

	const TBL_ID = 'tbl_xoct_pub_u';
	/**
	 * @var array
	 */
	protected $filter = array();
	/**
	 * @var PublicationUsageRepository
	 */
	protected $repository;


	/**
	 * @param xoctPublicationUsageGUI $a_parent_obj
	 * @param string                  $a_parent_cmd
	 */
	public function  __construct(xoctPublicationUsageGUI $a_parent_obj, $a_parent_cmd) {
		$this->repository = new PublicationUsageRepository();
		$this->setId(self::TBL_ID);
		$this->setPrefix(self::TBL_ID);
		$this->setFormName(self::TBL_ID);
		self::dic()->ctrl()->saveParameter($a_parent_obj, $this->getNavParameter());
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->parent_obj = $a_parent_obj;
		$this->setRowTemplate('tpl.publication_usage.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast');
		$this->setFormAction(self::dic()->ctrl()->getFormAction($a_parent_obj));
		$this->initColumns();
		$this->parseData();
	}


	/**
	 * @param array $a_set
	 *
	 * @throws DICException
	 */
	public function fillRow($a_set) {
		/**
		 * @var $PublicationUsage PublicationUsage
		 */
		$PublicationUsage =  $this->repository->getUsage($a_set['usage_id']);
		$this->tpl->setVariable('USAGE_ID', $PublicationUsage->getUsageId());
		$this->tpl->setVariable('TITLE', $PublicationUsage->getTitle());
		$this->tpl->setVariable('DESCRIPTION', $PublicationUsage->getDescription());
		$this->tpl->setVariable('CHANNEL', $PublicationUsage->getChannel());
		$this->tpl->setVariable('MD_TYPE', $this->parent_obj->txt('md_type_' . $PublicationUsage->getMdType()));
		if ($PublicationUsage->getMdType() === PublicationUsage::MD_TYPE_PUBLICATION_ITSELF) {
			$this->tpl->setVariable('FLAVOR', '&nbsp');
			$this->tpl->setVariable('TAG', '&nbsp');
		} elseif ($PublicationUsage->getSearchKey() == xoctPublicationUsageFormGUI::F_FLAVOR) {
			$this->tpl->setVariable('FLAVOR', $PublicationUsage->getFlavor());
			$this->tpl->setVariable('TAG', '&nbsp');
		} else {
			$this->tpl->setVariable('TAG', $PublicationUsage->getTag());
			$this->tpl->setVariable('FLAVOR', '&nbsp');
		}

		$this->addActionMenu($PublicationUsage);
	}


	protected function initColumns() {
		$this->addColumn($this->parent_obj->txt('usage_id'));
		$this->addColumn($this->parent_obj->txt('title'));
		$this->addColumn($this->parent_obj->txt('description'));
		$this->addColumn($this->parent_obj->txt('channel'));
		$this->addColumn($this->parent_obj->txt('md_type'));
		$this->addColumn($this->parent_obj->txt('flavor'));
		$this->addColumn($this->parent_obj->txt('tag'));
		//		$this->addColumn($this->txt('status'));

		$this->addColumn(self::plugin()->getPluginObject()->txt('common_actions'), '', '150px');
	}


	/**
	 * @param PublicationUsage $xoctPublicationUsage
	 *
	 * @throws DICException
	 */
	protected function addActionMenu(PublicationUsage $xoctPublicationUsage) {
		$current_selection_list = new ilAdvancedSelectionListGUI();
		$current_selection_list->setListTitle(self::plugin()->getPluginObject()->txt('common_actions'));
		$current_selection_list->setId(self::TBL_ID . '_actions_' . $xoctPublicationUsage->getUsageId());
		$current_selection_list->setUseImages(false);

		self::dic()->ctrl()->setParameter($this->parent_obj, xoctPublicationUsageGUI::IDENTIFIER, $xoctPublicationUsage->getUsageId());
		$current_selection_list->addItem($this->parent_obj->txt(xoctPublicationUsageGUI::CMD_EDIT), xoctPublicationUsageGUI::CMD_EDIT, self::dic()->ctrl()->getLinkTarget($this->parent_obj, xoctPublicationUsageGUI::CMD_EDIT));
		$current_selection_list->addItem($this->parent_obj->txt(xoctPublicationUsageGUI::CMD_DELETE), xoctPublicationUsageGUI::CMD_DELETE, self::dic()->ctrl()->getLinkTarget($this->parent_obj, xoctPublicationUsageGUI::CMD_CONFIRM));

		$this->tpl->setVariable('ACTIONS', $current_selection_list->getHTML());
	}


	protected function parseData() {
		$this->setData($this->repository->getArray());
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
