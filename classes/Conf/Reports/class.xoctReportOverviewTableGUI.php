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
    }

    protected function getSelectableColumns2() {
        return [];
    }

    protected function initColumns() {
        $this->addColumn('', '', '', true);
	    $this->addColumn(self::dic()->language()->txt('message'));
	    $this->addColumn(self::plugin()->translate('sender'), 'sender');
	    $this->addColumn(self::dic()->language()->txt('date'), 'created_at');
    }

    protected function initData() {
        $filter_values = $this->getFilterValues();
        $filter_sender = $filter_values['sender'];
        /** @var ilDate $ilDate */
        if ($ilDate = $filter_values['date_from']) {
            $filter_date_from = $ilDate->get(IL_CAL_DATE, 'Y-m-d h:i:s');
        }
        if ($ilDate = $filter_values['date_to']) {
            $filter_date_to = $ilDate->get(IL_CAL_DATE, 'Y-m-d h:i:s');
        }

        if ($filter_date_from && $filter_date_to) {
            $data = xoctReport::where(['created_at' => $filter_date_from], ['created_at' => '>='])->where(['created_at' => $filter_date_to], ['created_at' => '<='])->getArray();
        } elseif ($filter_date_from) {
            $data = xoctReport::where(['created_at' => $filter_date_from], ['created_at' => '>='])->getArray();
        } elseif ($filter_date_to) {
            $data = xoctReport::where(['created_at' => $filter_date_to], ['created_at' => '<='])->getArray();
        } else {
            $data = xoctReport::getArray();
        }

        $filtered = [];
        foreach ($data as $key => $value) {
	        $value['sender'] = ilObjUser::_lookupLogin($value['user_id']) . ', ' . ilObjUser::_lookupEmail($value['user_id']);
	        if ($filter_sender && (strpos(strtolower($value['sender']), strtolower($filter_sender)) === false)) {
		        unset($data[$key]);
	        } else {
	        	$filtered[] = $value;
	        }
        }

        $this->setData($filtered);
    }

    protected function initFilterFields() {
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

    protected function initId() {
        $this->setId('xoct_reports');
    }

    protected function initTitle() {
    }

    protected function fillRow($row) {
	    $this->tpl->setVariable('ID', $row['id']);
        $ilAccordionGUI = new ilAccordionGUI();
        $ilAccordionGUI->addItem($row['subject'], $row['message']);
        $this->tpl->setVariable('SENDER', $row['sender']);
	    $this->tpl->setVariable('MESSAGE', $ilAccordionGUI->getHTML());
	    $this->tpl->setVariable('DATE', date('d.m.Y h:i:s', strtotime($row['created_at'])));
    }

}