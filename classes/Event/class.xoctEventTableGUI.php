<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once('class.xoctEvent.php');
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
class xoctEventTableGUI extends ilTable2GUI {

	const TBL_ID = 'tbl_xoct_events';
	/**
	 * @var ilOpenCastPlugin
	 */
	protected $pl;
	/**
	 * @var array
	 */
	protected $filter = array();


	/**
	 * @param xoctEventGUI $a_parent_obj
	 * @param string       $a_parent_cmd
	 */
	public function  __construct(xoctEventGUI $a_parent_obj, $a_parent_cmd, xoctOpenCast $xoctOpenCast) {
		/**
		 * @var $ilCtrl ilCtrl
		 */
		global $ilCtrl;
		$this->ctrl = $ilCtrl;
		$this->pl = ilOpenCastPlugin::getInstance();
		$this->xoctOpenCast = $xoctOpenCast;
		$this->setId(self::TBL_ID);
		$this->setPrefix(self::TBL_ID);
		$this->setFormName(self::TBL_ID);
		$this->ctrl->saveParameter($a_parent_obj, $this->getNavParameter());
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->parent_obj = $a_parent_obj;
		$this->setRowTemplate('tpl.events.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast');
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
		 * @var $xoctEvent xoctEvent
		 */
		$xoctEvent = xoctEvent::find($a_set['identifier']);
		//		echo '<pre>' . print_r($xoctEvent, 1) . '</pre>';
		$this->tpl->setVariable('TITLE', $xoctEvent->getTitle());
		$this->tpl->setVariable('PRESENTER', implode(', ', $xoctEvent->getPresenters()));
		$this->tpl->setVariable('LOCATION', $xoctEvent->getLocation());
		$this->tpl->setVariable('RECORDING_STATION', $xoctEvent->getMetadata()->getField('recording_station')->getValue());
		$this->tpl->setVariable('DATE', $xoctEvent->getCreated()->format(DATE_ISO8601));
		//		$this->tpl->setVariable('VAL_CREATE_DATE', $a_set['create_date']);
		//		$this->tpl->setVariable('VAL_LAST_UPDATE', $a_set['last_change']);
		//		$this->tpl->setVariable('VAL_REQUESTER_EMAIL', $a_set['usr_data_email']);
		//		$this->tpl->setVariable('VAL_STATUS', $this->pl->txt('request_status_' . $a_set['status']));
		//		$this->tpl->setVariable('VAL_LIBRARY', $a_set['xdgl_library_title']);
		//		$this->tpl->setVariable('VAL_LIBRARIAN', $a_set['usr_data_2_email']);
		//
		$this->addActionMenu($xoctEvent);
	}


	protected function initColums() {
		$this->addColumn($this->pl->txt('event_preview'));
		$this->addColumn($this->pl->txt('event_clips'));
		$this->addColumn($this->pl->txt('event_title'), 'title');
		$this->addColumn($this->pl->txt('event_presenter'), 'presenter');
		$this->addColumn($this->pl->txt('event_location'), 'location');
		$this->addColumn($this->pl->txt('event_recording_station'), 'recording_station');
		$this->addColumn($this->pl->txt('event_date'), 'date');
		$this->addColumn($this->pl->txt('event_owner'), 'owner');

		$this->addColumn($this->pl->txt('common_actions'));
	}


