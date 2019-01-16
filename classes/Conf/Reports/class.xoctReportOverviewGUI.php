<?php

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
        $xoctReportOverviewTableGUI = new xoctReportOverviewTableGUI($this, self::CMD_STANDARD);
        $this->tpl->setContent($xoctReportOverviewTableGUI->getHTML());
    }

    /**
     *
     */
    protected function applyFilter() {
        $xoctReportOverviewTableGUI = new xoctReportOverviewTableGUI($this, self::CMD_STANDARD);
        $xoctReportOverviewTableGUI->writeFilterToSession();
        $xoctReportOverviewTableGUI->resetOffset();
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    /**
     *
     */
    protected function resetFilter() {
        $xoctReportOverviewTableGUI = new xoctReportOverviewTableGUI($this, self::CMD_STANDARD);
        $xoctReportOverviewTableGUI->resetOffset();
        $xoctReportOverviewTableGUI->resetFilter();
        $this->ctrl->redirect($this, self::CMD_STANDARD);
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
            $report = xoctReport::find($id);
            $report->delete();
        }
        ilUtil::sendSuccess($this->pl->txt('msg_success'));
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    /**
     *
     */
    protected function delete() {
        if (!is_array($_POST['id']) || empty($_POST['id'])) {
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        }
        $ilConfirmationGUI = new ilConfirmationGUI();
        $ilConfirmationGUI->setFormAction($this->ctrl->getFormAction($this, self::CMD_STANDARD));
        $ilConfirmationGUI->setHeaderText($this->pl->txt('msg_confirm_delete_reports'));
        foreach ($_POST['id'] as $id) {
            $report = xoctReport::find($id);
            $ilConfirmationGUI->addItem('id[]', $id, $report->getSubject() . ' (' . $report->getCreatedAt() . ')');
        }
        $ilConfirmationGUI->addButton($this->lng->txt('delete'), self::CMD_CONFIRM);
        $ilConfirmationGUI->addButton($this->lng->txt('cancel'), self::CMD_STANDARD);
        $this->tpl->setContent($ilConfirmationGUI->getHTML());
    }

}