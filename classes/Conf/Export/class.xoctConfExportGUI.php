<?php

use srag\Plugins\Opencast\Model\Config\PluginConfig;

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
    /**
     * @var \ilToolbarGUI
     */
    protected $toolbar;

    public function __construct()
    {
        global $DIC;
        parent::__construct();
        $this->toolbar = $DIC->toolbar();
    }

    protected function index()
    {
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

    /**
     *
     */
    protected function import()
    {
        PluginConfig::importFromXML($_FILES['xoct_import']['tmp_name']);
        $this->cancel();
    }

    /**
     *
     * @return never
     */
    protected function export()
    {
        // ob_end_clean();
        header('Content-Disposition: attachment; filename="opencastexport.xml"');
        echo PluginConfig::getXMLExport();
        exit;
    }

    protected function add()
    {
    }

    protected function create()
    {
    }

    protected function edit()
    {
    }

    protected function update()
    {
    }

    protected function confirmDelete()
    {
    }

    protected function delete()
    {
    }
}