	/**
	 * @param xoctEvent $xoctEvent
	 */
	protected function addActionMenu(xoctEvent $xoctEvent) {

		$current_selection_list = new ilAdvancedSelectionListGUI();
		$current_selection_list->setListTitle($this->pl->txt('common_actions'));
		$current_selection_list->setId('event_actions_' . $xoctEvent->getIdentifier());
		$current_selection_list->setUseImages(false);

		$this->ctrl->setParameter($this->parent_obj, xoctEventGUI::IDENTIFIER, $xoctEvent->getIdentifier());
		$current_selection_list->addItem($this->pl->txt('event_view'), 'event_view', $this->ctrl->getLinkTarget($this->parent_obj, xoctEventGUI::CMD_VIEW));

		$this->tpl->setVariable('ACTIONS', $current_selection_list->getHTML());
		//
		//		switch ($a_set['status']) {
		//			case xdglRequest::STATUS_NEW:
		//			case xdglRequest::STATUS_IN_PROGRRESS:
		//				$current_selection_list->addItem($this->pl->txt('request_view'), 'view_request', $this->ctrl->getLinkTarget($this->parent_obj, xdglRequestGUI::CMD_VIEW));
		//				$current_selection_list->addItem($this->pl->txt('request_edit'), 'edit_request', $this->ctrl->getLinkTarget($this->parent_obj, xdglRequestGUI::CMD_EDIT));
		//				$current_selection_list->addItem($this->pl->txt('upload_title'), 'upload_pdf', $this->ctrl->getLinkTarget($this->parent_obj, xdglRequestGUI::CMD_SELECT_FILE));
		//				$current_selection_list->addItem($this->pl->txt('request_change_status_to_wip'), 'change_status_to_wip', $this->ctrl->getLinkTarget($this->parent_obj, xdglRequestGUI::CMD_CHANGE_STATUS_TO_WIP));
		//				$current_selection_list->addItem($this->pl->txt('request_refuse'), 'refuse_request', $this->ctrl->getLinkTarget($this->parent_obj, xdglRequestGUI::CDM_CONFIRM_REFUSE));
		//				$current_selection_list->addItem($this->pl->txt('request_assign'), 'assign_request', $this->ctrl->getLinkTargetByClass('xdglLibraryGUI', xdglLibraryGUI::CMD_ASSIGN_LIBRARY));
		//				break;
		//			//			case xdglRequest::STATUS_IN_PROGRRESS:
		//			//				$current_selection_list->addItem($this->pl->txt('request_view'), 'view_request', $this->ctrl->getLinkTarget($this->parent_obj, xdglRequestGUI::CMD_VIEW));
		//			//				$current_selection_list->addItem($this->pl->txt('request_edit'), 'edit_request', $this->ctrl->getLinkTarget($this->parent_obj, xdglRequestGUI::CMD_EDIT));
		//			//				$current_selection_list->addItem($this->pl->txt('upload_title'), 'upload_pdf', $this->ctrl->getLinkTarget($this->parent_obj, xdglRequestGUI::CMD_SELECT_FILE));
		//			//				$current_selection_list->addItem($this->pl->txt('request_refuse'), 'refuse_request', $this->ctrl->getLinkTarget($this->parent_obj, xdglRequestGUI::CDM_CONFIRM_REFUSE));
		//			//				break;
		//			case xdglRequest::STATUS_RELEASED:
		//				$current_selection_list->addItem($this->pl->txt('request_view'), 'view_request', $this->ctrl->getLinkTarget($this->parent_obj, xdglRequestGUI::CMD_VIEW));
		//				$current_selection_list->addItem($this->pl->txt('request_edit'), 'edit_request', $this->ctrl->getLinkTarget($this->parent_obj, xdglRequestGUI::CMD_EDIT));
		//				$current_selection_list->addItem($this->pl->txt('request_download_file'), 'request_download_file', $this->ctrl->getLinkTarget($this->parent_obj, xdglRequestGUI::CMD_DOWNLOAD_FILE));
		//				$current_selection_list->addItem($this->pl->txt('request_replace_file'), 'request_replace_file', $this->ctrl->getLinkTarget($this->parent_obj, xdglRequestGUI::CMD_REPLACE_FILE));
		//				$current_selection_list->addItem($this->pl->txt('request_delete_file'), 'request_delete_file', $this->ctrl->getLinkTarget($this->parent_obj, xdglRequestGUI::CMD_DELETE_FILE));
		//				break;
		//			case xdglRequest::STATUS_REFUSED:
		//			case xdglRequest::STATUS_COPY:
		//				break;
		//		}
		//
		//		$this->tpl->setVariable('VAL_ACTION', $current_selection_list->getHTML());
	}


