<?php

use srag\CustomInputGUIs\OpenCast\PropertyFormGUI\PropertyFormGUI;
use srag\CustomInputGUIs\OpenCast\TableGUI\TableGUI;
use srag\Plugins\Opencast\Model\Report\Report;

/**
 * Class xoctReportOverviewTableGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctReportOverviewTableGUI extends TableGUI
{
    public const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;
    public const ROW_TEMPLATE = "tpl.report_table_row.html";
    /**
     * @var ilLanguage
     */
    protected $lng;
    /**
     * @var ilOpenCastPlugin
     */
    private $plugin;

    /**
     * xoctReportOverviewTableGUI constructor.
     * @param $parent xoctReportOverviewGUI
     * @param $parent_cmd
     */
    public function __construct($parent, string $parent_cmd)
    {
        global $DIC, $opencastContainer;
        $this->plugin = $opencastContainer[ilOpenCastPlugin::class];
        $this->lng = $DIC->language();
        $this->addMultiCommand(xoctReportOverviewGUI::CMD_DELETE, $this->lng->txt(xoctReportOverviewGUI::CMD_DELETE));
        $this->setSelectAllCheckbox('id[]');
        parent::__construct($parent, $parent_cmd);
    }

    /**
     * @param array $row
     *
     * @return string|void
     */
    protected function getColumnValue(string $column, /*array*/ $row, int $format = self::DEFAULT_FORMAT): string
    {
    }

    protected function getSelectableColumns2(): array
    {
        return [];
    }

    /**
     * @throws \srag\DIC\OpenCast\Exception\DICException
     */
    protected function initColumns(): void
    {
        $this->addColumn('', '', '', true);
        $this->addColumn($this->lng->txt('message'));
        $this->addColumn($this->plugin->txt('sender'));
        $this->addColumn($this->lng->txt('date'), 'created_at');
    }

    /**
     * @throws Exception
     */
    protected function initData(): void
    {
        $filter_date_from = null;
        $filter_date_to = null;
        $filter_values = $this->getFilterValues();
        $filter_sender = $filter_values['sender'];
        /** @var ilDate $ilDate */
        if ($ilDate = $filter_values['date_from']) {
            $filter_date_from = $ilDate->get(IL_CAL_DATE, 'Y-m-d H:i:s');
        }
        if ($ilDate = $filter_values['date_to']) {
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
            $value['sender'] = ilObjUser::_lookupLogin($value['user_id']) . ', ' . ilObjUser::_lookupEmail(
                $value['user_id']
            );
            if ($filter_sender && (stripos($value['sender'], strtolower($filter_sender)) === false)) {
                unset($data[$key]);
            } else {
                $filtered[] = $value;
            }
        }

        $this->setData($filtered);
    }

    /**
     *
     */
    protected function initFilterFields(): void
    {
        $this->filter_fields = [
            "sender" => [
                PropertyFormGUI::PROPERTY_CLASS => ilTextInputGUI::class
            ],
            "date_from" => [
                PropertyFormGUI::PROPERTY_CLASS => ilDateTimeInputGUI::class
            ],
            "date_to" => [
                PropertyFormGUI::PROPERTY_CLASS => ilDateTimeInputGUI::class
            ],
        ];
    }

    /**
     *
     */
    protected function initId(): void
    {
        $this->setId('xoct_reports');
    }

    /**
     *
     */
    protected function initTitle(): void
    {
    }

    /**
     * @param array $row
     */
    protected function fillRow($row): void
    {
        $this->tpl->setVariable('ID', $row['id']);
        $ilAccordionGUI = new ilAccordionGUI();
        $ilAccordionGUI->addItem($row['subject'], $row['message']);
        $this->tpl->setVariable('SENDER', $row['sender']);
        $this->tpl->setVariable('MESSAGE', $ilAccordionGUI->getHTML());
        $this->tpl->setVariable('DATE', date('d.m.Y H:i:s', strtotime($row['created_at'])));
    }
}
