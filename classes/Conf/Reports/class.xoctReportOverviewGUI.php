<?php

use srag\Plugins\Opencast\Model\Report\Report;

/**
 * Class xoctReportOverviewGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctReportOverviewGUI : xoctMainGUI
 */
class xoctReportOverviewGUI extends xoctGUI {

    /**
     *
     */
    protected function index() {
        ilUtil::sendInfo(self::plugin()->translate('msg_reports_table'));
        $xoctReportOverviewTableGUI = new xoctReportOverviewTableGUI($this, self::CMD_STANDARD);
        self::dic()->ui()->mainTemplate()->setContent($xoctReportOverviewTableGUI->getHTML());
    }

    /**
     *
     */
    protected function applyFilter() {
        $xoctReportOverviewTableGUI = new xoctReportOverviewTableGUI($this, self::CMD_STANDARD);
        $xoctReportOverviewTableGUI->writeFilterToSession();
        $xoctReportOverviewTableGUI->resetOffset();
        self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
    }

    /**
     *
     */
    protected function resetFilter() {
        $xoctReportOverviewTableGUI = new xoctReportOverviewTableGUI($this, self::CMD_STANDARD);
        $xoctReportOverviewTableGUI->resetOffset();
        $xoctReportOverviewTableGUI->resetFilter();
        self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
    }

    /**
     *
     */
    protected function add() {
    }

    /**
     *
     */
    protected function create() {
    }

    /**
     *
     */
    protected function edit() {
    }

    /**
     *
     */
    protected function update() {
    }

    /**
     *
     */
    protected function confirmDelete() {
        foreach ($_POST['id'] as $id) {
            $report = Report::find($id);
            $report->delete();
        }
        ilUtil::sendSuccess(self::plugin()->translate('msg_success'));
        self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
    }

    /**
     *
     */
    protected function delete() {
        if (!is_array($_POST['id']) || empty($_POST['id'])) {
            self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
        }
        $ilConfirmationGUI = new ilConfirmationGUI();
        $ilConfirmationGUI->setFormAction(self::dic()->ctrl()->getFormAction($this, self::CMD_STANDARD));
        $ilConfirmationGUI->setHeaderText(self::plugin()->translate('msg_confirm_delete_reports'));
        foreach ($_POST['id'] as $id) {
            $report = Report::find($id);
            $ilConfirmationGUI->addItem('id[]', $id, $report->getSubject() . ' (' . $report->getCreatedAt() . ')');
        }
        $ilConfirmationGUI->addButton(self::dic()->language()->txt('delete'), self::CMD_CONFIRM);
        $ilConfirmationGUI->addButton(self::dic()->language()->txt('cancel'), self::CMD_STANDARD);
        self::dic()->ui()->mainTemplate()->setContent($ilConfirmationGUI->getHTML());
    }

}