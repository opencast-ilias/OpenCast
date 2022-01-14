<?php

use srag\DIC\OpenCast\DICTrait;
use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Event\EventRepository;
use srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventAR;
use srag\Plugins\Opencast\Model\Metadata\MetadataField;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageRepository;

/**
 * Class xoctEventTableGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.00
 *
 */
class xoctEventTableGUI extends ilTable2GUI
{

    use DICTrait;

    const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

    const TBL_ID = 'tbl_xoct';
    /**
     * @var array
     */
    protected $filter = array();
    /**
     * @var ObjectSettings
     */
    protected $objectSettings;
    /**
     * @var xoctEventGUI
     */
    protected $parent_obj;
    /**
     * @var bool
     */
    protected $has_scheduled_events = false;
    /**
     * @var bool
     */
    protected $has_unprotected_links = false;
    /**
     * @var EventRepository
     */
    protected $event_repository;
    /**
     * @var MDFieldConfigEventAR[]
     */
    private $md_fields;
    /**
     * @var string
     */
    private $lang_key;

    /**
     * @param xoctEventGUI $a_parent_obj
     * @param string $a_parent_cmd
     * @param ObjectSettings $objectSettings
     * @param array $md_fields
     * @param array $data
     * @throws DICException
     * @throws xoctException
     */
    public function __construct(xoctEventGUI   $a_parent_obj,
                                string         $a_parent_cmd,
                                ObjectSettings $objectSettings,
                                array          $md_fields,
                                array          $data,
                                string         $lang_key)
    {
        $this->parent_obj = $a_parent_obj;
        $this->md_fields = $md_fields;
        $this->lang_key = $lang_key;
        $this->objectSettings = $objectSettings;
        $a_val = static::getGeneratedPrefix($a_parent_obj->getObjId());
        $this->setPrefix($a_val);
        $this->setFormName($a_val);
        $this->setId($a_val);
        self::dic()->ctrl()->saveParameter($a_parent_obj, $this->getNavParameter());
        parent::__construct($a_parent_obj, $a_parent_cmd);
        $this->setRowTemplate('tpl.events.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast');
        $this->setFormAction(self::dic()->ctrl()->getFormAction($a_parent_obj));
        $this->setData($data);
        $this->initColumns();
//        $this->setDefaultOrderField('created_unix');

        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EXPORT_CSV)) {
            $this->setExportFormats(array(self::EXPORT_CSV));
        }
    }

    public static function setDefaultRowValue(int $obj_id)
    {
        $_GET[self::getGeneratedPrefix($obj_id) . '_trows'] = 20;
    }


    public static function getGeneratedPrefix(int $obj_id)
    {
        return self::TBL_ID . '_' . substr($obj_id, 0, 5);
    }


    /**
     * @param $column
     *
     * @return bool
     */
    public function isColumsSelected($column)
    {
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
         * @var $event        Event
         * @var $xoctUser  xoctUser
         */
        $event = $a_set['object'] ?: $this->event_repository->find($a_set['identifier']);
        $renderer = new xoctEventRenderer($event, $this->objectSettings);

        $renderer->insertThumbnail($this->tpl, null);
        $renderer->insertPlayerLink($this->tpl);

        if (!$this->objectSettings->getStreamingOnly()) {
            $renderer->insertDownloadLink($this->tpl);
        }

        if ($this->objectSettings->getUseAnnotations()) {
            $renderer->insertAnnotationLink($this->tpl);
        }

        $first = true;
        foreach ($this->md_fields as $md_field) {
            if ($this->isColumsSelected($md_field->getId())) {
                $this->tpl->setCurrentBlock('generic' . ($first ? '_w_state' : ''));
                if ($first) {
                    $this->tpl->setVariable('STATE', $renderer->getStateHTML());
                }
                $this->tpl->setVariable('VALUE', $a_set[$md_field->getFieldId()]);
                $first = false;
                $this->tpl->parseCurrentBlock();
            }
        }

        if ($this->isColumsSelected('event_owner')) {
            $renderer->insertOwner($this->tpl, 'generic', 'VALUE');
        }

        if ($this->has_unprotected_links
            && ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_VIEW_UNPROTECTED_LINK)) {
            $renderer->insertUnprotectedLink($this->tpl, 'generic', 'VALUE');
        }

        $this->addActionMenu($event);
    }


    /**
     * @return array
     */
    protected function getAllColums()
    {
        $columns = array(
            'event_preview' => array(
                'selectable' => false,
                'sort_field' => NULL,
                'width' => '250px',
                'lang_var' => 'event_preview'
            ),
            'event_clips' => array(
                'selectable' => false,
                'sort_field' => NULL,
                'lang_var' => 'event_clips'
            ),
        );

        foreach ($this->md_fields as $md_field) {
            $columns[$md_field->getTitle($this->lang_key)] = [
                'selectable' => true,
                'sort_field' => $md_field->getFieldId() . '_s'
            ];
        }

        $columns += [
            'event_owner' => array(
                'selectable' => true,
                'sort_field' => 'owner_username',
                'default' => $this->getOwnerColDefault(),
                'lang_var' => 'event_owner'
            ),
            'unprotected_link' => array(
                'selectable' => false,
                'sort_field' => 'unprotected_link',
                'lang_var' => 'unprotected_link'
            ),
            'common_actions' => array(
                'selectable' => false,
                'lang_var' => 'common_actions'
            ),
        ];

        if (!(new PublicationUsageRepository())->exists(PublicationUsage::USAGE_UNPROTECTED_LINK)
            || !$this->has_unprotected_links
            || !ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_VIEW_UNPROTECTED_LINK)) {
            unset($columns['unprotected_link']);
        }

        return $columns;
    }


    /**
     * @return bool
     */
    protected function getOwnerColDefault()
    {
        static $owner_visible;
        if ($owner_visible !== NULL) {
            return $owner_visible;
        }
        $owner_visible = (ilObjOpenCastAccess::isActionAllowedForRole('upload', 'member') || $this->objectSettings->getPermissionPerClip());

        return $owner_visible;
    }


    /**
     * @throws DICException
     */
    protected function initColumns()
    {
        $selected_columns = $this->getSelectedColumns();

        foreach ($this->getAllColums() as $key => $col) {
            if (!$this->isColumsSelected($key)) {
                continue;
            }
            if ($col['selectable'] == false or in_array($key, $selected_columns)) {
                $col_title = isset($col['lang_var']) ? self::plugin()->translate($col['lang_var']) : $key;
                $this->addColumn($col_title, $col['sort_field'], $col['width']);
            }
        }
    }


    /**
     * @param Event $event
     * @throws DICException
     */
    protected function addActionMenu(Event $event)
    {
        $renderer = new xoctEventRenderer($event, $this->objectSettings);
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

    /**
     * @throws xoctException
     */
    protected function parseData()
    {
        $filter = array('series' => $this->objectSettings->getSeriesIdentifier());
        $a_data = $this->event_repository->getFiltered($filter, '', []);

        $a_data = array_filter($a_data, $this->filterPermissions());
        $a_data = array_filter($a_data, $this->filterArray());

        foreach ($a_data as $row) {
            /** @var $object Event */
            $object = $row['object'];
            if ($object->isScheduled()) {
                $this->has_scheduled_events = true;
            }
            if ($object->publications()->getUnprotectedLink()) {
                $this->has_unprotected_links = true;
            }
        }
        $this->setData($a_data);
    }


    /**
     * @return Closure => $value) {
     */
    protected function filterArray()
    {
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
        return function ($array) {
            $xoctUser = xoctUser::getInstance(self::dic()->user());
            $event = $array['object'] instanceof Event ? $array['object'] : $this->event_repository->find($array['identifier']);

            return ilObjOpenCastAccess::hasReadAccessOnEvent($event, $xoctUser, $this->objectSettings);
        };
    }


    /**
     * @param $item
     */
    protected function addAndReadFilterItem(ilFormPropertyGUI $item)
    {
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
    public function exportData($format, $send = false)
    {
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EXPORT_CSV)) {
            echo "Access Denied";
            exit;
        }
        parent::exportData($format, $send);
    }


    /**
     * @param object $a_csv
     */
    protected function fillHeaderCSV($a_csv)
    {
        $data = $this->getData();
        foreach ($data[0] as $k => $v) {
            switch ($k) {
                case 'created_unix';
                case 'start_unix';
                case 'object';
                    continue 2;
            }
            $a_csv->addColumn($k);
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
    public function getSelectableColumns()
    {
        static $selectable_columns;
        if ($selectable_columns !== NULL) {
            return $selectable_columns;
        }
        $selectable_columns = array();
        foreach ($this->getAllColums() as $key => $col) {
            if ($col['selectable']) {
                $col_title = isset($col['lang_var']) ? self::plugin()->translate($col['lang_var']) : $key;
                $selectable_columns[$key] = array(
                    'txt' => $col_title,
                    'default' => isset($col['default']) ? $col['default'] : true,
                );
            }
        }

        return $selectable_columns;
    }

    public static function setOwnerFieldVisibility($visible, int $obj_id)
    {
        $table_id = self::getGeneratedPrefix($obj_id);
        $query = self::dic()->database()->query("SELECT * FROM table_properties WHERE table_id = " . self::dic()->database()->quote($table_id, "text") . " AND property = 'selfields'");
        while ($rec = self::dic()->database()->fetchAssoc($query)) {
            $selfields = unserialize($rec['value']);
            if ($selfields['event_owner'] == $visible) {
                continue;
            }
            $selfields['event_owner'] = (bool)$visible;
            $usr_id = $rec['user_id'];
            self::dic()->database()->update('table_properties', array(
                'value' => array('text', serialize($selfields))
            ), array(
                'table_id' => array('text', $table_id),
                'user_id' => array('integer', $usr_id),
                'property' => array('text', 'selfields'),
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
