<?php

declare(strict_types=1);

use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Event\EventRepository;
use srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventAR;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageRepository;
use srag\Plugins\Opencast\Model\User\xoctUser;
use srag\Plugins\Opencast\DI\OpencastDIC;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDDataType;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogue;

/**
 * Class xoctEventTableGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.00
 *
 */
class xoctEventTableGUI extends ilTable2GUI
{
    public const TBL_ID = 'tbl_xoct';
    /**
     * @var ilOpenCastPlugin
     */
    protected $plugin;
    /**
     * @var OpencastDIC
     */
    protected $container;
    /**
     * @var array
     */
    protected $filter = [];
    /**
     * @var ObjectSettings
     */
    protected $object_settings;
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
     * @var \ILIAS\DI\UIServices
     */
    private $ui;
    /**
     * @var \ilObjUser
     */
    private $user;
    /**
     * @var \MDCatalogue
     */
    private $md_catalogue_event;

    public function __construct(
        xoctEventGUI $a_parent_obj,
        string $a_parent_cmd,
        ObjectSettings $object_settings,
        array $md_fields,
        array $data,
        string $lang_key,
        MDCatalogue $md_catalogue_event
    ) {
        global $DIC;
        $ctrl = $DIC->ctrl();
        $this->ui = $DIC->ui();
        $this->user = $DIC->user();
        $this->parent_obj = $a_parent_obj;
        $this->md_fields = $md_fields;
        $this->lang_key = $lang_key;
        $this->object_settings = $object_settings;
        $this->container = OpencastDIC::getInstance();
        $this->plugin = $this->container->plugin();
        $a_val = static::getGeneratedPrefix($a_parent_obj->getObjId());
        $this->setPrefix($a_val);
        $this->setFormName($a_val);
        $this->setId($a_val);
        $ctrl->saveParameter($a_parent_obj, $this->getNavParameter());
        parent::__construct($a_parent_obj, $a_parent_cmd);
        $this->setRowTemplate(
            'tpl.events.html',
            'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast'
        );
        $this->setFormAction($ctrl->getFormAction($a_parent_obj));
        $data = array_filter(
            $data,
            $this->filterPermissions() ?? function ($v, $k): bool {
                return !empty($v);
            },
            $this->filterPermissions() === null ? ARRAY_FILTER_USE_BOTH : 0
        );
        $this->setData($data);
        foreach ($data as $item) {
            /** @var Event $event */
            $event = $item['object'];
            if ($event->isScheduled()) {
                $this->has_scheduled_events = true;
                break;
            }
        }
        $this->initColumns();
        $this->setDefaultOrderField('startDate_s');

        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EXPORT_CSV)) {
            $this->setExportFormats([self::EXPORT_CSV]);
        }
        $this->md_catalogue_event = $md_catalogue_event;
    }

    public static function setDefaultRowValue(int $obj_id): void
    {
        // $_GET[self::getGeneratedPrefix($obj_id) . '_trows'] = 20; no longer possible to write into $_GET
    }

    public static function getGeneratedPrefix(int $obj_id): string
    {
        return self::TBL_ID . '_' . substr((string)$obj_id, 0, 5);
    }

    public function isColumnSelected($a_col): bool
    {
        if (!array_key_exists($a_col, $this->getSelectableColumns())) {
            return true;
        }

        if (isset($this->getSelectedColumns()[$a_col])) {
            return true;
        }

        $column_settings = $this->getSelectableColumns()[$a_col] ?? [];

        return false; //$column_settings['default'] ?? false;
    }

    /**
     * @param array $a_set
     * @throws ilTemplateException
     * @throws xoctException
     */
    #[ReturnTypeWillChange]
    protected function fillRow(/*array*/ $a_set): void
    {
        /**
         * @var $event        Event
         * @var $xoctUser     xoctUser
         */
        $event = $a_set['object'] ?: $this->event_repository->find($a_set['identifier']);
        $renderer = new xoctEventRenderer($event, $this->object_settings);

        $renderer->insertPreviewImage($this->tpl, '');
        $renderer->insertPlayerLink($this->tpl);

        // The object settings will be checked based from within the insertDownloadLink method!
        $renderer->insertDownloadLink($this->tpl);

        if ($this->object_settings->getUseAnnotations()) {
            $renderer->insertAnnotationLink($this->tpl);
        }

        $first = true;
        foreach ($this->md_fields as $md_field) {
            if ($this->isColumnSelected($md_field->getFieldId())) {
                $this->tpl->setCurrentBlock('generic' . ($first ? '_w_state' : ''));
                if ($first) {
                    $this->tpl->setVariable('STATE', $renderer->getStateHTML());
                }
                $md_field_def = $this->md_catalogue_event->getFieldById($md_field->getFieldId());
                $value = $a_set[$md_field->getFieldId()] ?? '';
                if ($md_field_def->getType()->getTitle() == MDDataType::TYPE_TEXT_SELECTION) {
                    $value = $md_field->getValues()[$value] ?? '';
                }
                $this->tpl->setVariable('VALUE', $value);
                $first = false;
                $this->tpl->parseCurrentBlock();
            }
        }

        if ($this->isColumnSelected('event_owner')) {
            $renderer->insertOwner($this->tpl, 'generic', 'VALUE', $a_set['owner_username']);
        }

        if ($this->has_unprotected_links
            && ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_VIEW_UNPROTECTED_LINK)) {
            $renderer->insertUnprotectedLink($this->tpl, 'generic', 'VALUE');
        }

        // In order to render dropdowns, we have to call its method here (at the end),
        // because the dropdown list gets its value during the call of download and annotate insertion.
        $renderer->renderDropdowns($this->tpl);

        $this->addActionMenu($event);
    }

    /**
     * @return array{selectable: true, sort_field: string, text: string}[]|array{selectable: false, sort_field: null, width: string, lang_var: string}[]|array{selectable: false, sort_field: null, lang_var: string}[]|array{selectable: true, sort_field: string, default: bool, lang_var: string}[]|array{selectable: false, sort_field: string, lang_var: string}[]|array{selectable: false, lang_var: string}[]
     */
    protected function getAllColumns(): array
    {
        $columns = [
            'event_preview' => [
                'selectable' => false,
                'sort_field' => null,
                'width' => '250px',
                'lang_var' => 'event_preview'
            ],
            'event_clips' => [
                'selectable' => false,
                'sort_field' => null,
                'lang_var' => 'event_clips'
            ],
        ];

        foreach ($this->md_fields as $md_field) {
            $field_id = $md_field->getFieldId();
            $columns[$field_id] = [
                'selectable' => true,
                'sort_field' => $field_id,
                'text' => $md_field->getTitle($this->lang_key)
            ];
        }

        $columns += [
            'event_owner' => [
                'selectable' => true,
                'sort_field' => 'owner_username',
                'default' => $this->getOwnerColDefault(),
                'lang_var' => 'event_owner'
            ],
            'unprotected_link' => [
                'selectable' => false,
                'sort_field' => 'unprotected_link',
                'lang_var' => 'unprotected_link'
            ],
            'common_actions' => [
                'selectable' => false,
                'lang_var' => 'common_actions'
            ],
        ];

        if (!$this->has_unprotected_links
            || !ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_VIEW_UNPROTECTED_LINK)
            || !(new PublicationUsageRepository())->exists(PublicationUsage::USAGE_UNPROTECTED_LINK)) {
            unset($columns['unprotected_link']);
        }

        return $columns;
    }

    protected function getOwnerColDefault(): bool
    {
        static $owner_visible;
        if ($owner_visible !== null) {
            return $owner_visible;
        }
        $owner_visible = (ilObjOpenCastAccess::isActionAllowedForRole(
            'upload',
            'member'
        ) || $this->object_settings->getPermissionPerClip());

        return $owner_visible;
    }

    protected function initColumns(): void
    {
        foreach ($this->getAllColumns() as $key => $col) {
            if (!$this->isColumnSelected($key)) {
                continue;
            }
            $this->addColumn(
                isset($col['lang_var']) ? $this->plugin->txt($col['lang_var']) : $col['text'],
                $col['sort_field']??'',
                $col['width']??''
            );
        }
    }

    protected function addActionMenu(Event $event): void
    {
        $renderer = new xoctEventRenderer($event, $this->object_settings);
        $actions = $renderer->getActions();
        if ($actions === []) {
            return;
        }

        $dropdown = $this->ui->factory()->dropdown()->standard($actions)
                             ->withLabel($this->plugin->txt('common_actions'));

        $this->tpl->setVariable(
            'ACTIONS',
            $this->ui->renderer()->renderAsync($dropdown)
        );
    }

    /**
     * @return Closure => $value) {
     */
    protected function filterArray(): Closure
    {
        return function ($array): bool {
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
                        if ($value === null || $value === '' || $value === false) {
                            continue 2;
                        }
                        $strpos = (stripos($array[$field], strtolower($value)) !== false);
                        if (!$strpos) {
                            $return = false;
                        }
                        break;
                }
            }

            return $return;
        };
    }

    protected function filterPermissions(): Closure
    {
        return function ($array): bool {
            $xoctUser = xoctUser::getInstance($this->user);
            $event = $array['object'] instanceof Event ? $array['object'] : $this->event_repository->find(
                $array['identifier']
            );

            return ilObjOpenCastAccess::hasReadAccessOnEvent($event, $xoctUser, $this->object_settings);
        };
    }

    protected function addAndReadFilterItem(ilFormPropertyGUI $item): void
    {
        $this->addFilterItem($item);
        $item->readFromSession();

        switch (true) {
            case ($item instanceof ilCheckboxInputGUI):
                $this->filter[$item->getPostVar()] = $item->getChecked();
                break;
            case ($item instanceof ilDateDurationInputGUI):
                $this->filter[$item->getPostVar()] = [
                    'start' => $item->getStart(),
                    'end' => $item->getEnd(),
                ];
                break;
            default:
                $this->filter[$item->getPostVar()] = $item->getValue();
                break;
        }
    }

    /**
     * @param int  $format
     * @param bool $send
     */
    public function exportData($format, $send = false): void
    {
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EXPORT_CSV)) {
            echo "Access Denied";
            exit;
        }
        parent::exportData($format, $send);
    }

    #[ReturnTypeWillChange]
    protected function fillRowCSV(/*ilCSVWriter*/ $a_csv, /*array*/ $a_set): void
    {
        $data = $this->getData();
        foreach ($data[0] as $k => $v) {
            switch ($k) {
                case 'created_unix':
                case 'start_unix':
                case 'object':
                    continue 2;
            }
            $a_csv->addColumn($k);
        }
        $a_csv->addRow();
    }


    public function getSelectableColumns(): array
    {
        static $selectable_columns;
        if ($selectable_columns !== null) {
            return $selectable_columns;
        }
        $selectable_columns = [];
        foreach ($this->getAllColumns() as $key => $col) {
            if ($col['selectable'] ?? false) {
                $col_title = isset($col['lang_var']) ? $this->plugin->txt($col['lang_var']) : $col['text'];
                $selectable_columns[$key] = [
                    'txt' => $col_title,
                    'default' => $col['default'] ?? true,
                ];
            }
        }

        return $selectable_columns;
    }

    public static function setOwnerFieldVisibility($visible, int $obj_id): void
    {
        global $DIC;
        $db = $DIC->database();
        $table_id = self::getGeneratedPrefix($obj_id);
        $query = $db->query(
            "SELECT * FROM table_properties WHERE table_id = " . $db->quote(
                $table_id,
                "text"
            ) . " AND property = 'selfields'"
        );
        while ($rec = $db->fetchAssoc($query)) {
            $selfields = unserialize($rec['value'], ['allowed_classes' => false]);
            if ($selfields['event_owner'] == $visible) {
                continue;
            }
            $selfields['event_owner'] = (bool) $visible;
            $usr_id = $rec['user_id'];
            $db->update('table_properties', [
                'value' => ['text', serialize($selfields)]
            ], [
                'table_id' => ['text', $table_id],
                'user_id' => ['integer', $usr_id],
                'property' => ['text', 'selfields'],
            ]);
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
