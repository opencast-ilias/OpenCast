<?php
use srag\DIC\OpenCast\DICTrait;
use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Model\API\Event\EventRepository;
use srag\Plugins\Opencast\Model\Config\PublicationUsage\PublicationUsageRepository;
use srag\Plugins\Opencast\Model\Config\PublicationUsage\PublicationUsage;

/**
 * Class xoctEventTableGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.00
 *
 */
class xoctEventTableGUI extends ilTable2GUI {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

	const TBL_ID = 'tbl_xoct';
	/**
	 * @var array
	 */
	protected $filter = array();
	/**
	 * @var xoctOpenCast
	 */
	protected $xoctOpenCast;
	/**
	 * @var xoctEventGUI
	 */
	protected $parent_obj;
    /**
     * @var bool
     */
	protected $has_scheduled_events = false;
	/**
	 * @var EventRepository
	 */
	protected $event_repository;

	/**
	 * xoctEventTableGUI constructor.
	 *
	 * @param xoctEventGUI $a_parent_obj
	 * @param string        $a_parent_cmd
	 * @param xoctOpenCast $xoctOpenCast
	 * @param               $load_data bool
	 */
	public function __construct(xoctEventGUI $a_parent_obj, $a_parent_cmd, xoctOpenCast $xoctOpenCast, $load_data = true) {
		$this->xoctOpenCast = $xoctOpenCast;
		$this->event_repository = new EventRepository(self::dic()->dic());
		$a_val = static::getGeneratedPrefix($xoctOpenCast);
		$this->setPrefix($a_val);
		$this->setFormName($a_val);
		$this->setId($a_val);
		self::dic()->ctrl()->saveParameter($a_parent_obj, $this->getNavParameter());
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->parent_obj = $a_parent_obj;
		$this->setRowTemplate('tpl.events.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast');
		$this->setFormAction(self::dic()->ctrl()->getFormAction($a_parent_obj));
		$this->initColumns();
		$this->initFilters();
		$this->setDefaultOrderField('created_unix');

		if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EXPORT_CSV)) {
			$this->setExportFormats(array( self::EXPORT_CSV ));
		}

		if ($load_data) {
			$this->parseData();
		}
	}


	/**
	 * @param xoctOpenCast $xoctOpenCast
	 */
	public static function setDefaultRowValue(xoctOpenCast $xoctOpenCast) {
		$_GET[self::getGeneratedPrefix($xoctOpenCast) . '_trows'] = 20;
	}


	/**
	 * @param xoctOpenCast $xoctOpenCast
	 *
	 * @return string
	 */
	public static function getGeneratedPrefix(xoctOpenCast $xoctOpenCast) {
		return self::TBL_ID . '_' . substr($xoctOpenCast->getSeriesIdentifier(), 0, 5);
	}


	/**
	 * @param $column
	 *
	 * @return bool
	 */
	public function isColumsSelected($column) {
		if (!array_key_exists($column, $this->getSelectableColumns())) {
			return true;
		}

		return in_array($column, $this->getSelectedColumns());
	}


	/**
	 * @param array $a_set
	 * @throws DICException
	 * @throws ilTemplateException
	 * @throws xoctException
	 */
	public function fillRow($a_set)
	{
		/**
		 * @var $xE        xoctEvent
		 * @var $xoctUser  xoctUser
		 */
		$xE = $a_set['object'] ? $a_set['object'] : xoctEvent::find($a_set['identifier']);
		$renderer = new xoctEventRenderer($xE, $this->xoctOpenCast);

		$renderer->insertThumbnail($this->tpl, null);
		$renderer->insertPlayerLink($this->tpl);
		$renderer->insertDownloadLink($this->tpl);
		$renderer->insertAnnotationLink($this->tpl);

		if ($this->isColumsSelected('event_title')) {
			$renderer->insertTitle($this->tpl);
			$renderer->insertState($this->tpl);
		}

		if ($this->isColumsSelected('event_description')) {
			$renderer->insertDescription($this->tpl);
		}

		if ($this->isColumsSelected('event_presenter')) {
			$renderer->insertPresenter($this->tpl);
		}

		if ($this->isColumsSelected('event_location')) {
			$renderer->insertLocation($this->tpl);
		}

		if ($this->isColumsSelected('event_start')) {
			$renderer->insertStart($this->tpl);
		}

		if ($this->isColumsSelected('event_owner')) {
			$renderer->insertOwner($this->tpl);
		}

		if (in_array('unprotected_link', $this->selected_column) && $this->isColumsSelected('unprotected_link')) {
			$renderer->insertUnprotectedLink($this->tpl);
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
				'sort_field' => NULL,
				'width' => '250px',
			),
			'event_clips' => array(
				'selectable' => false,
				'sort_field' => NULL,
			),
			'event_title' => array(
				'selectable' => false,
				'sort_field' => 'title',
			),
			'event_description' => array(
				'selectable' => true,
				'sort_field' => 'description',
				'default' => false,
			),
			'event_presenter' => array(
				'selectable' => true,
				'sort_field' => 'presenter',
			),
			'event_location' => array(
				'selectable' => true,
				'sort_field' => 'location',
			),
			'event_start' => array(
				'selectable' => true,
				'sort_field' => 'start_unix',
			),
			'event_owner' => array(
				'selectable' => true,
				'sort_field' => 'owner_username',
				'default' => $this->getOwnerColDefault(),
			),
			'unprotected_link' => array(
				'selectable' => true,
				'sort_field' => 'unprotected_link',
			),
			'common_actions' => array(
				'selectable' => false,
			),
		);

		if (!(new PublicationUsageRepository())->exists(PublicationUsage::USAGE_UNPROTECTED_LINK)) {
			unset($columns['unprotected_link']);
		}

		return $columns;
	}


	/**
	 * @return bool
	 */
	protected function getOwnerColDefault() {
		static $owner_visible;
		if ($owner_visible !== NULL) {
			return $owner_visible;
		}
		$owner_visible = (ilObjOpenCastAccess::isActionAllowedForRole('upload', 'member') || $this->xoctOpenCast->getPermissionPerClip());

		return $owner_visible;
	}


	/**
	 * @throws DICException
	 */
	protected function initColumns() {
		$selected_columns = $this->getSelectedColumns();

		foreach ($this->getAllColums() as $text => $col) {
			if (!$this->isColumsSelected($text)) {
				continue;
			}
			if ($col['selectable'] == false OR in_array($text, $selected_columns)) {
				$this->addColumn(self::plugin()->translate($text), $col['sort_field'], $col['width']);
			}
		}
	}


	/**
	 * @param xoctEvent $xoctEvent
	 * @throws DICException
	 */
	protected function addActionMenu(xoctEvent $xoctEvent) {
		$renderer = new xoctEventRenderer($xoctEvent, $this->xoctOpenCast);
		$actions = $renderer->getActions();
		if (empty($actions)) {
			return;
		}

		$dropdown = self::dic()->ui()->factory()->dropdown()->standard($actions)
			->withLabel(self::plugin()->translate('common_actions'));

		$this->tpl->setVariable('ACTIONS',
			self::dic()->ui()->renderer()->renderAsync($dropdown)
		);
	}


	protected function initFilters() {
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


	/**
	 * @throws xoctException
	 */
	protected function parseData() {
		$filter = array( 'series' => $this->xoctOpenCast->getSeriesIdentifier() );
		$a_data = $this->event_repository->getFiltered($filter, '', []);

		$a_data = array_filter($a_data, $this->filterPermissions());
		$a_data = array_filter($a_data, $this->filterArray());

        foreach ($a_data as $row) {
			if ($row['object']->isScheduled()) {
				$this->has_scheduled_events = true;
			}
		}
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
							continue 2;
						}
						$dateObject = new ilDateTime($array['created_unix'], IL_CAL_UNIX);
						$within = ilDateTime::_within($dateObject, $value['start'], $value['end']);
						if (!$within) {
							$return = false;
						}
						break;
					default:
						if ($value === NULL || $value === '' || $value === false) {
							continue 2;
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
	protected function filterPermissions()
	{
		return function ($array)
		{
			$xoctUser = xoctUser::getInstance(self::dic()->user());
			$xoctEvent = $array['object'] instanceof xoctEvent ? $array['object'] : xoctEvent::find($array['identifier']);

			return ilObjOpenCastAccess::hasReadAccessOnEvent($xoctEvent, $xoctUser, $this->xoctOpenCast);
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
					'end' => $item->getEnd(),
				);
				break;
			default:
				$this->filter[$item->getPostVar()] = $item->getValue();
				break;
		}
	}


	/**
	 * @param int $format
	 * @param bool $send
	 */
	public function exportData($format, $send = false) {
		if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EXPORT_CSV)) {
			echo "Access Denied";
			exit;
		}
		parent::exportData($format, $send);
	}


	/**
	 * @param object $a_csv
	 */
	protected function fillHeaderCSV($a_csv) {
		$data = $this->getData();
		foreach ($data[0] as $k => $v) {
			switch ($k) {
				case 'created_unix';
				case 'start_unix';
				case 'object';
					continue 2;
			}
			$a_csv->addColumn(self::plugin()->translate('event_' . $k));
		}
		$a_csv->addRow();
	}


	/**
	 * @param object $a_csv
	 * @param array  $a_set
	 */
	protected function fillRowCSV($a_csv, $a_set) {
		$set = array();
		foreach ($a_set as $k => $value) {
			switch ($k) {
				case 'created_unix';
				case 'start_unix';
				case 'object';
					continue 2;
			}

			$set[$k] = $value;
		}
		parent::fillRowCSV($a_csv, $set);
	}


	/**
	 * @return array
	 */
	public function getSelectableColumns() {
		static $selectable_columns;
		if ($selectable_columns !== NULL) {
			return $selectable_columns;
		}
		$selectable_columns = array();
		foreach ($this->getAllColums() as $text => $col) {
			if ($col['selectable']) {
				$selectable_columns[$text] = array(
					'txt' => self::plugin()->translate($text),
					'default' => isset($col['default']) ? $col['default'] : true,
				);
			}
		}

		return $selectable_columns;
	}


	/**
	 * @param $visible
	 * @param $xoctOpenCast
	 */
	public static function setOwnerFieldVisibility($visible, $xoctOpenCast) {
		$table_id = self::getGeneratedPrefix($xoctOpenCast);
		$query = self::dic()->database()->query("SELECT * FROM table_properties WHERE table_id = " . self::dic()->database()->quote($table_id, "text") . " AND property = 'selfields'");
		while ($rec = self::dic()->database()->fetchAssoc($query)) {
			$selfields = unserialize($rec['value']);
			if ($selfields['event_owner'] == $visible) {
				continue;
			}
			$selfields['event_owner'] = (bool)$visible;
			$usr_id = $rec['user_id'];
			self::dic()->database()->update('table_properties', array(
					'value' => array( 'text', serialize($selfields) )
				), array(
					'table_id' => array( 'text', $table_id ),
					'user_id' => array( 'integer', $usr_id ),
					'property' => array( 'text', 'selfields' ),
				));
		}
	}

    /**
     * @return bool
     */
    public function hasScheduledEvents()
    {
        return $this->has_scheduled_events;
    }


}

?>
