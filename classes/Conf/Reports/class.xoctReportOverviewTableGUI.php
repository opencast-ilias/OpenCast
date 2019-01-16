<?php

use srag\CustomInputGUIs\OpenCast\PropertyFormGUI\PropertyFormGUI;
use srag\CustomInputGUIs\OpenCast\TableGUI\TableGUI;

/**
 * Class xoctReportOverviewTableGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctReportOverviewTableGUI extends TableGUI {

    const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;
    const ROW_TEMPLATE = "tpl.report_table_row.html";

    /**
     * xoctReportOverviewTableGUI constructor.
     * @param $parent xoctReportOverviewGUI
     * @param $parent_cmd
     */
    public function __construct($parent, $parent_cmd) {
        parent::__construct($parent, $parent_cmd);
        $this->addMultiCommand(xoctReportOverviewGUI::CMD_DELETE, self::dic()->language()->txt(xoctReportOverviewGUI::CMD_DELETE));
        $this->setSelectAllCheckbox('id[]');
    }


    protected function getColumnValue($column, $row, $raw_export = false) {
        // TODO: Implement getColumnValue() method.
    }

    protected function getSelectableColumns2() {
        return [];
    }

    protected function initColumns() {
        $this->addColumn('', '', '', true);
        $this->addColumn(self::dic()->language()->txt('message'));
        $this->addColumn(self::dic()->language()->txt('date'), 'created_at');
    }

    protected function initData() {
        $filter_values = $this->getFilterValues();
        /** @var ilDate $ilDate */
        if ($ilDate = $filter_values['date_from']) {
            $date_from = $ilDate->get(IL_CAL_DATE, 'Y-m-d h:i:s');
        }
        if ($ilDate = $filter_values['date_to']) {
            $date_to = $ilDate->get(IL_CAL_DATE, 'Y-m-d h:i:s');
        }

        if ($date_from && $date_to) {
            $this->setData(xoctReport::where(['created_at' => $date_from], ['created_at' => '>='])->where(['created_at' => $date_to], ['created_at' => '<='])->getArray());
        } elseif ($date_from) {
            $this->setData(xoctReport::where(['created_at' => $date_from], ['created_at' => '>='])->getArray());
        } elseif ($date_to) {
            $this->setData(xoctReport::where(['created_at' => $date_to], ['created_at' => '<='])->getArray());
        } else {
            $this->setData(xoctReport::getArray());
        }
    }

    protected function initFilterFields() {
        $this->filter_fields = [
            "date_from" => [
                PropertyFormGUI::PROPERTY_CLASS => ilDateTimeInputGUI::class
            ],
            "date_to" => [
                PropertyFormGUI::PROPERTY_CLASS => ilDateTimeInputGUI::class
            ],
        ];
    }

    protected function initId() {
        $this->setId('xoct_reports');
    }

    protected function initTitle() {
    }

    protected function fillRow($row) {
        $this->tpl->setVariable('ID', $row['id']);
        $ilAccordionGUI = new ilAccordionGUI();
        $ilAccordionGUI->addItem($row['subject'], $row['message']);
        $this->tpl->setVariable('MESSAGE', $ilAccordionGUI->getHTML());
        $this->tpl->setVariable('DATE', date('d.m.Y h:i:s', strtotime($row['created_at'])));
    }

}