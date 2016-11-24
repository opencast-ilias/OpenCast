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
class xoctEventTableGUI extends ilTable2GUI
{

	const TBL_ID = 'tbl_xoct';
	/**
	 * @var ilOpenCastPlugin
	 */
	protected $pl;
	/**
	 * @var array
	 */
	protected $filter = array();
	/**
	 * @var \xoctOpenCast
	 */
	protected $xoctOpenCast;
	/**
	 * @var \ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var \xoctEventGUI
	 */
	protected $parent_obj;


	/**
	 * xoctEventTableGUI constructor.
	 *
	 * @param \xoctEventGUI $a_parent_obj
	 * @param string $a_parent_cmd
	 * @param \xoctOpenCast $xoctOpenCast
	 */
	public function __construct(xoctEventGUI $a_parent_obj, $a_parent_cmd, xoctOpenCast $xoctOpenCast)
	{
		/**
		 * @var $ilCtrl ilCtrl
		 */
		global $ilCtrl;
		$this->ctrl = $ilCtrl;
		$this->pl = ilOpenCastPlugin::getInstance();
		$this->xoctOpenCast = $xoctOpenCast;
		$a_val = static::getGeneratedPrefix($xoctOpenCast);
		$this->setPrefix($a_val);
		$this->setFormName($a_val);
		$this->setId($a_val);
		$this->ctrl->saveParameter($a_parent_obj, $this->getNavParameter());
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->parent_obj = $a_parent_obj;
		$this->setRowTemplate('tpl.events.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast');
		$this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
		$this->initColums();
		$this->initFilters();
		$this->setDefaultOrderField('created_unix');


		if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EXPORT_CSV)) {
			$this->setExportFormats(array( self::EXPORT_CSV ));
		}

		$this->parseData();
	}


	/**
	 * @param \xoctOpenCast $xoctOpenCast
	 */
	public static function setDefaultRowValue(xoctOpenCast $xoctOpenCast)
	{
		$_GET[self::getGeneratedPrefix($xoctOpenCast) . '_trows'] = 20;
	}


	/**
	 * @param \xoctOpenCast $xoctOpenCast
	 * @return string
	 */
	public static function getGeneratedPrefix(xoctOpenCast $xoctOpenCast)
	{
		return self::TBL_ID . '_' . substr($xoctOpenCast->getSeriesIdentifier(), 0, 5);
	}


	/**
	 * @param $column
	 * @return bool
	 */
	public function isColumsSelected($column)
	{
		if (!array_key_exists($column, $this->getSelectableColumns()))
		{
			return true;
		}

		return in_array($column, $this->getSelectedColumns());
	}


	/**
	 * @param array $a_set
	 */
	public function fillRow($a_set)
	{
		global $ilUser;
		$xoctUser = xoctUser::getInstance($ilUser);
		/**
		 * @var $xE        xoctEvent
		 * @var $xoctUser  xoctUser
		 */
		$xE = $a_set['object'] ? $a_set['object'] : xoctEvent::find($a_set['identifier']);

		if ($xE->getThumbnailUrl() == xoctEvent::NO_PREVIEW)
		{
			$this->tpl->setVariable('PREVIEW', xoctEvent::NO_PREVIEW);
		} elseif ($xE->getThumbnailUrl())
		{
			$this->tpl->setVariable('PREVIEW', $xE->getThumbnailUrl());
		}
		if ($xE->getProcessingState() == xoctEvent::STATE_SUCCEEDED)
		{
			// PLAYER LINK
			$playerLink = $xE->getPlayerLink();
			if ($playerLink)
			{
				$this->tpl->setCurrentBlock('link');
				$this->tpl->setVariable('LINK_URL', $playerLink);
				$this->tpl->setVariable('LINK_TEXT', $this->parent_obj->txt('player'));
				if (xoctConf::get(xoctConf::F_USE_MODALS))
				{
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
			// DOWNLOAD LINK
			if (!$this->xoctOpenCast->getStreamingOnly())
			{
				$downloadLink = $xE->getDownloadLink();
				if ($downloadLink)
				{
					$this->tpl->setCurrentBlock('link');
					$this->tpl->setVariable('LINK_URL', $downloadLink);
					$this->tpl->setVariable('LINK_TEXT', $this->parent_obj->txt('download'));
					$this->tpl->parseCurrentBlock();
				}
			}
			// ANNOTATIONS LINK
			if ($this->xoctOpenCast->getUseAnnotations())
			{
				$annotationLink = $xE->getAnnotationLink();
				if ($annotationLink)
				{
					$this->tpl->setCurrentBlock('link');
					$this->tpl->setVariable('LINK_URL', $annotationLink);
					$this->tpl->setVariable('LINK_TEXT', $this->parent_obj->txt('annotate'));

					$this->tpl->parseCurrentBlock();
				}
			}
		}

		if ($this->isColumsSelected('event_title'))
		{
			$this->tpl->setCurrentBlock('event_title');
			$this->tpl->setVariable('STATE_CSS', xoctEvent::$state_mapping[$xE->getProcessingState()]);

			if ($xE->getProcessingState() != xoctEvent::STATE_SUCCEEDED)
			{
				$this->tpl->setVariable('STATE', $this->parent_obj->txt('state_' . strtolower($xE->getProcessingState())));
			}
			$this->tpl->setVariable('TITLE', $xE->getTitle());
			$this->tpl->parseCurrentBlock();
		}

		if ($this->isColumsSelected('event_description'))
		{
			$this->tpl->setCurrentBlock('event_description');
			$this->tpl->setVariable('DESCRIPTION', $xE->getDescription());
			$this->tpl->parseCurrentBlock();
		}

		if ($this->isColumsSelected('event_presenter'))
		{
			$this->tpl->setCurrentBlock('event_presenter');
			$this->tpl->setVariable('PRESENTER', $xE->getPresenter());
			$this->tpl->parseCurrentBlock();
		}

		if ($this->isColumsSelected('event_location'))
		{
			$this->tpl->setCurrentBlock('event_location');
			$this->tpl->setVariable('LOCATION', $xE->getLocation());
			$this->tpl->parseCurrentBlock();
		}

		if ($this->isColumsSelected('event_date'))
		{
			$this->tpl->setCurrentBlock('event_date');
			$this->tpl->setVariable('DATE', $xE->getCreated()->add(new DateInterval('PT7200S'))->format('d.m.Y - H:i:s'));
			$this->tpl->parseCurrentBlock();
		}

		//		$this->tpl->setVariable('RECORDING_STATION', $xE->getMetadata()->getField('recording_station')->getValue());

		if ($this->isColumsSelected('event_owner'))
		{

			$this->tpl->setCurrentBlock('event_owner');

			$this->tpl->setVariable('OWNER', $xE->getOwnerUsername());
			if ($xE->isOwner($xoctUser))
			{
				$this->tpl->setCurrentBlock('invitations');
				$in = xoctInvitation::where(array(
					'owner_id'         => $xoctUser->getIliasUserId(),
					'event_identifier' => $xE->getIdentifier(),
				))->count();
				if ($in > 0)
				{
					$this->tpl->setVariable('INVITATIONS', $in);
				}
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->parseCurrentBlock();
		}

		$this->addActionMenu($xE);
	}


	/**
	 * @return array
	 */
	protected function getAllColums()
	{
		$columns = array(
			'event_preview'     => array(
				'selectable' => false,
				'sort_field' => null,
				'width'      => '250px',
			),
			'event_clips'       => array(
				'selectable' => false,
				'sort_field' => null,
			),
			'event_title'       => array(
				'selectable' => true,
				'sort_field' => 'title',
			),
			'event_description' => array(
				'selectable' => true,
				'sort_field' => 'description',
				'default'    => false,
			),
			'event_presenter'   => array(
				'selectable' => true,
				'sort_field' => 'presenter',
			),
			'event_location'    => array(
				'selectable' => true,
				'sort_field' => 'event_location',
			),
			'event_date'        => array(
				'selectable' => true,
				'sort_field' => 'created_unix',
			),
			'event_owner'       => array(
				'selectable' => true,
				'sort_field' => 'owner_username',
			),
			'common_actions'    => array(
				'selectable' => false,
			),
		);

		return $columns;
	}


	protected function initColums()
	{
		$selected_colums = $this->getSelectedColumns();

		foreach ($this->getAllColums() as $text => $col)
		{
			if (!$this->isColumsSelected($text))
			{
				continue;
			}
			if ($col['selectable'] == false OR in_array($text, $selected_colums))
			{
				$this->addColumn($this->pl->txt($text), $col['sort_field'], $col['width']);
			}
		}
	}


	/**
	 * @param xoctEvent $xoctEvent
	 */
	protected function addActionMenu(xoctEvent $xoctEvent)
	{
		if (!in_array($xoctEvent->getProcessingState(), array(
			xoctEvent::STATE_SUCCEEDED,
			xoctEvent::STATE_NOT_PUBLISHED,
			xoctEvent::STATE_OFFLINE,
			xoctEvent::STATE_FAILED,
			//			xoctEvent::STATE_ENCODING,
		))
		)
		{
			return;
		}
		global $ilUser;
		/**
		 * @var $xoctUser xoctUser
		 */
		$xoctUser = xoctUser::getInstance($ilUser);

		$ac = new ilAdvancedSelectionListGUI();
		$ac->setListTitle($this->pl->txt('common_actions'));
		$ac->setId('event_actions_' . $xoctEvent->getIdentifier());
		$ac->setUseImages(false);

		$this->ctrl->setParameter($this->parent_obj, xoctEventGUI::IDENTIFIER, $xoctEvent->getIdentifier());
		$this->ctrl->setParameterByClass('xoctInvitationGUI', xoctEventGUI::IDENTIFIER, $xoctEvent->getIdentifier());

		if (ilObjOpenCast::DEV) {
			$ac->addItem($this->pl->txt('event_view'), 'event_view', $this->ctrl->getLinkTarget($this->parent_obj, xoctEventGUI::CMD_VIEW));
		}

		// Edit Owner
		if (ilObjOpenCastAccess::checkAction('edit_owner', $xoctEvent, $xoctUser) && $this->xoctOpenCast->getPermissionPerClip()) {
			$ac->addItem($this->pl->txt('event_edit_owner'), 'event_edit_owner', $this->ctrl->getLinkTarget($this->parent_obj, xoctEventGUI::CMD_EDIT_OWNER));
		}

		// Share event
		if (ilObjOpenCastAccess::checkAction('share_event', $xoctEvent, $xoctUser) && $this->xoctOpenCast->getPermissionAllowSetOwn()) {
			$ac->addItem($this->pl->txt('event_invite_others'), 'invite_others', $this->ctrl->getLinkTargetByClass('xoctInvitationGUI', xoctInvitationGUI::CMD_STANDARD));
		}

		// Cut Event
		if (ilObjOpenCastAccess::checkAction('cut', $xoctEvent, $xoctUser)) {
			$ac->addItem($this->pl->txt('event_cut'), 'event_cut', $this->ctrl->getLinkTarget($this->parent_obj, xoctEventGUI::CMD_CUT));
		}

		// Delete Event
		if (ilObjOpenCastAccess::checkAction('delete_event', $xoctEvent, $xoctUser)) {
			$ac->addItem($this->pl->txt('event_delete'), 'event_delete', $this->ctrl->getLinkTarget($this->parent_obj, xoctEventGUI::CMD_CONFIRM));
		}

		// Edit Event
		if (ilObjOpenCastAccess::checkAction('edit_event', $xoctEvent, $xoctUser)) {
			$ac->addItem($this->pl->txt('event_edit'), 'event_edit', $this->ctrl->getLinkTarget($this->parent_obj, xoctEventGUI::CMD_EDIT));
		}

		// Online/offline
		if (ilObjOpenCastAccess::checkAction('set_online_offline', $xoctEvent, $xoctUser)) {
			if ($xoctEvent->getXoctEventAdditions()->getIsOnline()) {
				$ac->addItem($this->pl->txt('event_set_offline'), 'event_set_offline', $this->ctrl->getLinkTarget($this->parent_obj, xoctEventGUI::CMD_SET_OFFLINE));
			} else {
				$ac->addItem($this->pl->txt('event_set_online'), 'event_set_online', $this->ctrl->getLinkTarget($this->parent_obj, xoctEventGUI::CMD_SET_ONLINE));
			}
		}

		$this->tpl->setVariable('ACTIONS', $ac->getHTML());
	}


	protected function initFilters()
	{
		// TITLE
		$te = new ilTextInputGUI($this->parent_obj->txt('title'), 'title');
		$this->addAndReadFilterItem($te);
		//
		//		// DESCRIPTION
		//		$te = new ilTextInputGUI($this->parent_obj->txt('description'), 'description');
		//		$this->addAndReadFilterItem($te);

		// PRESENTER
		$te = new ilTextInputGUI($this->parent_obj->txt('presenter'), 'presenter');
		$this->addAndReadFilterItem($te);

		// LCOATION
		$te = new ilTextInputGUI($this->parent_obj->txt('location'), 'location');
		$this->addAndReadFilterItem($te);

		// OWNER
		$te = new ilTextInputGUI($this->parent_obj->txt('owner'), 'owner_username');
		$this->addAndReadFilterItem($te);

		// DATE
		//		require_once('./Services/Form/classes/class.ilDateDurationInputGUI.php');
		//		$date = new ilDateDurationInputGUI($this->parent_obj->txt('created'), 'created_unix');
		//		$date->setStart(new ilDateTime(time() - 1 * 365 * 24 * 60 * 60, IL_CAL_UNIX));
		//		$date->setEnd(new ilDateTime(time() + 1 * 365 * 24 * 60 * 60, IL_CAL_UNIX));
		//		$this->addAndReadFilterItem($date);

	}


	protected function parseData()
	{
		require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.ilObjOpenCastAccess.php');

		$filter = array( 'series' => $this->xoctOpenCast->getSeriesIdentifier() );
		$a_data = xoctEvent::getFiltered($filter, null, null, $this->getOffset(), $this->getLimit());

		$a_data = array_filter($a_data, $this->filterPermissions());
		$a_data = array_filter($a_data, $this->filterArray());
		$this->setData($a_data);
	}


	/**
	 * @return Closure => $value) {
	 */
	protected function filterArray()
	{
		return function ($array)
		{
			$return = true;
			foreach ($this->filter as $field => $value)
			{
				switch ($field)
				{
					case 'created_unix':
						if (!$value['start'] || !$value['end'])
						{
							continue;
						}
						$dateObject = new ilDateTime($array['created_unix'], IL_CAL_UNIX);
						$within = ilDateTime::_within($dateObject, $value['start'], $value['end']);
						if (!$within)
						{
							$return = false;
						}
						break;
					default:
						if ($value === null || $value === '' || $value === false)
						{
							continue;
						}
						$strpos = (strpos(strtolower($array[$field]), strtolower($value)) !== false);
						if (!$strpos)
						{
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
	protected function filterPermissions()
	{
		return function ($array)
		{
			global $ilUser;

			$xoctUser = xoctUser::getInstance($ilUser);
			$xoctEvent = $array['object'] instanceof xoctEvent ? $array['object'] : xoctEvent::find($array['identifier']);

			// edit_videos and write access see all videos
			if (ilObjOpenCastAccess::hasPermission('edit_videos') || ilObjOpenCastAccess::hasWriteAccess()) {
				return true;
			}

			// no ivt mode: only show online and published videos
			if (!$this->xoctOpenCast->getPermissionPerClip()) {
				return $xoctEvent->getXoctEventAdditions()->getIsOnline()
					&& $xoctEvent->getProcessingState() == xoctEvent::STATE_SUCCEEDED;
			}

			// ivt mode: if user is owner, show video
			return $xoctEvent->hasReadAccess($xoctUser);
		};
	}


	/**
	 * @param $item
	 */
	protected function addAndReadFilterItem(ilFormPropertyGUI $item)
	{
		$this->addFilterItem($item);
		$item->readFromSession();

		switch (true)
		{
			case ($item instanceof ilCheckboxInputGUI):
				$this->filter[$item->getPostVar()] = $item->getChecked();
				break;
			case ($item instanceof ilDateDurationInputGUI):
				$this->filter[$item->getPostVar()] = array(
					'start' => $item->getStart(),
					'end'   => $item->getEnd(),
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
	protected function fillHeaderCSV($a_csv)
	{
		$data = $this->getData();
		foreach ($data[0] as $k => $v)
		{
			switch ($k)
			{
				case 'created_unix';
				case 'object';
					continue 2;
			}
			$a_csv->addColumn($this->pl->txt('event_' . $k));
		}
		$a_csv->addRow();
	}


	/**
	 * @param object $a_csv
	 * @param array $a_set
	 */
	protected function fillRowCSV($a_csv, $a_set)
	{
		$set = array();
		foreach ($a_set as $k => $value)
		{
			switch ($k)
			{
				case 'created_unix';
				case 'object';
					continue 2;
			}

			$set[$k] = utf8_decode($value);
		}
		parent::fillRowCSV($a_csv, $set);
	}


	/**
	 * @return array
	 */
	public function getSelectableColumns()
	{
		$return = array();
		foreach ($this->getAllColums() as $text => $col)
		{
			if ($col['selectable'])
			{
				$return[$text] = array(
					'txt'     => $this->pl->txt($text),
					'default' => isset($col['default']) ? $col['default'] : true,
				);
			}
		}

		return $return;
	}
}

?>
