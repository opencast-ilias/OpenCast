<?php

declare(strict_types=1);

use srag\CustomInputGUIs\OpenCast\PropertyFormGUI\PropertyFormGUI;
use srag\CustomInputGUIs\OpenCast\TableGUI\TableGUI;
use srag\Plugins\Opencast\Model\Report\Report;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;
use srag\Plugins\Opencast\LegacyHelpers\TableGUIConstants;
use srag\Plugins\Opencast\Container\Init;

/**
 * Class xoctReportOverviewTableGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctReportOverviewTableGUI extends ilTable2GUI
{
    use LocaleTrait;
    use \srag\Plugins\Opencast\LegacyHelpers\TableGUI;
    public $filter;

    public const ROW_TEMPLATE = "tpl.report_table_row.html";
    private ilOpenCastPlugin $plugin;

    /**
     * xoctReportOverviewTableGUI constructor.
     * @param $parent xoctReportOverviewGUI
     * @param $parent_cmd
     */
    public function __construct(?object $parent, string $parent_cmd)
    {
        global $DIC;
        $opencastContainer = Init::init();
        $this->plugin = $opencastContainer[ilOpenCastPlugin::class];
        $this->setSelectAllCheckbox('id[]');
        parent::__construct($parent, $parent_cmd);
        $this->setFormAction($DIC->ctrl()->getFormAction($parent));
        $this->addMultiCommand(xoctGUI::CMD_DELETE, $this->getLocaleString(xoctGUI::CMD_DELETE, 'common'));
        $this->initId();
        $this->initRowTemplate();
        $this->initFilter2();
        $this->initTitle();
        $this->initData();
    }

    protected function getRowTemplate(): string
    {
        return $this->plugin->getDirectory() . '/templates/default/' . self::ROW_TEMPLATE;
    }

    protected function getSelectableColumns2(): array
    {
        return [];
    }

    protected function initColumns(): void
    {
        $this->addColumn('', '', '', true);
        $this->addColumn($this->getLocaleString('message'));
        $this->addColumn($this->getLocaleString('sender'));
        $this->addColumn($this->getLocaleString('date'), 'created_at');
    }

    protected function initData(): void
    {
        $filter_date_from = null;
        $filter_date_to = null;
        $filter_values = $this->filter;
        $filter_sender = $filter_values['sender'] ?? '';
        /** @var ilDate $ilDate */
        if ($ilDate = $filter_values['date_from'] ?? null) {
            $filter_date_from = $ilDate->get(IL_CAL_DATE, 'Y-m-d H:i:s');
        }
        if ($ilDate = $filter_values['date_to'] ?? null) {
            $filter_date_to = $ilDate->get(IL_CAL_DATE, 'Y-m-d H:i:s');
        }

        if ($filter_date_from && $filter_date_to) {
            $data = Report::where(['created_at' => $filter_date_from], ['created_at' => '>='])->where(
                ['created_at' => $filter_date_to],
                ['created_at' => '<=']
            )->getArray();
        } elseif ($filter_date_from) {
            $data = Report::where(['created_at' => $filter_date_from], ['created_at' => '>='])->getArray();
        } elseif ($filter_date_to) {
            $data = Report::where(['created_at' => $filter_date_to], ['created_at' => '<='])->getArray();
        } else {
            $data = Report::getArray();
        }

        $filtered = [];
        foreach ($data as $key => $value) {
            $value['sender'] = ilObjUser::_lookupLogin((int) $value['user_id']) . ', ' . ilObjUser::_lookupEmail(
                (int) $value['user_id']
            );
            if ($filter_sender && (stripos($value['sender'], strtolower((string) $filter_sender)) === false)) {
                unset($data[$key]);
            } else {
                $filtered[] = $value;
            }
        }

        $this->setData($filtered);
    }

    private function initFilter2(): void // ilTable2GUI has it's own initFilter method final
    {
        $this->setFilterCommand(xoctReportOverviewGUI::CMD_APPLY_FILTER);
        //$this->setDefaultFilterVisiblity(false);
        //$this->setDisableFilterHiding(false);
        $this->initFilterFields();

        foreach ($this->filter_fields as $key => $field) {
            $this->filter_cache[$key] = $field;

            $this->addFilterItem($field);

            if ($this->hasSessionValue($field->getFieldId())) { // Supports filter default values
                $field->readFromSession();
            }
        }
    }

    protected function initFilterFields(): void
    {
        $sender = $this->addFilterItemByMetaType(
            'sender',
            self::FILTER_TEXT,
            false,
            $this->getLocaleString('sender')
        );
        $this->filter['sender'] = $sender->getValue();

        $range = new ilDateDurationInputGUI($this->getLocaleString('event_start'), 'date_range');
        $range->setAllowOpenIntervals(true);
        $range->setStartText('');
        $range->setEndText('');
        $this->addFilterItem($range, false);
        $range->readFromSession();
        $range = $range->getValue();
        $start = $range['start'] ?? null;
        $this->filter['date_from'] = $start === null ? null : new ilDateTime($start, IL_CAL_UNIX);
        $end = $range['end'] ?? null;
        $this->filter['date_to'] = $end === null ? null : new ilDateTime($end, IL_CAL_UNIX);
    }

    protected function initId(): void
    {
        $this->setId('xoct_reports');
    }

    protected function initTitle(): void
    {
    }

    protected function fillRow(array $row): void
    {
        $this->tpl->setVariable('ID', $row['id']);
        $ilAccordionGUI = new ilAccordionGUI();
        $ilAccordionGUI->addItem($row['subject'], $row['message']);
        $this->tpl->setVariable('SENDER', $row['sender']);
        $this->tpl->setVariable('MESSAGE', $ilAccordionGUI->getHTML());
        $this->tpl->setVariable('DATE', date('d.m.Y H:i:s', strtotime((string) $row['created_at'])));
    }
}
