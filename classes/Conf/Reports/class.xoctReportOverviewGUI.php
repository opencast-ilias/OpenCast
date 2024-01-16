<?php

declare(strict_types=1);

use srag\Plugins\Opencast\Model\Report\Report;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;

/**
 * Class xoctReportOverviewGUI
 *
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctReportOverviewGUI : xoctMainGUI
 */
class xoctReportOverviewGUI extends xoctGUI
{
    use LocaleTrait;

    protected function index(): void
    {
        $this->main_tpl->setOnScreenMessage('info', $this->getLocaleString('msg_reports_table'));
        $xoctReportOverviewTableGUI = new xoctReportOverviewTableGUI($this, self::CMD_STANDARD);
        $this->main_tpl->setContent($xoctReportOverviewTableGUI->getHTML());
    }

    protected function applyFilter(): void
    {
        $xoctReportOverviewTableGUI = new xoctReportOverviewTableGUI($this, self::CMD_STANDARD);
        $xoctReportOverviewTableGUI->writeFilterToSession();
        $xoctReportOverviewTableGUI->resetOffset();
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    protected function resetFilter(): void
    {
        $xoctReportOverviewTableGUI = new xoctReportOverviewTableGUI($this, self::CMD_STANDARD);
        $xoctReportOverviewTableGUI->resetOffset();
        $xoctReportOverviewTableGUI->resetFilter();
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    protected function add(): void
    {
    }

    protected function create(): void
    {
    }

    protected function edit(): void
    {
    }

    protected function update(): void
    {
    }

    protected function confirmDelete(): void
    {
        foreach ($this->http->request()->getParsedBody()['id'] ?? [] as $id) {
            $report = Report::find((int) $id);
            $report->delete();
        }
        $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('msg_success'));
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    protected function delete(): void
    {
        if (!is_array($this->http->request()->getParsedBody()['id'] ?? null)) {
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        }
        $ilConfirmationGUI = new ilConfirmationGUI();
        $ilConfirmationGUI->setFormAction($this->ctrl->getFormAction($this, self::CMD_STANDARD));
        $ilConfirmationGUI->setHeaderText($this->getLocaleString('msg_confirm_delete_reports'));
        foreach ($this->http->request()->getParsedBody()['id'] ?? [] as $id) {
            $report = Report::find((int) $id);
            $ilConfirmationGUI->addItem('id[]', $id, $report->getSubject() . ' (' . $report->getCreatedAt() . ')');
        }
        $ilConfirmationGUI->setConfirm($this->getLocaleString('delete', 'common'), self::CMD_CONFIRM);
        $ilConfirmationGUI->setCancel($this->getLocaleString('cancel', 'common'), self::CMD_STANDARD);
        $this->main_tpl->setContent($ilConfirmationGUI->getHTML());
    }
}
