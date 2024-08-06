<?php

declare(strict_types=1);
use ILIAS\FileUpload\FileUpload;

use srag\Plugins\Opencast\Model\Config\PluginConfig;
use ILIAS\Filesystem\Stream\Streams;

/**
 * Class xoctConfExportGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @version           1.0.0
 *
 * @ilCtrl_IsCalledBy xoctConfExportGUI : xoctMainGUI
 */
class xoctConfExportGUI extends xoctGUI
{
    private const EXPORT_FILE_NAME = 'opencastexport.xml';
    /**
     * @readonly
     */
    private FileUpload $upload;
    /**
     * @var \ilToolbarGUI
     */
    protected $toolbar;

    public function __construct()
    {
        global $DIC;
        parent::__construct();
        $this->toolbar = $DIC->toolbar();
        $this->upload = $DIC->upload();
    }

    protected function index(): void
    {
        $this->main_tpl->setOnScreenMessage('info', $this->plugin->txt('msg_admin_import_freindly_reminder_info'));
        $b = ilLinkButton::getInstance();
        $b->setCaption('rep_robj_xoct_admin_export');
        $b->setUrl($this->ctrl->getLinkTarget($this, 'export'));
        $this->toolbar->addButtonInstance($b);
        $this->toolbar->addSpacer();
        $this->toolbar->addSeparator();
        $this->toolbar->addSpacer();

        $this->toolbar->setFormAction($this->ctrl->getLinkTarget($this, 'import'), true);
        $import = new ilFileInputGUI('xoct_import', 'xoct_import');
        $this->toolbar->addInputItem($import);
        $this->toolbar->addFormButton($this->plugin->txt('admin_import'), 'import');
    }

    protected function import(): void
    {
        $this->upload->process();

        if (!$this->upload->hasUploads()) {
            $this->main_tpl->setOnScreenMessage('failure', $this->plugin->txt("admin_import_file_missign"), true);
            $this->cancel();
        }

        try {
            $upload_results = $this->upload->getResults();
            $upload = end($upload_results);
            PluginConfig::importFromXML($upload->getPath());
            $this->main_tpl->setOnScreenMessage('success', $this->plugin->txt('admin_import_success'), true);
        } catch (\Throwable $th) {
            throw $th;
            $this->main_tpl->setOnScreenMessage('failure', $this->plugin->txt("admin_import_failed"), true);
        }
        $this->cancel();
    }

    protected function export(): void
    {
        $body = Streams::ofString(PluginConfig::getXMLExport());

        $response = $this->http->response()->withHeader(
            'Content-Type',
            'application/xml'
        )->withHeader(
            'Content-Disposition',
            'attachment; filename="' . self::EXPORT_FILE_NAME . '"'
        )->withBody(
            $body
        )->withHeader(
            'Content-Length',
            (string) $body->getSize()
        );
        $this->http->saveResponse($response);
        $this->http->sendResponse();
        $this->http->close();
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
    }

    protected function delete(): void
    {
    }
}