	protected function initFilters() {
		//		// Status
		//		$te = new ilMultiSelectInputGUI($this->pl->txt('filter_status'), 'status');
		//		$te->setOptions(array(
		//			xdglRequest::STATUS_NEW => $this->pl->txt('request_status_' . xdglRequest::STATUS_NEW),
		//			xdglRequest::STATUS_IN_PROGRRESS => $this->pl->txt('request_status_' . xdglRequest::STATUS_IN_PROGRRESS),
		//			xdglRequest::STATUS_REFUSED => $this->pl->txt('request_status_' . xdglRequest::STATUS_REFUSED),
		//			xdglRequest::STATUS_RELEASED => $this->pl->txt('request_status_' . xdglRequest::STATUS_RELEASED),
		//			xdglRequest::STATUS_RELEASED => $this->pl->txt('request_status_' . xdglRequest::STATUS_RELEASED),
		//			xdglRequest::STATUS_COPY => $this->pl->txt('request_status_' . xdglRequest::STATUS_COPY),
		//		));
		//		$this->addAndReadFilterItem($te);
		//
		//		// Library
		//		if (ilObjDigiLitAccess::showAllLibraries()) {
		//			$te = new ilMultiSelectInputGUI($this->pl->txt('filter_library'), 'xdgl_library_id');
		//			$te->setOptions(xdglLibrary::where(array( 'active' => true ))->getArray('id', 'title'));
		//			$this->addAndReadFilterItem($te);
		//		}
		//		global $ilUser;
		//		$te = new ilMultiSelectInputGUI($this->pl->txt('filter_librarian'), 'xdgl_librarian_id');
		//		xdglLibrary::getLibraryIdsForUser($ilUser);
		//		$lib_id = ilObjDigiLitAccess::showAllLibraries() ? NULL : xdglLibrary::getLibraryIdsForUser($ilUser);
		//		$libs = xdglLibrarian::getAssignedLibrariansForLibrary($lib_id, $ilUser->getId(), ilObjDigiLitAccess::showAllLibraries());
		//		$libs[xdglRequest::LIBRARIAN_ID_NONE] = $this->pl->txt('filter_none');
		//		$libs[xdglRequest::LIBRARIAN_ID_MINE] = $this->pl->txt('filter_mine');
		//		ksort($libs);
		//		$te->setOptions($libs);
		//		$this->addAndReadFilterItem($te);
		//
		//		// Ext_ID
		//		$te = new ilTextInputGUI($this->pl->txt('request_ext_id'), 'ext_id');
		//		$this->addAndReadFilterItem($te);
	}


	/**
	 * @param                  $usr_id
	 * @param ActiveRecordList $xdglRequestList
	 *
	 * @throws Exception
	 */
	protected function filterResults($usr_id, ActiveRecordList $xdglRequestList) {
		//		foreach ($this->filter as $field => $value) {
		//			if ($value) {
		//				switch ($field) {
		//					case 'xdgl_library_id':
		//						$field = 'xdgl_library.id';
		//						$xdglRequestList->where(array( $field => $value ));
		//						break;
		//					case 'ext_id':
		//						if (class_exists('arHaving')) {
		//							$h = new arHaving();
		//							$h->setFieldname('ext_id');
		//							$h->setValue('%' . $value . '%');
		//							$h->setOperator('LIKE');
		//							$xdglRequestList->getArHavingCollection()->add($h);
		//						}
		//						break;
		//					case 'xdgl_librarian_id':
		//						$key = array_keys($value, xdglRequest::LIBRARIAN_ID_MINE);
		//						if (count($key)) {
		//							$value[$key[0]] = $usr_id;
		//						}
		//						$xdglRequestList->where(array( 'librarian_id' => $value ));
		//						break;
		//					default:
		//						$xdglRequestList->where(array( $field => $value ));
		//						break;
		//				}
		//			}
		//		}
	}


	protected function parseData() {
		$filter = array( 'series' => $this->xoctOpenCast->getSeriesIdentifier() );
		$filter = array();
		$this->setData(xoctEvent::getFiltered($filter));
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
