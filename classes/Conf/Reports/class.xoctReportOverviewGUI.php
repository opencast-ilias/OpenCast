<?php

use srag\Plugins\Opencast\Model\Report\Report;

/**
 * Class xoctReportOverviewGUI
 *
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctReportOverviewGUI : xoctMainGUI
 */
class xoctReportOverviewGUI extends xoctGUI
{
    /**
     * @var \ilGlobalTemplateInterface
     */
    private $main_tpl;
    /**
     * @var \ilLanguage
     */
    private $language;

    public function __construct()
    {
        global $DIC;
        parent::__construct();
        $this->main_tpl = $DIC->ui()->mainTemplate();
        $this->language = $DIC->language();
    }

    /**
     *
     */
    protected function index()
    {
        ilUtil::sendInfo($this->plugin->txt('msg_reports_table'));
        $xoctReportOverviewTableGUI = new xoctReportOverviewTableGUI($this, self::CMD_STANDARD);
        $this->main_tpl->setContent($xoctReportOverviewTableGUI->getHTML());
    }

    /**
     *
     */
    protected function applyFilter()
    {
        $xoctReportOverviewTableGUI = new xoctReportOverviewTableGUI($this, self::CMD_STANDARD);
        $xoctReportOverviewTableGUI->writeFilterToSession();
        $xoctReportOverviewTableGUI->resetOffset();
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    /**
     *
     */
    protected function resetFilter()
    {
        $xoctReportOverviewTableGUI = new xoctReportOverviewTableGUI($this, self::CMD_STANDARD);
        $xoctReportOverviewTableGUI->resetOffset();
        $xoctReportOverviewTableGUI->resetFilter();
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    /**
     *
     */
    protected function add()
    {
    }

    /**
     *
     */
    protected function create()
    {
    }

    /**
     *
     */
    protected function edit()
    {
    }

    /**
     *
     */
    protected function update()
    {
    }

    /**
     *
     */
    protected function confirmDelete()
    {
        foreach ($_POST['id'] as $id) {
            $report = Report::find($id);
            $report->delete();
        }
        ilUtil::sendSuccess($this->plugin->txt('msg_success'));
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    /**
     *
     */
    protected function delete()
    {
        if (!is_array($_POST['id']) || empty($_POST['id'])) {
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        }
        $ilConfirmationGUI = new ilConfirmationGUI();
        $ilConfirmationGUI->setFormAction($this->ctrl->getFormAction($this, self::CMD_STANDARD));
        $ilConfirmationGUI->setHeaderText($this->plugin->txt('msg_confirm_delete_reports'));
        foreach ($_POST['id'] as $id) {
            $report = Report::find($id);
            $ilConfirmationGUI->addItem('id[]', $id, $report->getSubject() . ' (' . $report->getCreatedAt() . ')');
        }
        $ilConfirmationGUI->addButton($this->language->txt('delete'), self::CMD_CONFIRM);
        $ilConfirmationGUI->addButton($this->language->txt('cancel'), self::CMD_STANDARD);
        $this->main_tpl->setContent($ilConfirmationGUI->getHTML());
    }
}
