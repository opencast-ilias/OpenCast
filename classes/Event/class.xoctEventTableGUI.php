<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctSecureLink.php');
require_once('class.xoctEvent.php');
require_once('./Services/Table/classes/class.ilTable2GUI.php');
require_once('./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php');
require_once('./Services/Form/classes/class.ilMultiSelectInputGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.ilObjOpenCastAccess.php');
require_once('./Services/UIComponent/Button/classes/class.ilLinkButton.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Invitations/class.xoctInvitationGUI.php');

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
		// Add new

		$this->parseData();
	}


	/**
	 * @param array $a_set
	 */
	public function fillRow($a_set) {
		global $ilUser;
		$xoctUser = xoctUser::getInstance($ilUser);
		/**
		 * @var $xoctEvent xoctEvent
		 * @var $xoctUser  xoctUser
		 */
		$xoctEvent = xoctEvent::find($a_set['identifier']);

		$this->tpl->setVariable('ADDITIONAL_CSS', 'xoct-state-' . strtolower($xoctEvent->getProcessingState()));

		if ($xoctEvent->getThumbnailUrl() == xoctEvent::NO_PREVIEW) {
			$this->tpl->setVariable('PREVIEW', xoctEvent::NO_PREVIEW);
		} elseif ($xoctEvent->getThumbnailUrl()) {
			$this->tpl->setVariable('PREVIEW', xoctSecureLink::sign($xoctEvent->getThumbnailUrl()));
		}
		if ($xoctEvent->getProcessingState() != xoctEvent::STATE_SUCCEEDED) {
			//			$this->tpl->setVariable('STATE', $this->parent_obj->txt('state_' . strtolower($xoctEvent->getProcessingState())));
		}

		if ($this->xoctOpenCast->getUseAnnotations()) {
			$this->tpl->setCurrentBlock('link');
			$this->tpl->setVariable('LINK_URL', $xoctEvent->getAnnotationLink());
			$this->tpl->setVariable('LINK_TEXT', $this->parent_obj->txt('annotate'));

			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setCurrentBlock('link');
		$this->tpl->setVariable('LINK_URL', $xoctEvent->getPlayerLink());
		$this->tpl->setVariable('LINK_TEXT', $this->parent_obj->txt('player'));
		if (xoctConf::get(xoctConf::F_USE_MODALS)) {
			require_once('./Services/UIComponent/Modal/classes/class.ilModalGUI.php');
			$modal = ilModalGUI::getInstance();
			$modal->setId('modal_' . $xoctEvent->getIdentifier());
			$modal->setHeading($xoctEvent->getTitle());
			$modal->setBody('<iframe class="xoct_iframe" src="' . $xoctEvent->getPlayerLink() . '"></iframe>');
			$this->tpl->setVariable('MODAL', $modal->getHTML());
			$this->tpl->setVariable('LINK_URL', '#');
			$this->tpl->setVariable('MODAL_LINK', 'data-toggle="modal" data-target="#modal_' . $xoctEvent->getIdentifier() . '"');
		}
		$this->tpl->parseCurrentBlock();

		if (! $this->xoctOpenCast->getStreamingOnly()) {
			$this->tpl->setCurrentBlock('link');
			$this->tpl->setVariable('LINK_URL', $xoctEvent->getDownloadLink());
			$this->tpl->setVariable('LINK_TEXT', $this->parent_obj->txt('download'));
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->setVariable('TITLE', $xoctEvent->getTitle());
		$this->tpl->setVariable('PRESENTER', $xoctEvent->getPresenter());
		$this->tpl->setVariable('LOCATION', $xoctEvent->getLocation());
		$this->tpl->setVariable('RECORDING_STATION', $xoctEvent->getMetadata()->getField('recording_station')->getValue());
		$this->tpl->setVariable('DATE', $xoctEvent->getCreated()->format('d.m.Y - H:i:s'));
		if ($this->xoctOpenCast->getPermissionPerClip()) {
			$this->tpl->setCurrentBlock('owner');

			$this->tpl->setVariable('OWNER', $xoctEvent->getOwnerUsername());
			if ($xoctEvent->isOwner($xoctUser)) {
				$this->tpl->setCurrentBlock('invitations');
				$in = xoctInvitation::where(array(
					'owner_id' => $xoctUser->getIliasUserId(),
					'event_identifier' => $xoctEvent->getIdentifier()
				))->count();
				if ($in > 0) {
					$this->tpl->setVariable('INVITATIONS', $in);
				}
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->parseCurrentBlock();
		}
		$this->addActionMenu($xoctEvent);
	}


	protected function initColums() {
		$this->addColumn($this->pl->txt('event_preview'), '', '250px');
		$this->addColumn($this->pl->txt('event_clips'));
		$this->addColumn($this->pl->txt('event_title'), 'title');
		$this->addColumn($this->pl->txt('event_presenter'), 'presenter');
		$this->addColumn($this->pl->txt('event_location'), 'location');
		//		$this->addColumn($this->pl->txt('event_recording_station'), 'recording_station');
		$this->addColumn($this->pl->txt('event_date'), 'date');
		if ($this->xoctOpenCast->getPermissionPerClip()) {
			$this->addColumn($this->pl->txt('event_owner'), 'owner_username');
		}
		$this->addColumn($this->pl->txt('common_actions'), "", "", false, "text-right");
	}


	/**
	 * @param xoctEvent $xoctEvent
	 */
	protected function addActionMenu(xoctEvent $xoctEvent) {
		global $ilUser;
		/**
		 * @var $xoctUser xoctUser
		 */
		$xoctUser = xoctUser::getInstance($ilUser);

		$current_selection_list = new ilAdvancedSelectionListGUI();
		$current_selection_list->setListTitle($this->pl->txt('common_actions'));
		$current_selection_list->setId('event_actions_' . $xoctEvent->getIdentifier());
		$current_selection_list->setUseImages(false);

		$this->ctrl->setParameter($this->parent_obj, xoctEventGUI::IDENTIFIER, $xoctEvent->getIdentifier());
		$this->ctrl->setParameterByClass('xoctInvitationGUI', xoctEventGUI::IDENTIFIER, $xoctEvent->getIdentifier());

		if (ilObjOpenCast::DEV) {
			$current_selection_list->addItem($this->pl->txt('event_view'), 'event_view', $this->ctrl->getLinkTarget($this->parent_obj, xoctEventGUI::CMD_VIEW));
		}

		if ((ilObjOpenCastAccess::getCourseRole() == ilObjOpenCastAccess::ROLE_ADMIN
			|| ($xoctEvent->hasWriteAccess($xoctUser) && $this->xoctOpenCast->getPermissionPerClip()))
		) {
			$current_selection_list->addItem($this->pl->txt('event_edit'), 'event_edit', $this->ctrl->getLinkTarget($this->parent_obj, xoctEventGUI::CMD_EDIT));
			$cutting_link = $xoctEvent->getPublicationMetadataForUsage(xoctPublicationUsage::find(xoctPublicationUsage::USAGE_CUTTING))->getUrl();
			if ($cutting_link) {
				$current_selection_list->addItem($this->pl->txt('event_cut'), 'event_cut', $cutting_link, '', '', '_blank');
			}
			$current_selection_list->addItem($this->pl->txt('event_delete'), 'event_delete', $this->ctrl->getLinkTarget($this->parent_obj, xoctEventGUI::CMD_CONFIRM));
			if ($this->xoctOpenCast->getPermissionPerClip()) {
				$current_selection_list->addItem($this->pl->txt('event_edit_owner'), 'event_edit_owner', $this->ctrl->getLinkTarget($this->parent_obj, xoctEventGUI::CMD_EDIT_OWNER));
			}
		}

		if ($this->xoctOpenCast->getPermissionAllowSetOwn() && $xoctEvent->hasWriteAccess($xoctUser)) {
			$current_selection_list->addItem($this->pl->txt('event_invite_others'), 'invite_others', $this->ctrl->getLinkTargetByClass('xoctInvitationGUI', xoctInvitationGUI::CMD_STANDARD));
		}

		if ($xoctEvent->getProcessingState() == xoctEvent::STATE_SUCCEEDED) {
			$this->tpl->setVariable('ACTIONS', $current_selection_list->getHTML());
		} else {
			$this->tpl->setVariable('ACTIONS', $this->parent_obj->txt('state_' . strtolower($xoctEvent->getProcessingState())));
		}
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
		global $ilUser;
		require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.ilObjOpenCastAccess.php');

		$user = '';
		$xoctUser = xoctUser::getInstance($ilUser);
		if ($this->xoctOpenCast->getPermissionPerClip() && ilObjOpenCastAccess::getCourseRole() == ilObjOpenCastAccess::ROLE_MEMBER) {
			$user = $xoctUser->getIVTRoleName();
		}
		$filter = array( 'series' => $this->xoctOpenCast->getSeriesIdentifier() );
		//		$filter = array();
		$a_data = xoctEvent::getFiltered($filter, $user, NULL);

		if ($this->xoctOpenCast->getPermissionPerClip() && ilObjOpenCastAccess::getCourseRole() == ilObjOpenCastAccess::ROLE_MEMBER) {
			foreach ($a_data as $i => $d) {
				$xoctEvent = xoctEvent::find($d['identifier']);
				if (! $xoctEvent->hasReadAccess($xoctUser)) {
					unset($a_data[$i]);
				}
			}
		}

		$this->setData($a_data);
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
