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
	 * @param string $a_parent_cmd
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
		$this->initFilters();
		$this->setDefaultOrderField('title');
		$this->setExportFormats(array( self::EXPORT_CSV ));
		//		$this->setEnableNumInfo(true);
		//		$this->setExternalSorting(true);
		//		$this->setExternalSegmentation(true);
		// Add new

		$this->parseData();
	}


	/**
	 * @param $column
	 * @return bool
	 */
	public function isColumsSelected($column) {
		return in_array($column, $this->getSelectedColumns());
	}


	/**
	 * @param array $a_set
	 */
	public function fillRow($a_set) {
		global $ilUser;
		$xoctUser = xoctUser::getInstance($ilUser);
		/**
		 * @var $xE xoctEvent
		 * @var $xoctUser  xoctUser
		 */
		$xE = xoctEvent::find($a_set['identifier']);

		// $this->tpl->setVariable('ADDITIONAL_CSS', 'xoct-state-' . strtolower($xE->getProcessingState()));

		if ($xE->getThumbnailUrl() == xoctEvent::NO_PREVIEW) {
			$this->tpl->setVariable('PREVIEW', xoctEvent::NO_PREVIEW);
		} elseif ($xE->getThumbnailUrl()) {
			$this->tpl->setVariable('PREVIEW', $xE->getThumbnailUrl());
		}
		if ($xE->getProcessingState() == xoctEvent::STATE_SUCCEEDED) {
			if ($this->xoctOpenCast->getUseAnnotations()) {
				$annotationLink = $xE->getAnnotationLink();
				if ($annotationLink) {
					$this->tpl->setCurrentBlock('link');
					$this->tpl->setVariable('LINK_URL', $annotationLink);
					$this->tpl->setVariable('LINK_TEXT', $this->parent_obj->txt('annotate'));

					$this->tpl->parseCurrentBlock();
				}
			}
			$playerLink = $xE->getPlayerLink();
			if ($playerLink) {
				$this->tpl->setCurrentBlock('link');
				$this->tpl->setVariable('LINK_URL', $playerLink);
				$this->tpl->setVariable('LINK_TEXT', $this->parent_obj->txt('player'));
				if (xoctConf::get(xoctConf::F_USE_MODALS)) {
					require_once('./Services/UIComponent/Modal/classes/class.ilModalGUI.php');
					$modal = ilModalGUI::getInstance();
					$modal->setId('modal_' . $xE->getIdentifier());
					$modal->setHeading($xE->getTitle());
					$modal->setBody('<iframe class="xoct_iframe" src="' . $xE->getPlayerLink() . '"></iframe>');
					$this->tpl->setVariable('MODAL', $modal->getHTML());
					$this->tpl->setVariable('LINK_URL', '#');
					$this->tpl->setVariable('MODAL_LINK', 'data-toggle="modal" data-target="#modal_' . $xE->getIdentifier() . '"');
				}
				$this->tpl->parseCurrentBlock();
			}

			if (!$this->xoctOpenCast->getStreamingOnly()) {
				$downloadLink = $xE->getDownloadLink();
				if ($downloadLink) {
					$this->tpl->setCurrentBlock('link');
					$this->tpl->setVariable('LINK_URL', $downloadLink);
					$this->tpl->setVariable('LINK_TEXT', $this->parent_obj->txt('download'));
					$this->tpl->parseCurrentBlock();
				}
			}
		}

		if ($this->isColumsSelected('event_title')) {
			$this->tpl->setCurrentBlock('event_title');
			$this->tpl->setVariable('STATE_CSS', xoctEvent::$state_mapping[$xE->getProcessingState()]);

			if ($xE->getProcessingState() != xoctEvent::STATE_SUCCEEDED) {
				$this->tpl->setVariable('STATE', $this->parent_obj->txt('state_' . strtolower($xE->getProcessingState())));
			}
			$this->tpl->setVariable('TITLE', $xE->getTitle());
			$this->tpl->parseCurrentBlock();
		}
		if ($this->isColumsSelected('event_presenter')) {
			$this->tpl->setCurrentBlock('event_presenter');
			$this->tpl->setVariable('PRESENTER', $xE->getPresenter());
			$this->tpl->parseCurrentBlock();
		}

		if ($this->isColumsSelected('event_location')) {
			$this->tpl->setCurrentBlock('event_location');
			$this->tpl->setVariable('LOCATION', $xE->getLocation());
			$this->tpl->parseCurrentBlock();
		}

		if ($this->isColumsSelected('event_date')) {
			$this->tpl->setCurrentBlock('event_date');
			$this->tpl->setVariable('DATE', $xE->getCreated()->format('d.m.Y - H:i:s'));
			$this->tpl->parseCurrentBlock();
		}

		//		$this->tpl->setVariable('RECORDING_STATION', $xE->getMetadata()->getField('recording_station')->getValue());

		if ($this->xoctOpenCast->getPermissionPerClip()) {

			if ($this->isColumsSelected('event_owner')) {

				$this->tpl->setCurrentBlock('event_owner');

				$this->tpl->setVariable('OWNER', $xE->getOwnerUsername());
				if ($xE->isOwner($xoctUser)) {
					$this->tpl->setCurrentBlock('invitations');
					$in = xoctInvitation::where(array(
						'owner_id' => $xoctUser->getIliasUserId(),
						'event_identifier' => $xE->getIdentifier()
					))->count();
					if ($in > 0) {
						$this->tpl->setVariable('INVITATIONS', $in);
					}
					$this->tpl->parseCurrentBlock();
				}
				$this->tpl->parseCurrentBlock();
			}
		}
		$this->addActionMenu($xE);
	}


	/**
	 * @return array
	 */
	protected function getAllColums() {
		$columns = array(
			'event_preview' => array(
				'selectable' => false,
				'sort_field' => null,
				'width' => '250px'
			),

			'event_clips' => array(
				'selectable' => false,
				'sort_field' => null,
			),

			'event_title' => array(
				'selectable' => true,
				'sort_field' => 'title',
			),

			'event_presenter' => array(
				'selectable' => true,
				'sort_field' => 'presenter',
			),

			'event_location' => array(
				'selectable' => true,
				'sort_field' => 'event_location',
			),

			'event_date' => array(
				'selectable' => true,
				'sort_field' => 'created_unix',
			),

			'event_owner' => array(
				'selectable' => true,
				'sort_field' => 'owner_username',
			),

			'common_actions' => array(
				'selectable' => false,
			),
		);

		if (!$this->xoctOpenCast->getPermissionPerClip()) {
			unset($columns['event_owner']);
		}

		return $columns;
	}


	protected function initColums() {
		$selected_colums = $this->getSelectedColumns();

		foreach ($this->getAllColums() as $text => $col) {
			if ($col['selectable'] == false OR in_array($text, $selected_colums)) {
				$this->addColumn($this->pl->txt($text), $col['sort_field'], $col['width']);
			}
		}
	}


	/**
	 * @param xoctEvent $xoctEvent
	 */
	protected function addActionMenu(xoctEvent $xoctEvent) {
		if ($xoctEvent->getProcessingState() != xoctEvent::STATE_SUCCEEDED && $xoctEvent->getProcessingState() != xoctEvent::STATE_NOT_PUBLISHED) {
			return;
		}
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

		if ((ilObjOpenCastAccess::getCourseRole() == ilObjOpenCastAccess::ROLE_ADMIN)) {
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

		if ($this->xoctOpenCast->getPermissionAllowSetOwn() && $xoctEvent->isOwner($xoctUser)) {
			$current_selection_list->addItem($this->pl->txt('event_invite_others'), 'invite_others', $this->ctrl->getLinkTargetByClass('xoctInvitationGUI', xoctInvitationGUI::CMD_STANDARD));
		}

		$this->tpl->setVariable('ACTIONS', $current_selection_list->getHTML());
	}


	protected function initFilters() {
		// TITLE
		$te = new ilTextInputGUI($this->parent_obj->txt('title'), 'title');
		$this->addAndReadFilterItem($te);

		// PRESENTER
		$te = new ilTextInputGUI($this->parent_obj->txt('presenter'), 'presenter');
		$this->addAndReadFilterItem($te);

		// LCOATION
		$te = new ilTextInputGUI($this->parent_obj->txt('location'), 'event_location');
		$this->addAndReadFilterItem($te);

		// OWNER
		$te = new ilTextInputGUI($this->parent_obj->txt('owner'), 'owner_username');
		$this->addAndReadFilterItem($te);

		// DATE
		require_once('./Services/Form/classes/class.ilDateDurationInputGUI.php');
		$date = new ilDateDurationInputGUI($this->parent_obj->txt('created'), 'created_unix');
		$date->setStart(new ilDateTime(time() - 1 * 365 * 24 * 60 * 60, IL_CAL_UNIX));
		$date->setEnd(new ilDateTime(time() + 1 * 365 * 24 * 60 * 60, IL_CAL_UNIX));
		$this->addAndReadFilterItem($date);

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


	protected function parseData() {
		global $ilUser;
		require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.ilObjOpenCastAccess.php');

		$user = '';
		$xoctUser = xoctUser::getInstance($ilUser);
		if ($this->xoctOpenCast->getPermissionPerClip() && ilObjOpenCastAccess::getCourseRole() == ilObjOpenCastAccess::ROLE_MEMBER) {
			$user = $xoctUser->getIVTRoleName();
		}
		$filter = array( 'series' => $this->xoctOpenCast->getSeriesIdentifier() );
		$a_data = xoctEvent::getFiltered($filter, NULL, NULL, $this->getOffset(), $this->getLimit());

		$a_data = array_filter($a_data, $this->filterPermissions());
		$a_data = array_filter($a_data, $this->filterArray());
		$this->setData($a_data);
	}


	/**
	 * @return Closure => $value) {
	 */
	protected function filterArray() {
		return function ($array) {
			$return = true;
			foreach ($this->filter as $field => $value) {
				switch ($field) {
					case 'created_unix':
						if (!$value['start'] || !$value['end']) {
							continue;
						}
						$dateObject = new ilDateTime($array['created_unix'], IL_CAL_UNIX);
						$within = ilDateTime::_within($dateObject, $value['start'], $value['end']);
						if (!$within) {
							$return = false;
						}
						break;
					default:
						if ($value === null || $value === '' || $value === false) {
							continue;
						}
						$strpos = (strpos(strtolower($array[$field]), strtolower($value)) !== false);
						if (!$strpos) {
							$return = false;
						}
						break;
				}
			}

			return $return;
		};
	}


	/**
	 * @return Closure
	 */
	protected function filterPermissions() {
		return function ($array) {
			global $ilUser;

			if (ilObjOpenCastAccess::getCourseRole() == ilObjOpenCastAccess::ROLE_MEMBER) {
				$xoctUser = xoctUser::getInstance($ilUser);
				$xoctEvent = xoctEvent::find($array['identifier']);
				if ($this->xoctOpenCast->getPermissionPerClip() && !$xoctEvent->hasReadAccess($xoctUser)) {
					return false;
				}
				if ($xoctEvent->getProcessingState() != xoctEvent::STATE_SUCCEEDED) {
					return false;
				}
			}

			return true;
		};
	}


	/**
	 * @param $item
	 */
	protected function addAndReadFilterItem(ilFormPropertyGUI $item) {
		$this->addFilterItem($item);
		$item->readFromSession();

		switch (true) {
			case ($item instanceof ilCheckboxInputGUI):
				$this->filter[$item->getPostVar()] = $item->getChecked();
				break;
			case ($item instanceof ilDateDurationInputGUI):
				$this->filter[$item->getPostVar()] = array(
					'start' => $item->getStart(),
					'end' => $item->getEnd()
				);
				break;
			default:
				$this->filter[$item->getPostVar()] = $item->getValue();
				break;
		}
	}


	/**
	 * @param object $a_csv
	 */
	protected function fillHeaderCSV($a_csv) {
		$data = $this->getData();
		foreach ($data[0] as $k => $v) {
			$a_csv->addColumn($k);
		}

		$a_csv->addRow();
	}


	/**
	 * @param object $a_csv
	 * @param array $a_set
	 */
	protected function fillRowCSV($a_csv, $a_set) {
		parent::fillRowCSV($a_csv, $a_set);
	}


	/**
	 * @return array
	 */
	public function getSelectableColumns() {
		$return = array();
		foreach ($this->getAllColums() as $text => $col) {
			if ($col['selectable']) {
				$return[$text] = array(
					'txt' => $this->pl->txt($text),
					'default' => true
				);
			}
		}

		return $return;
	}
}

?>
